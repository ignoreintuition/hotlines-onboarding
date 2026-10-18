<?php
require '/var/www/html/wp-load.php';
if (strpos(home_url(), 'localhost:8097') === false) {exit(1);}
$pages = [];
foreach (['registration', 'library', 'my-account', 'learn', 'test-yellow', 'test-orange', 'test-red'] as $slug) {
    $pages[$slug] = get_page_by_path($slug)->ID;
}
$tiers = [];
foreach (['yellow' => 2, 'orange' => 3, 'red' => 4] as $tier => $group) {
    $login = 'http-' . $tier;
    $user = get_user_by('login', $login);
    $id = $user ? $user->ID : wp_create_user($login, 'local-http-only-test', $login . '@example.test');
    if (!Groups_User_Group::read($id, $group)) {Groups_User_Group::create(['user_id' => $id, 'group_id' => $group]);}
    $tiers[$tier] = $login;
}
echo wp_json_encode(['pages' => $pages, 'tiers' => $tiers, 'registration_code' => get_option('pyropaths_test_registration')]);
