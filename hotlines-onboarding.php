<?php
/**
 * Plugin Name: HotLines Onboarding
 * Description: Registers HotLines users and redeems access codes.
 * Version: 0.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('hotlines_register', 'hotlines_render_register_form');

/**
 * Main shortcode handler.
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
 * Registration form for new users.
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
            <label>First Name</label><br>
            <input type="text" name="first_name" required>
        </p>

        <p>
            <label>Last Name</label><br>
            <input type="text" name="last_name" required>
        </p>

        <p>
            <label>Email</label><br>
            <input type="email" name="email" required>
        </p>

        <p>
            <label>Password</label><br>
            <input type="password" name="password" required>
        </p>

        <p>
            <label>Access Code</label><br>
            <input type="text" name="access_code" required>
        </p>

        <p>
            <button type="submit">Create Account & Unlock Access</button>
        </p>
    </form>

    <?php
    return ob_get_clean();
}

/**
 * Code-only redemption form for existing logged-in users.
 */
function hotlines_render_code_only_form($message = '') {
    ob_start();
    ?>

    <?php echo wp_kses_post($message); ?>

    <form method="post">
        <?php wp_nonce_field('hotlines_register_action', 'hotlines_register_nonce'); ?>

        <p>
            <label>Access Code</label><br>
            <input type="text" name="access_code" required>
        </p>

        <p>
            <button type="submit">Unlock Additional Access</button>
        </p>
    </form>

    <?php
    return ob_get_clean();
}

/**
 * Handles registration for brand-new users.
 *
 * Important:
 * The access code is validated before the account is created.
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

    /**
     * Existing email case:
     * Do not create another user. Tell them to log in instead.
     */
    if (email_exists($email)) {
        $login_url = wp_login_url(get_permalink());

        return hotlines_error_message_with_link(
            'An account already exists with that email.',
            'Log in to redeem your code.',
            $login_url
        );
    }

    /**
     * Validate access code before creating user.
     */
    $code_post = hotlines_validate_access_code_from_request();

    if (is_wp_error($code_post)) {
        return hotlines_error_message($code_post->get_error_message());
    }

    /**
     * Create the WordPress user only after the code is confirmed valid.
     */
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

    /**
     * Grant access and mark the code as redeemed.
     */
    hotlines_redeem_code_for_user($user_id, $code_post->ID);

    /**
     * Log in the newly created user.
     */
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    wp_safe_redirect(site_url('/learn'));
    exit;
}

/**
 * Handles code redemption for already logged-in users.
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

    hotlines_redeem_code_for_user($user_id, $code_post->ID);

    return hotlines_success_message('Your new access has been unlocked.');
}

/**
 * Validates the nonce shared by both forms.
 */
function hotlines_verify_nonce() {
    return isset($_POST['hotlines_register_nonce'])
        && wp_verify_nonce($_POST['hotlines_register_nonce'], 'hotlines_register_action');
}

/**
 * Validates the submitted access code.
 */
function hotlines_validate_access_code_from_request() {
    $access_code = strtoupper(trim(sanitize_text_field($_POST['access_code'] ?? '')));

    if (empty($access_code)) {
        return new WP_Error('missing_code', 'Please enter an access code.');
    }

    $code_post = hotlines_find_access_code($access_code);

    if (!$code_post) {
        return new WP_Error('code_not_found', 'That access code was not found.');
    }

    $status = get_field('status', $code_post->ID);

    if ($status !== 'unused') {
        return new WP_Error('code_used', 'That access code has already been used or is no longer active.');
    }

    return $code_post;
}

/**
 * Finds an access-code post by title.
 */
function hotlines_find_access_code($access_code) {
    $query = new WP_Query([
        'post_type'      => 'access-code',
        'post_status'    => 'publish',
        'title'          => $access_code,
        'posts_per_page' => 1,
    ]);

    return $query->have_posts() ? $query->posts[0] : false;
}

/**
 * Grants access and records redemption data.
 */
function hotlines_redeem_code_for_user($user_id, $code_post_id) {
    hotlines_assign_groups_from_code($user_id, $code_post_id);

    update_field('status', 'redeemed', $code_post_id);
    update_field('redeemed_by', $user_id, $code_post_id);
    update_field('redeemed_at', current_time('mysql'), $code_post_id);
}

/**
 * Adds the user to Groups selected on the access code post.
 */
function hotlines_assign_groups_from_code($user_id, $code_post_id) {
    if (!function_exists('get_field')) {
        return;
    }

    $groups = get_field('groups_to_grant', $code_post_id);

    if (empty($groups) || !class_exists('Groups_User_Group')) {
        return;
    }

    foreach ($groups as $group_id) {
        Groups_User_Group::create([
            'user_id'  => $user_id,
            'group_id' => $group_id,
        ]);
    }
}

/**
 * Basic error message.
 */
function hotlines_error_message($message) {
    return '<p class="hotlines-message hotlines-error">' . esc_html($message) . '</p>';
}

/**
 * Error message with login link.
 */
function hotlines_error_message_with_link($message, $link_text, $url) {
    return '<p class="hotlines-message hotlines-error">'
        . esc_html($message)
        . ' <a href="' . esc_url($url) . '">'
        . esc_html($link_text)
        . '</a></p>';
}

/**
 * Basic success message.
 */
function hotlines_success_message($message) {
    return '<p class="hotlines-message hotlines-success">' . esc_html($message) . '</p>';
}