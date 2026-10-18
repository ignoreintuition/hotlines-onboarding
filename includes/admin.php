<?php
/** Configuration and fulfillment tools. */
defined('ABSPATH') || exit;

function pyropaths_select($name, $label, $options, $value) {
    echo '<p><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label><br><select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
    foreach ($options as $id => $text) {
        echo '<option value="' . esc_attr($id) . '" ' . selected((string) $value, (string) $id, false) . '>' . esc_html($text) . '</option>';
    }
    echo '</select></p>';
}
function pyropaths_posts_options($type) {
    $options = ['' => '— Select —'];
    foreach (get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']) as $post) {
        $options[$post->ID] = $post->post_title . ' (#' . $post->ID . ')';
    }
    return $options;
}

// A standard WordPress metabox also works alongside the classic WooCommerce editor.
add_action('add_meta_boxes', function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    add_meta_box('pyropaths-product', 'PyroPaths Access', 'pyropaths_product_box', 'product', 'normal');
    add_meta_box('pyropaths-code', 'PyroPaths Product', 'pyropaths_code_box', HOTLINES_ACCESS_CODE_POST_TYPE, 'side');
});
function pyropaths_product_box($post) {
    wp_nonce_field('pyropaths_product', 'pyropaths_product_nonce');
    echo '<p>Choose an existing product-specific group. Create groups in Groups administration. Restrict its resource to the same group using Groups Read.</p>';
    pyropaths_select('_pyropaths_tier', 'Tutorial tier', ['' => '— Select —', 'yellow' => 'Yellow', 'orange' => 'Orange', 'red' => 'Red'], get_post_meta($post->ID, '_pyropaths_tier', true));
    $options = ['' => '— Select —'];
    if (class_exists('Groups_Group')) {
        $map = hotlines_group_id_map();
        foreach (Groups_Group::get_groups(['order_by' => 'name']) as $group) {
            if (!in_array((int) $group->group_id, [1, $map['yellow'], $map['orange'], $map['red']], true)) {
                $options[$group->group_id] = $group->name . ' (#' . $group->group_id . ')';
            }
        }
    }
    pyropaths_select('_pyropaths_group', 'Product group (not a tier group)', $options, get_post_meta($post->ID, '_pyropaths_group', true));
    pyropaths_select('_pyropaths_resource', 'Template resource', pyropaths_posts_options('resource'), get_post_meta($post->ID, '_pyropaths_resource', true));
    echo '<p>Use a different product group for each product. Group capabilities and resource restrictions remain administrator-managed.</p>';
}
function pyropaths_code_box($post) {
    wp_nonce_field('pyropaths_code', 'pyropaths_code_nonce');
    if (get_post_meta($post->ID, HOTLINES_FIELD_STATUS, true) === HOTLINES_STATUS_REDEEMED) {
        echo '<p>Redeemed codes cannot be reassigned here.</p>';
        return;
    }
    pyropaths_select('_pyropaths_product', 'Product', pyropaths_posts_options('product'), get_post_meta($post->ID, '_pyropaths_product', true));
    echo '<p>With a product selected, its access mapping replaces the legacy Groups to Grant checkboxes. Leave blank only for an existing legacy code.</p>';
}
add_action('save_post', function ($post_id) {
    if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (current_user_can('manage_options') && get_post_type($post_id) === 'product' && wp_verify_nonce(pyropaths_request('pyropaths_product_nonce'), 'pyropaths_product')) {
        $tier = pyropaths_request('_pyropaths_tier');
        $group = absint(pyropaths_request('_pyropaths_group'));
        $resource = absint(pyropaths_request('_pyropaths_resource'));
        $map = hotlines_group_id_map();
        if ($tier !== '' && !in_array($tier, ['yellow', 'orange', 'red'], true)) {
            return;
        }
        if ($group && (!class_exists('Groups_Group') || !Groups_Group::read($group)
            || in_array($group, [1, $map['yellow'], $map['orange'], $map['red']], true))) {
            return;
        }
        if ($resource && (get_post_type($resource) !== 'resource' || get_post_status($resource) !== 'publish')) {
            return;
        }
        update_post_meta($post_id, '_pyropaths_tier', $tier);
        update_post_meta($post_id, '_pyropaths_group', $group);
        update_post_meta($post_id, '_pyropaths_resource', $resource);
    }
    if (current_user_can('manage_options') && get_post_type($post_id) === HOTLINES_ACCESS_CODE_POST_TYPE
        && wp_verify_nonce(pyropaths_request('pyropaths_code_nonce'), 'pyropaths_code')
        && get_post_meta($post_id, HOTLINES_FIELD_STATUS, true) !== HOTLINES_STATUS_REDEEMED) {
        $product = absint(pyropaths_request('_pyropaths_product'));
        if (!$product || get_post_type($product) === 'product') {
            update_post_meta($post_id, '_pyropaths_product', $product);
        }
    }
});

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=access-codes', 'Generate PyroPaths Codes', 'Generate / Export', 'manage_options', 'pyropaths-codes', 'pyropaths_codes_page');
});
function pyropaths_codes_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap"><h1>PyroPaths Access Codes</h1>';
    $batch = isset($_GET['batch']) && is_string($_GET['batch']) ? sanitize_text_field(wp_unslash($_GET['batch'])) : '';
    if ($batch) {
        echo '<div class="notice notice-success"><p>Batch saved: ' . esc_html($batch) . '. Export it below for printing.</p></div>';
    }
    echo '<h2>Generate codes</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('pyropaths_generate');
    echo '<input type="hidden" name="action" value="pyropaths_generate">';
    pyropaths_select('product', 'Product', pyropaths_posts_options('product'), '');
    echo '<p><label>Quantity (1–500)<br><input type="number" name="quantity" min="1" max="500" value="1" required></label></p>';
    echo '<p><label>Batch label (optional; a unique suffix is added)<br><input type="text" name="batch" maxlength="80"></label></p>';
    submit_button('Generate codes');
    echo '</form><h2>Export saved batch</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('pyropaths_export');
    echo '<input type="hidden" name="action" value="pyropaths_export">';
    echo '<p><label>Batch identifier<br><input type="text" name="batch" value="' . esc_attr($batch) . '" required></label></p>';
    submit_button('Download CSV');
    echo '</form><h2>Saved batches</h2><ul>';
    global $wpdb;
    $batches = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key=%s AND p.post_type=%s ORDER BY m.meta_value DESC LIMIT 100", 'batch', HOTLINES_ACCESS_CODE_POST_TYPE));
    foreach ($batches as $saved) {
        $url = add_query_arg(['post_type' => 'access-codes', 'page' => 'pyropaths-codes', 'batch' => $saved], admin_url('edit.php'));
        echo '<li><a href="' . esc_url($url) . '">' . esc_html($saved) . '</a></li>';
    }
    echo '</ul></div>';
}

function pyropaths_generate_codes($product, $quantity, $label) {
    global $wpdb;
    if (!post_type_exists(HOTLINES_ACCESS_CODE_POST_TYPE) || !function_exists('update_field')) {
        return new WP_Error('dependencies', 'ACF and the access-codes post type must be active.');
    }
    if ($quantity < 1 || $quantity > 500) {
        return new WP_Error('quantity', 'Choose between 1 and 500 codes.');
    }
    $plan = pyropaths_product_plan($product);
    if (is_wp_error($plan)) {
        return $plan;
    }
    if (!pyropaths_lock('generation')) {
        return new WP_Error('busy', 'Code generation is busy. Please try again.');
    }
    $ids = [];
    $started = false;
    try {
        if (!pyropaths_transaction_ready() || $wpdb->query('START TRANSACTION') === false) {
            throw new RuntimeException('Storage unavailable: InnoDB tables are required.');
        }
        $started = true;
        $batch = (sanitize_title($label) ?: 'batch') . '-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
        for ($i = 0; $i < $quantity; $i++) {
            do {
                $code = implode('-', str_split(bin2hex(random_bytes(8)), 4));
            } while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", 'access_code', $code)));
            $id = wp_insert_post(['post_type' => HOTLINES_ACCESS_CODE_POST_TYPE, 'post_status' => 'publish',
                'post_title' => 'PyroPaths ' . $code], true);
            if (is_wp_error($id) || !$id) {
                throw new RuntimeException('Could not save a code.');
            }
            $ids[] = $id;
            // Use field keys for new ACF values to preserve field references.
            update_field('field_6a316ac93726b', $code, $id);
            update_field('field_6a316afc67903', HOTLINES_STATUS_AVAILABLE, $id);
            update_field('field_6a316bd667907', $batch, $id);
            update_post_meta($id, '_pyropaths_product', (int) $product);
            foreach (['access_code' => $code, 'status' => HOTLINES_STATUS_AVAILABLE, 'batch' => $batch, '_pyropaths_product' => (string) $product] as $key => $value) {
                if ((string) get_post_meta($id, $key, true) !== (string) $value) {
                    throw new RuntimeException('Could not save code configuration.');
                }
            }
        }
        if ($wpdb->query('COMMIT') === false) {
            throw new RuntimeException('Could not commit generated codes.');
        }
        $started = false;
        return $batch;
    } catch (Throwable $error) {
        return new WP_Error('generation_failed', 'No batch was saved. Check product configuration and database storage, then try again.');
    } finally {
        if ($started) {
            $wpdb->query('ROLLBACK');
        }
        foreach ($ids as $id) {
            clean_post_cache($id);
        }
        pyropaths_unlock('generation');
    }
}
add_action('admin_post_pyropaths_generate', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You are not permitted to generate access codes.', '', ['response' => 403]);
    }
    check_admin_referer('pyropaths_generate');
    $result = pyropaths_generate_codes(absint(pyropaths_request('product')), absint(pyropaths_request('quantity')), sanitize_text_field(pyropaths_request('batch')));
    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()));
    }
    wp_safe_redirect(add_query_arg(['post_type' => 'access-codes', 'page' => 'pyropaths-codes', 'batch' => $result], admin_url('edit.php')));
    exit;
});

function pyropaths_csv_cell($value) {
    $value = (string) $value;
    return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'" . $value : $value;
}
add_action('admin_post_pyropaths_export', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You are not permitted to export access codes.', '', ['response' => 403]);
    }
    check_admin_referer('pyropaths_export');
    $batch = sanitize_text_field(pyropaths_request('batch'));
    if (!$batch) {
        wp_die('Choose a batch.');
    }
    $codes = get_posts(['post_type' => HOTLINES_ACCESS_CODE_POST_TYPE, 'post_status' => 'publish', 'numberposts' => -1,
        'meta_key' => 'batch', 'meta_value' => $batch, 'orderby' => 'ID', 'order' => 'ASC']);
    if (!$codes) {
        wp_die('No codes were found for this batch.');
    }
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pyropaths-' . sanitize_file_name($batch) . '.csv"');
    $stream = fopen('php://output', 'w');
    fwrite($stream, "\xEF\xBB\xBF");
    fputcsv($stream, ['code', 'product_id', 'product', 'tier', 'batch', 'status'], ',', '"', '');
    foreach ($codes as $code) {
        $product = (int) get_post_meta($code->ID, '_pyropaths_product', true);
        $row = [get_post_meta($code->ID, 'access_code', true), $product, get_the_title($product),
            get_post_meta($product, '_pyropaths_tier', true), $batch, get_post_meta($code->ID, 'status', true)];
        fputcsv($stream, array_map('pyropaths_csv_cell', $row), ',', '"', '');
    }
    fclose($stream);
    exit;
});

// Access codes must never be exposed through public REST or front-end queries.
add_filter('register_post_type_args', function ($args, $type) {
    if ($type === HOTLINES_ACCESS_CODE_POST_TYPE) {
        $args['public'] = false;
        $args['publicly_queryable'] = false;
        $args['exclude_from_search'] = true;
        $args['show_in_rest'] = false;
    }
    return $args;
}, 20, 2);
add_action('admin_notices', function () {
    if (current_user_can('manage_options') && (!function_exists('get_field') || !function_exists('wc_get_product')
        || !class_exists('Groups_User_Group') || !post_type_exists(HOTLINES_ACCESS_CODE_POST_TYPE))) {
        echo '<div class="notice notice-error"><p>PyroPaths Onboarding requires WooCommerce, ACF, Groups, and the access-codes custom post type.</p></div>';
    }
});

add_filter('manage_access-codes_posts_columns', function ($columns) {
    $columns['pyropaths_product'] = 'Product';
    $columns['pyropaths_batch'] = 'Batch';
    return $columns;
});
add_action('manage_access-codes_posts_custom_column', function ($column, $id) {
    if ($column === 'pyropaths_product') {
        $product = (int) get_post_meta($id, '_pyropaths_product', true);
        echo $product ? esc_html(get_the_title($product)) : 'Legacy grants';
    } elseif ($column === 'pyropaths_batch') {
        echo esc_html(get_post_meta($id, 'batch', true));
    }
}, 10, 2);
