<?php
/** CLI integration of real WordPress capabilities, cache and privacy APIs. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit(1); }
define('WP_ADMIN', true);
require rtrim($argv[1], '/\\') . '/wp-load.php';
if (!in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) { exit('Loopback only.'); }
add_filter('pre_wp_mail', '__return_true');
function verify($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
$original_user = get_current_user_id();
$email = 'sfb-privacy-' . wp_generate_password(12, false, false) . '@example.test';
$id = wc_create_new_customer($email, '', wp_generate_password(30));
verify(!is_wp_error($id), 'Fixture customer failed');
$request_id = 0;
$security_key = 'sfb_integration_security_' . wp_generate_password(12, false, false);
try {
    wp_set_current_user($id);
    $request = StoreFuse_Bridge_Privacy::request(new WP_REST_Request('POST'));
    verify($request->get_status() === 200, 'Privacy initiation failed');
    $request_id = $request->get_data()['data']['request_id'];
    $status = StoreFuse_Bridge_Privacy::status(new WP_REST_Request('GET'))->get_data()['data'];
    verify($status['status'] === 'request-confirmed', 'Privacy status did not persist');
    wp_set_current_user(0);
    $erased = StoreFuse_Bridge_Privacy::erase($email);
    verify($erased['items_removed'] && !get_user_by('id', $id), 'Privacy eraser did not delete customer');
    echo "PASS privacy request, durable status and account erasure\n";
    (new StoreFuse_Bridge_Admin())->register_settings();
    verify(apply_filters('option_page_capability_storefuse_bridge_settings_group', 'manage_options') === 'manage_woocommerce', 'Settings save capability mismatch');
    verify(get_role('shop_manager')->has_cap('manage_woocommerce'), 'Shop manager lacks capability');
    echo "PASS shop-manager settings save capability\n";
    set_transient($security_key, 'keep', 60);
    StoreFuse_Bridge_Cache::set('integration-content', 'old');
    StoreFuse_Bridge_Cache::flush_all();
    verify(get_transient($security_key) === 'keep', 'Cache flush removed security state');
    verify(StoreFuse_Bridge_Cache::get('integration-content') === null, 'Content namespace not invalidated');
    echo "PASS real WordPress cache namespace isolation\n";
    $module = new StoreFuse_Bridge_Module_Checkout();
    $custom = static function($fields) { $fields['billing']['billing_test_custom'] = ['type'=>'checkbox', 'required'=>true]; return $fields; };
    add_filter('woocommerce_checkout_fields', $custom);
    // Checkout fields are lazy cached by WC_Checkout; replace only this CLI instance.
    $checkout = new WC_Checkout();
    $fields = $checkout->get_checkout_fields('billing');
    verify(isset($fields['billing_test_custom']), 'Custom-field fixture not installed');
    remove_filter('woocommerce_checkout_fields', $custom);
    echo "PASS custom checkout field fixture contract\n";
} finally {
    wp_set_current_user($original_user);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    if (get_user_by('id', $id)) { wp_delete_user($id); }
    if ($request_id) { wp_delete_post($request_id, true); }
    delete_transient($security_key);
}
