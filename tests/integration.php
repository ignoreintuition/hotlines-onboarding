<?php
/** Run through wp eval-file on the isolated fixture site, never production. */
if (strpos(home_url(), 'localhost:8097') === false) {
    throw new RuntimeException('Run only on the isolated test site.');
}
function verify($condition, $message) {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    echo 'PASS: ' . $message . "\n";
}
function test_product($name, $tier, $group, $resource) {
    $product = new WC_Product_Simple();
    $product->set_name($name);
    $product->set_status('publish');
    $id = $product->save();
    update_post_meta($id, '_pyropaths_tier', $tier);
    update_post_meta($id, '_pyropaths_group', $group);
    update_post_meta($id, '_pyropaths_resource', $resource);
    return $id;
}
function batch_codes($batch) {
    return get_posts(['post_type' => 'access-codes', 'numberposts' => -1, 'meta_key' => 'batch', 'meta_value' => $batch]);
}
// Match supplied live IDs in this disposable database.
foreach (['Yellow Tier', 'Orange Tier', 'Red Tier', 'Mushroom Kit', 'Frog Kit'] as $name) {
    if (!Groups_Group::read_by_name($name)) {
        Groups_Group::create(['name' => $name]);
    }
}
verify((int) Groups_Group::read_by_name('Yellow Tier')->group_id === 2, 'fixture tier IDs match site');
$run = bin2hex(random_bytes(4));
$mushroom = wp_insert_post(['post_type' => 'resource', 'post_status' => 'publish', 'post_title' => 'Mushroom template ' . $run]);
$frog = wp_insert_post(['post_type' => 'resource', 'post_status' => 'publish', 'post_title' => 'Frog template ' . $run]);
// Previous fixture products must not share groups with this run.
foreach (get_posts(['post_type' => 'product', 'numberposts' => -1]) as $old) {
    wp_delete_post($old->ID, true);
}
$p1 = test_product('Mushroom ' . $run, 'orange', 5, $mushroom);
$p2 = test_product('Frog ' . $run, 'red', 6, $frog);
$u1 = wp_create_user('owner-' . $run, wp_generate_password(), 'owner-' . $run . '@example.test');
$u2 = wp_create_user('other-' . $run, wp_generate_password(), 'other-' . $run . '@example.test');
verify(!is_wp_error($u1) && !is_wp_error($u2), 'test accounts created');
verify(pyropaths_transaction_ready(), 'participating tables support rollback');
verify(is_wp_error(pyropaths_generate_codes($p1, 0, 'bad')), 'invalid batch quantities rejected');
$batch = pyropaths_generate_codes($p1, 3, 'mushroom-test');
verify(!is_wp_error($batch), 'batch generation succeeds');
$codes = batch_codes($batch);
verify(count($codes) === 3, 'batch contains requested quantity');
$strings = array_map(function ($code) {return get_post_meta($code->ID, 'access_code', true);}, $codes);
verify(count(array_unique($strings)) === 3 && preg_match('/^[a-f0-9]{4}(-[a-f0-9]{4}){3}$/', $strings[0]), 'codes unique and label-friendly');
verify(get_field('status', $codes[0]->ID) === 'Available', 'ACF reads generated code status');
$_POST['access_code'] = strtoupper($strings[0]);
verify(!is_wp_error(hotlines_validate_access_code_from_request()), 'uppercase code input accepted');
verify(pyropaths_redeem($u1, $codes[0]->ID) === true, 'first redemption succeeds');
verify((bool) Groups_User_Group::read($u1, 3) && (bool) Groups_User_Group::read($u1, 5), 'tier and product group granted');
verify(!Groups_User_Group::read($u1, 4) && !Groups_User_Group::read($u1, 6), 'unrelated tier/product groups excluded');
verify(get_post_meta($codes[0]->ID, 'redeemed_at', true) === current_time('Ymd'), 'redemption date matches ACF storage');
verify((int) get_field('redeemed_by', $codes[0]->ID)['ID'] === $u1, 'ACF formats redeemed user field');
verify(is_wp_error(pyropaths_redeem($u2, $codes[0]->ID)), 'used code rejected for another account');
verify(is_wp_error(pyropaths_redeem($u1, $codes[0]->ID)), 'used code rejected for same account');
verify(pyropaths_redeem($u1, $codes[1]->ID) === true, 'second code for same product accepted');
wp_set_current_user($u1);
$library = pyropaths_library();
verify(strpos($library, 'Mushroom template') !== false && strpos($library, 'Frog template') === false, 'Library shows owned templates only');
$single = pyropaths_generate_codes($p2, 1, 'single-test');
verify(!is_wp_error($single) && count(batch_codes($single)) === 1, 'single-code generation supported');
$single_code = batch_codes($single)[0];
verify(pyropaths_redeem($u1, $single_code->ID) === true, 'another product adds access');
verify(strpos(pyropaths_library(), 'Frog template') !== false && strpos(pyropaths_library(), 'Mushroom template') !== false, 'multiple product templates retained');
Groups_User_Group::delete($u1, 5);
verify(strpos(pyropaths_library(), 'Mushroom template') === false, 'manual product-group revocation updates Library');
wp_set_current_user(0);
verify(strpos(pyropaths_library(), 'log in') !== false, 'anonymous Library requires login');
verify(get_post_type_object('access-codes')->show_in_rest === false, 'access-code REST route disabled');
verify(pyropaths_csv_cell('=1+1') === "'=1+1" && pyropaths_csv_cell('Frog') === 'Frog', 'CSV formula injection escaped');
verify(pyropaths_account_url() === wc_get_page_permalink('myaccount'), 'registration destination is WooCommerce My Account');

// Inject a real database-write failure AFTER memberships have been added.
$fail = function ($check, $object_id, $key) use ($codes) {
    return $object_id === $codes[2]->ID && $key === 'status' ? false : $check;
};
add_filter('update_post_metadata', $fail, 10, 3);
verify(is_wp_error(pyropaths_redeem($u2, $codes[2]->ID)), 'failed redemption record reports an error');
remove_filter('update_post_metadata', $fail, 10);
verify(!Groups_User_Group::read($u2, 3) && !Groups_User_Group::read($u2, 5), 'failed redemption rolls back membership grants');
verify(get_post_meta($codes[2]->ID, 'status', true) === 'Available', 'failed redemption leaves code available');
verify(pyropaths_redeem($u2, $codes[2]->ID) === true, 'retry after failure succeeds');

// Invalid legacy choices must be validated before any grants.
$legacy = wp_insert_post(['post_type' => 'access-codes', 'post_status' => 'publish']);
update_field('field_6a316afc67903', 'Available', $legacy);
update_field('field_6a316b4a67904', ['yellow', 'unknown'], $legacy);
verify(is_wp_error(pyropaths_redeem($u2, $legacy)) && !Groups_User_Group::read($u2, 2), 'invalid legacy selection cannot partially grant access');
update_field('field_6a316b4a67904', ['yellow', 'mushroom_kit'], $legacy);
verify(pyropaths_redeem($u2, $legacy) === true, 'legacy Mushroom selection supported');

global $wpdb;
// A partially generated batch must not survive a failed metadata write.
$before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='access-codes'");
$fail_generation = function ($check, $id, $key) {
    return $key === 'access_code' ? false : $check;
};
add_filter('add_post_metadata', $fail_generation, 10, 3);
verify(is_wp_error(pyropaths_generate_codes($p1, 2, 'failed-batch')), 'generation reports failed code storage');
remove_filter('add_post_metadata', $fail_generation, 10);
$after = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='access-codes'");
verify($before === $after, 'failed generation leaves no partial codes');

// A broken mapping must not consume a valid code.
update_post_meta($p1, '_pyropaths_resource', 0);
$broken_batch = pyropaths_generate_codes($p1, 1, 'broken-map');
verify(is_wp_error($broken_batch), 'generation rejects incomplete product mappings');
update_post_meta($p1, '_pyropaths_resource', $mushroom);

// New account creation is cleaned up if the later redemption save fails.
$account_batch = pyropaths_generate_codes($p1, 1, 'account-failure');
$account_code = batch_codes($account_batch)[0];
$email = 'failed-account-' . $run . '@example.test';
$_POST = ['hotlines_register_nonce' => wp_create_nonce('hotlines_register_action'), 'first_name' => 'Test',
    'last_name' => 'Failure', 'email' => $email, 'password' => 'local-test-only',
    'access_code' => get_post_meta($account_code->ID, 'access_code', true)];
$fail_account = function ($check, $id, $key) use ($account_code) {
    return $id === $account_code->ID && $key === 'status' ? false : $check;
};
add_filter('update_post_metadata', $fail_account, 10, 3);
$message = hotlines_handle_new_user_registration();
remove_filter('update_post_metadata', $fail_account, 10);
verify(strpos($message, 'could not be saved') !== false && !email_exists($email), 'failed initial redemption cleans up the new account');
verify(get_post_meta($account_code->ID, 'status', true) === 'Available', 'failed account registration preserves its code');
$_POST = [];

// Save fixtures for HTTP/concurrency checks. No real accounts or codes involved.
$race_batch = pyropaths_generate_codes($p2, 1, 'race-test');
$race = batch_codes($race_batch)[0];
update_option('pyropaths_test_race', ['code_id' => $race->ID, 'users' => [$u1, $u2]]);
$registration_batch = pyropaths_generate_codes($p1, 1, 'registration-test');
update_option('pyropaths_test_registration', get_post_meta(batch_codes($registration_batch)[0]->ID, 'access_code', true));
echo "Integration checks complete.\n";
