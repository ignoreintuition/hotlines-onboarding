<?php
/** Product access and redemption. Legacy public identifiers are preserved. */
defined('ABSPATH') || exit;

function pyropaths_request($key) {
    return isset($_POST[$key]) && is_string($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
}

function pyropaths_account_url() {
    return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
}

// Process before the theme emits headers; rendering a shortcode must not log users in.
add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['hotlines_register_nonce'])) {
        return;
    }
    nocache_headers();
    $GLOBALS['pyropaths_registration_message'] = is_user_logged_in()
        ? hotlines_handle_existing_user_redemption() : hotlines_handle_new_user_registration();
});
add_shortcode('pyropaths_register', 'hotlines_render_register_form');

function pyropaths_product_plan($product_id) {
    if (!class_exists('Groups_Group') || !class_exists('Groups_User_Group') || !function_exists('wc_get_product')) {
        return new WP_Error('dependencies', 'WooCommerce and Groups must be active.');
    }
    $product = wc_get_product($product_id);
    $tier = get_post_meta($product_id, '_pyropaths_tier', true);
    $group = (int) get_post_meta($product_id, '_pyropaths_group', true);
    $resource = (int) get_post_meta($product_id, '_pyropaths_resource', true);
    $tiers = hotlines_group_id_map();
    if (!$product || !in_array($tier, ['yellow', 'orange', 'red'], true)
        || !$group || in_array($group, [$tiers['yellow'], $tiers['orange'], $tiers['red'], 1], true)
        || !Groups_Group::read($group) || get_post_type($resource) !== 'resource'
        || get_post_status($resource) !== 'publish') {
        return new WP_Error('product_mapping', 'This product needs a tutorial tier, a separate product group, and a published template resource. Please contact the site administrator.');
    }
    $shared = get_posts(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'private', 'pending'],
        'numberposts' => 1, 'post__not_in' => [(int) $product_id], 'fields' => 'ids',
        'meta_key' => '_pyropaths_group', 'meta_value' => $group]);
    if ($shared) {
        return new WP_Error('shared_group', 'Each product must use its own product group.');
    }
    // Page Read settings implement cumulative access; grant only the selected tier.
    if (!Groups_Group::read($tiers[$tier])) {
        return new WP_Error('tier_missing', 'The tutorial group is unavailable.');
    }
    return ['product' => (int) $product_id, 'resource' => $resource, 'groups' => [$tiers[$tier], $group]];
}

function pyropaths_code_plan($code_id) {
    if (!function_exists('get_field') || !class_exists('Groups_User_Group') || !class_exists('Groups_Group')) {
        return new WP_Error('dependencies', 'ACF and Groups must be active.');
    }
    $product = (int) get_post_meta($code_id, '_pyropaths_product', true);
    if ($product) {
        return pyropaths_product_plan($product);
    }
    // Existing codes retain their explicit grants until an admin associates a product.
    $values = get_field(HOTLINES_FIELD_GROUPS_TO_GRANT, $code_id);
    if (!is_array($values) || !$values) {
        return new WP_Error('no_groups', 'No access has been configured for this code.');
    }
    $map = hotlines_group_id_map();
    $groups = [];
    foreach ($values as $value) {
        if (!is_string($value) || !isset($map[$value]) || !Groups_Group::read($map[$value])) {
            return new WP_Error('invalid_group', 'A group on this code is unavailable. Please contact the site administrator.');
        }
        $groups[] = $map[$value];
    }
    return ['product' => 0, 'resource' => 0, 'groups' => array_unique($groups)];
}

/** Fail closed if atomic rollback is not supported by any participating table. */
function pyropaths_transaction_ready() {
    global $wpdb;
    $tables = [$wpdb->posts, $wpdb->postmeta, $wpdb->usermeta, $wpdb->prefix . 'groups_user_group'];
    foreach ($tables as $table) {
        $engine = $wpdb->get_var($wpdb->prepare(
            'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table
        ));
        if (strtoupper((string) $engine) !== 'INNODB') {
            return false;
        }
    }
    return true;
}

function pyropaths_lock($purpose) {
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', 'pyropaths_' . md5($wpdb->prefix . $purpose))) === 1;
}
function pyropaths_unlock($purpose) {
    global $wpdb;
    $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', 'pyropaths_' . md5($wpdb->prefix . $purpose)));
}

function pyropaths_redeem($user_id, $code_id) {
    global $wpdb;
    if (!$user_id || !get_userdata($user_id)) {
        return new WP_Error('user_missing', 'Please log in before registering a product.');
    }
    // Serialize grants, including overlapping product grants by the same account.
    if (!pyropaths_lock('redemption')) {
        return new WP_Error('busy', 'Registration is busy. Please try again.');
    }
    $started = false;
    try {
        clean_post_cache($code_id);
        if (get_post_type($code_id) !== HOTLINES_ACCESS_CODE_POST_TYPE || get_post_status($code_id) !== 'publish'
            || get_post_meta($code_id, HOTLINES_FIELD_STATUS, true) !== HOTLINES_STATUS_AVAILABLE) {
            return new WP_Error('used', 'That code is unavailable or has already been redeemed.');
        }
        $plan = pyropaths_code_plan($code_id);
        if (is_wp_error($plan)) {
            return $plan;
        }
        if (!pyropaths_transaction_ready() || $wpdb->query('START TRANSACTION') === false) {
            return new WP_Error('storage', 'Registration storage is unavailable. Please contact the site administrator.');
        }
        $started = true;
        foreach ($plan['groups'] as $group_id) {
            if (!Groups_User_Group::read($user_id, $group_id)
                && !Groups_User_Group::create(['user_id' => $user_id, 'group_id' => $group_id])) {
                throw new RuntimeException('Group grant failed');
            }
        }
        $fields = [
            HOTLINES_FIELD_STATUS => HOTLINES_STATUS_REDEEMED,
            HOTLINES_FIELD_REDEEMED_BY => (string) $user_id,
            HOTLINES_FIELD_REDEEMED_AT => current_time('Ymd'),
        ];
        $field_keys = [
            'status' => 'field_6a316afc67903',
            'redeemed_by' => 'field_6a316b8a67905',
            'redeemed_at' => 'field_6a316bac67906',
        ];
        foreach ($fields as $key => $value) {
            update_field($field_keys[$key], $value, $code_id);
            // Read DB directly: do not trust ACF/object caches after a failed write.
            $stored = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $code_id, $key));
            if ((string) $stored !== (string) $value) {
                throw new RuntimeException('Redemption record failed');
            }
        }
        if ($wpdb->query('COMMIT') === false) {
            throw new RuntimeException('Commit failed');
        }
        $started = false;
        return true;
    } catch (Throwable $error) {
        return new WP_Error('redemption_failed', 'Access could not be saved. Your code has not been consumed. Please try again or contact support.');
    } finally {
        if ($started) {
            $wpdb->query('ROLLBACK');
        }
        clean_post_cache($code_id);
        clean_user_cache($user_id);
        wp_cache_delete($user_id, 'user_meta');
        if (class_exists('Groups_User')) {
            // Groups caches membership-derived capabilities within the request/cache.
            Groups_User::clear_cache($user_id);
        }
        pyropaths_unlock('redemption');
    }
}

/** Library uses product Groups membership, so manual grants and revocations apply. */
function pyropaths_library() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">log in</a> to view your templates.</p>';
    }
    if (!class_exists('Groups_User_Group') || !function_exists('wc_get_products')) {
        return '<p>Your templates are temporarily unavailable.</p>';
    }
    $resources = [];
    $products = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1,
        'meta_key' => '_pyropaths_group', 'fields' => 'ids']);
    foreach ($products as $product_id) {
        $group = (int) get_post_meta($product_id, '_pyropaths_group', true);
        $resource = (int) get_post_meta($product_id, '_pyropaths_resource', true);
        if ($group && Groups_User_Group::read(get_current_user_id(), $group)
            && get_post_type($resource) === 'resource' && get_post_status($resource) === 'publish') {
            $resources[$resource] = '<li><a href="' . esc_url(get_permalink($resource)) . '">' . esc_html(get_the_title($resource)) . '</a></li>';
        }
    }
    return $resources ? '<ul class="pyropaths-library">' . implode('', $resources) . '</ul>' : '<p>Register a product to see its templates here.</p>';
}
add_shortcode('pyropaths_library', 'pyropaths_library');
add_action('woocommerce_account_dashboard', function () {
    echo '<h2>Register a product</h2>';
    echo hotlines_render_register_form(); // All messages and fields escaped by renderer.
});
