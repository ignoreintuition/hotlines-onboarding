<?php
/** Isolated test-site schema only, never packaged. */
add_action('init', function () {
    register_post_type('access-codes', ['public' => true, 'show_ui' => true, 'show_in_rest' => true, 'supports' => ['title']]);
    register_post_type('resource', ['public' => true, 'show_ui' => true, 'supports' => ['title', 'editor']]);
});
add_action('acf/init', function () {
    $groups = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hotlines-onboarding/docs/site-config/acf-export-2026-10-17.json'), true);
    foreach ($groups as $group) {
        acf_add_local_field_group($group);
    }
});
