<?php
if (strpos(home_url(), 'localhost:8097') === false) {throw new RuntimeException('Test site only');}
foreach (['registration' => '[hotlines_register]', 'library' => '[pyropaths_library]', 'my-account' => '[woocommerce_my_account]', 'learn' => 'Free videos for everyone'] as $slug => $content) {
    $page = get_page_by_path($slug);
    $id = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $slug, 'post_content' => $content]);
    if ($slug === 'my-account') {update_option('woocommerce_myaccount_page_id', $id);}
}
foreach (['yellow' => [2,3,4], 'orange' => [3,4], 'red' => [4]] as $tier => $groups) {
    $page = get_page_by_path('test-' . $tier);
    $id = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'test-' . $tier, 'post_title' => 'Test ' . $tier, 'post_content' => 'Exclusive ' . $tier . ' content']);
    foreach ($groups as $group) {Groups_Post_Access::create(['post_id' => $id, 'group_id' => $group]);}
}
echo "Test pages ready\n";
