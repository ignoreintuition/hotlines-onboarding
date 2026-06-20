<?php
/**
 * Plugin Name: HotLines Onboarding
 * Description: Registers HotLines users and redeems access codes.
 * Version: 0.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration constants.
 */
define('HOTLINES_ACCESS_CODE_POST_TYPE', 'access-codes');

define('HOTLINES_FIELD_ACCESS_CODE', 'access_code');
define('HOTLINES_FIELD_STATUS', 'status');
define('HOTLINES_FIELD_GROUPS_TO_GRANT', 'groups_to_grant');
define('HOTLINES_FIELD_REDEEMED_BY', 'redeemed_by');
define('HOTLINES_FIELD_REDEEMED_AT', 'redeemed_at');

define('HOTLINES_STATUS_AVAILABLE', 'Available');
define('HOTLINES_STATUS_REDEEMED', 'Redeemed');

/**
 * Map ACF checkbox values to Groups plugin group IDs.
 *
 * Current Groups IDs:
 * yellow => Yellow Tier, ID 2
 * orange => Orange Tier, ID 3
 * red    => Red Tier, ID 4
 */
function hotlines_group_id_map() {
    return [
        'yellow' => 2,
        'orange' => 3,
        'red'    => 4,
    ];
}

/**
 * Shortcode:
 * [hotlines_register]
 */
add_shortcode('hotlines_register', 'hotlines_render_register_form');

/**
 * Main shortcode controller.
 *
 * Logged-out users see the full account registration form.
 * Logged-in users see the access-code-only form.
 */
function hotlines_render_register_form() {
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hotlines_register_nonce'])) {
        $message = is_user_logged_in()
            ? hotlines_handle_existing_user_redemption()
            : hotlines_handle_new_user_registration();
    }

    return is_user_logged_in()
        ? hotlines_render_code_only_form($message)
        : hotlines_render_registration_form($message);
}

/**
 * Render full registration form for new users.
 */
function hotlines_render_registration_form($message = '') {
    ob_start();
    ?>

    <?php echo wp_kses_post($message); ?>

    <p>
        Already have an account?
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
            Log in to redeem another code.
        </a>
    </p>

    <form method="post">
        <?php wp_nonce_field('hotlines_register_action', 'hotlines_register_nonce'); ?>

        <p>
            <label for="hotlines_first_name">First Name</label><br>
            <input id="hotlines_first_name" type="text" name="first_name" required>
        </p>

        <p>
            <label for="hotlines_last_name">Last Name</label><br>
            <input id="hotlines_last_name" type="text" name="last_name" required>
        </p>

        <p>
            <label for="hotlines_email">Email</label><br>
            <input id="hotlines_email" type="email" name="email" required>
        </p>

        <p>
            <label for="hotlines_password">Password</label><br>
            <input id="hotlines_password" type="password" name="password" required>
        </p>

        <p>
            <label for="hotlines_access_code">Access Code</label><br>
            <input id="hotlines_access_code" type="text" name="access_code" required>
        </p>

        <p>
            <button type="submit">Create Account & Unlock Access</button>
        </p>
    </form>

    <?php
    return ob_get_clean();
}

/**
 * Render code-only form for logged-in users.
 */
function hotlines_render_code_only_form($message = '') {
    ob_start();
    ?>

    <?php echo wp_kses_post($message); ?>

    <form method="post">
        <?php wp_nonce_field('hotlines_register_action', 'hotlines_register_nonce'); ?>

        <p>
            <label for="hotlines_access_code">Access Code</label><br>
            <input id="hotlines_access_code" type="text" name="access_code" required>
        </p>

        <p>
            <button type="submit">Unlock Additional Access</button>
        </p>
    </form>

    <?php
    return ob_get_clean();
}

/**
 * Handle registration for brand-new users.
 *
 * Important:
 * Access code validation happens before user creation.
 * That prevents users from registering without a valid, available code.
 */
function hotlines_handle_new_user_registration() {
    if (!hotlines_verify_nonce()) {
        return hotlines_error_message('Security check failed.');
    }

    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        return hotlines_error_message('Please complete all required fields.');
    }

    if (!is_email($email)) {
        return hotlines_error_message('Please enter a valid email address.');
    }

    if (email_exists($email)) {
        return hotlines_error_message_with_link(
            'An account already exists with that email.',
            'Log in to redeem your code.',
            wp_login_url(get_permalink())
        );
    }

    $code_post = hotlines_validate_access_code_from_request();

    if (is_wp_error($code_post)) {
        return hotlines_error_message($code_post->get_error_message());
    }

    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'user_pass'  => $password,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'role'       => 'subscriber',
    ]);

    if (is_wp_error($user_id)) {
        return hotlines_error_message('Could not create user: ' . $user_id->get_error_message());
    }

    $redeem_result = hotlines_redeem_code_for_user($user_id, $code_post->ID);

    if (is_wp_error($redeem_result)) {
        return hotlines_error_message($redeem_result->get_error_message());
    }

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    wp_safe_redirect(site_url('/learn'));
    exit;
}

/**
 * Handle access-code redemption for users who are already logged in.
 */
function hotlines_handle_existing_user_redemption() {
    if (!hotlines_verify_nonce()) {
        return hotlines_error_message('Security check failed.');
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        return hotlines_error_message('You must be logged in to redeem a code.');
    }

    $code_post = hotlines_validate_access_code_from_request();

    if (is_wp_error($code_post)) {
        return hotlines_error_message($code_post->get_error_message());
    }

    $redeem_result = hotlines_redeem_code_for_user($user_id, $code_post->ID);

    if (is_wp_error($redeem_result)) {
        return hotlines_error_message($redeem_result->get_error_message());
    }

    return hotlines_success_message('Your new access has been unlocked.');
}

/**
 * Verify form nonce.
 */
function hotlines_verify_nonce() {
    return isset($_POST['hotlines_register_nonce'])
        && wp_verify_nonce($_POST['hotlines_register_nonce'], 'hotlines_register_action');
}

/**
 * Validate submitted access code.
 *
 * Returns:
 * - WP_Post when the code is valid and available.
 * - WP_Error when the code is missing, unavailable, or already redeemed.
 */
function hotlines_validate_access_code_from_request() {
    $access_code = strtolower(trim(sanitize_text_field($_POST['access_code'] ?? '')));

    if (empty($access_code)) {
        return new WP_Error('missing_code', 'Please enter an access code.');
    }

    if (!function_exists('get_field')) {
        return new WP_Error('acf_missing', 'ACF fields are unavailable.');
    }

    $code_post = hotlines_find_access_code($access_code);

    if (!$code_post) {
        return new WP_Error('code_not_found', 'That access code was not found.');
    }

    $status = get_field(HOTLINES_FIELD_STATUS, $code_post->ID);

    if ($status !== HOTLINES_STATUS_AVAILABLE) {
        return new WP_Error('code_used', 'That access code has already been used or is no longer active.');
    }

    return $code_post;
}

/**
 * Find an access-code post by the ACF access_code field.
 */
function hotlines_find_access_code($access_code) {
    $query = new WP_Query([
        'post_type'      => HOTLINES_ACCESS_CODE_POST_TYPE,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => HOTLINES_FIELD_ACCESS_CODE,
                'value'   => $access_code,
                'compare' => '=',
            ],
        ],
    ]);

    return $query->have_posts() ? $query->posts[0] : false;
}

/**
 * Assign user to Groups and mark the access code as redeemed.
 */
function hotlines_redeem_code_for_user($user_id, $code_post_id) {
    $group_result = hotlines_assign_groups_from_code($user_id, $code_post_id);

    if (is_wp_error($group_result)) {
        return $group_result;
    }

    update_field(HOTLINES_FIELD_STATUS, HOTLINES_STATUS_REDEEMED, $code_post_id);
    update_field(HOTLINES_FIELD_REDEEMED_BY, $user_id, $code_post_id);
    update_field(HOTLINES_FIELD_REDEEMED_AT, current_time('mysql'), $code_post_id);

    return true;
}

/**
 * Assign the user to the Groups selected on the access-code post.
 *
 * The ACF checkbox stores values like:
 * - yellow
 * - orange
 * - red
 *
 * Those values are mapped to the numeric Groups IDs in hotlines_group_id_map().
 */
function hotlines_assign_groups_from_code($user_id, $code_post_id) {
    if (!function_exists('get_field')) {
        return new WP_Error('acf_missing', 'ACF is unavailable.');
    }

    if (!class_exists('Groups_User_Group')) {
        return new WP_Error('groups_missing', 'Groups plugin classes are unavailable.');
    }

    $groups = get_field(HOTLINES_FIELD_GROUPS_TO_GRANT, $code_post_id);

    if (empty($groups)) {
        return new WP_Error('no_groups', 'No groups were selected on this access code.');
    }

    $group_id_map = hotlines_group_id_map();

    foreach ($groups as $group_value) {
        if (!isset($group_id_map[$group_value])) {
            return new WP_Error(
                'bad_group_value',
                'Unknown group value: ' . print_r($group_value, true)
            );
        }

        Groups_User_Group::create([
            'user_id'  => (int) $user_id,
            'group_id' => (int) $group_id_map[$group_value],
        ]);
    }

    return true;
}

/**
 * Render a sanitized error message.
 */
function hotlines_error_message($message) {
    return '<p class="hotlines-message hotlines-error">' . esc_html($message) . '</p>';
}

/**
 * Render a sanitized error message with a link.
 */
function hotlines_error_message_with_link($message, $link_text, $url) {
    return '<p class="hotlines-message hotlines-error">'
        . esc_html($message)
        . ' <a href="' . esc_url($url) . '">'
        . esc_html($link_text)
        . '</a></p>';
}

/**
 * Render a sanitized success message.
 */
function hotlines_success_message($message) {
    return '<p class="hotlines-message hotlines-success">' . esc_html($message) . '</p>';
}