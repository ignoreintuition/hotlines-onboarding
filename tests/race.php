<?php
require '/var/www/html/wp-load.php';
if (strpos(home_url(), 'localhost:8097') === false) {
    exit(1);
}
$fixture = get_option('pyropaths_test_race');
// Keep the winner in the grant path long enough for the other process to contend.
add_filter('get_post_metadata', function ($check, $id, $key) {
    if ($key === 'status') {usleep(500000);}
    return $check;
}, 10, 3);
$result = pyropaths_redeem($fixture['users'][(int) $argv[1]], $fixture['code_id']);
echo is_wp_error($result) ? 'rejected' : 'redeemed';
