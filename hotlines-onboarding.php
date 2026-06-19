<?php
/**
 * Plugin Name: HotLines Onboarding
 * Description: Registers HotLines users and redeems access codes.
 * Version: 0.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('hotlines_register', 'hotlines_render_register_form');

function hotlines_render_register_form() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hotlines_register_nonce'])) {
        $message = hotlines_handle_registration();
    }

    ob_start();
    ?>

    <?php echo $message; ?>

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

function hotlines_handle_registration() {
    if (!isset($_POST['hotlines_register_nonce']) || !wp_verify_nonce($_POST['hotlines_register_nonce'], 'hotlines_register_action')) {
        return '<p>Security check failed.</p>';
    }

    $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
    $email       = sanitize_email($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $access_code = strtoupper(trim(sanitize_text_field($_POST['access_code'] ?? '')));

    if (empty($email) || empty($password) || empty($access_code)) {
        return '<p>Please complete all required fields.</p>';
    }

    if (email_exists($email)) {
        return '<p>An account already exists with that email. Please log in first.</p>';
    }

    $code_post = hotlines_find_access_code($access_code);

    if (!$code_post) {
        return '<p>That access code was not found.</p>';
    }

    $status = get_field('status', $code_post->ID);

    if ($status !== 'unused') {
        return '<p>That access code has already been used or is no longer active.</p>';
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
        return '<p>Could not create user: ' . esc_html($user_id->get_error_message()) . '</p>';
    }

    hotlines_assign_groups_from_code($user_id, $code_post->ID);

    update_field('status', 'redeemed', $code_post->ID);
    update_field('redeemed_by', $user_id, $code_post->ID);
    update_field('redeemed_at', current_time('mysql'), $code_post->ID);

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    wp_safe_redirect(site_url('/learn'));
    exit;
}

function hotlines_find_access_code($access_code) {
    $query = new WP_Query([
        'post_type'      => 'access-code',
        'post_status'    => 'publish',
        'title'          => $access_code,
        'posts_per_page' => 1,
    ]);

    if (!$query->have_posts()) {
        return false;
    }

    return $query->posts[0];
}

function hotlines_assign_groups_from_code($user_id, $code_post_id) {
    $groups = get_field('groups_to_grant', $code_post_id);

    if (empty($groups)) {
        return;
    }

    foreach ($groups as $group_id) {
        if (class_exists('Groups_User_Group')) {
            Groups_User_Group::create([
                'user_id'  => $user_id,
                'group_id' => $group_id,
            ]);
        }
    }
}