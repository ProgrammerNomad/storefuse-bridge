<?php
/** CLI only: php scripts/local-fixtures.php PATH_TO_WORDPRESS PRIVATE_OUTPUT_DIR */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) || empty( $argv[2] ) ) {
    exit( "Usage: php local-fixtures.php wordpress-path private-output-directory\n" );
}
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), [ 'localhost', '127.0.0.1' ], true ) ) {
    exit( "Fixtures are restricted to a loopback store.\n" );
}
$out = $argv[2];
if ( ! is_dir( $out ) ) {
    mkdir( $out, 0700, true );
}
// Keep database credentials out of command lines and tool output.
$ini = tempnam( sys_get_temp_dir(), 'sfb-db-' );
$escape = static fn( $v ) => '"' . addcslashes( $v, "\\\"\n\r" ) . '"';
file_put_contents( $ini, "[client]\nhost=" . $escape( DB_HOST ) . "\nuser=" . $escape( DB_USER ) . "\npassword=" . $escape( DB_PASSWORD ) . "\n" );
try {
    $dump = PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump';
    $args = [ $dump, '--defaults-extra-file=' . $ini, '--single-transaction', '--result-file=' . $out . '/before-fixtures.sql', DB_NAME ];
    $process = proc_open( $args, [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
    fclose( $pipes[0] );
    stream_get_contents( $pipes[1] );
    stream_get_contents( $pipes[2] );
    fclose( $pipes[1] );
    fclose( $pipes[2] );
    if ( proc_close( $process ) !== 0 ) {
        exit( "Database backup failed; no fixtures were created.\n" );
    }
} finally {
    unlink( $ini );
}
// Suppress all fixture-triggered email; this is never a delivery test.
add_filter( 'pre_wp_mail', '__return_true' );
$accounts = [];
foreach ( [ 'a', 'b' ] as $suffix ) {
    $email = 'storefuse.audit.' . $suffix . '@example.test';
    $user = get_user_by( 'email', $email );
    $password = wp_generate_password( 28, false );
    if ( $user ) {
        wp_set_password( $password, $user->ID );
        $id = $user->ID;
    } else {
        $id = wc_create_new_customer( $email, 'storefuse_audit_' . $suffix, $password );
    }
    if ( is_wp_error( $id ) ) {
        exit( "Could not create test account.\n" );
    }
    $accounts[] = [ 'id' => $id, 'email' => $email, 'password' => $password ];
}
$products = [];
foreach ( [ 'simple', 'sale', 'virtual', 'outofstock' ] as $kind ) {
    $sku = 'sfb-audit-' . $kind;
    $id = wc_get_product_id_by_sku( $sku );
    $product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
    $product->set_name( 'StoreFuse Test ' . ucfirst( $kind ) );
    $product->set_slug( $sku );
    $product->set_sku( $sku );
    $product->set_status( 'publish' );
    $product->set_description( 'Local acceptance-test product. Not real inventory.' );
    $product->set_regular_price( '29.00' );
    $product->set_sale_price( $kind === 'sale' ? '19.00' : '' );
    $product->set_virtual( $kind === 'virtual' );
    $product->set_manage_stock( true );
    $product->set_stock_quantity( $kind === 'outofstock' ? 0 : 100 );
    $product->set_stock_status( $kind === 'outofstock' ? 'outofstock' : 'instock' );
    $products[ $kind ] = $product->save();
}
$variable_sku = 'sfb-audit-variable';
$variable_id = wc_get_product_id_by_sku( $variable_sku );
if ( ! $variable_id ) {
    $variable = new WC_Product_Variable();
    $variable->set_name( 'StoreFuse Test Variable' );
    $variable->set_slug( $variable_sku );
    $variable->set_sku( $variable_sku );
    $variable->set_status( 'publish' );
    $variable->set_description( 'Variable product for variation acceptance tests.' );
    $attr = new WC_Product_Attribute();
    $attr->set_name( 'Size' );
    $attr->set_options( [ 'Small', 'Large' ] );
    $attr->set_visible( true );
    $attr->set_variation( true );
    $variable->set_attributes( [ $attr ] );
    $variable_id = $variable->save();
    foreach ( [ 'Small' => '24.00', 'Large' => '28.00' ] as $size => $price ) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id( $variable_id );
        $variation->set_regular_price( $price );
        $variation->set_attributes( [ 'size' => $size ] );
        $variation->set_stock_quantity( 50 );
        $variation->set_manage_stock( true );
        $variation->save();
    }
} else {
    $variable_id = (int) $variable_id;
}
$products['variable'] = $variable_id;
$zone_id = (int) get_option( 'sfb_audit_zone_id', 0 );
$zone = new WC_Shipping_Zone( $zone_id );
$zone->set_zone_name( 'StoreFuse local test delivery' );
$zone->set_zone_order( 0 );
$zone->set_zone_locations( [ [ 'code' => 'IN', 'type' => 'country' ] ] );
$zone_id = $zone->save();
update_option( 'sfb_audit_zone_id', $zone_id, false );
$methods = $zone->get_shipping_methods();
if ( ! $methods ) {
    $instance_id = $zone->add_shipping_method( 'flat_rate' );
    update_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', [ 'title' => 'Test delivery', 'cost' => '5', 'tax_status' => 'taxable' ] );
}
update_option( 'woocommerce_cod_settings', [
	'enabled'            => 'yes',
	'title'                => 'Cash on delivery (local test)',
	'enable_for_virtual'   => 'yes',
	'enable_for_methods'   => [],
] );
update_option( 'woocommerce_bacs_settings', [
	'enabled'     => 'yes',
	'title'       => 'Direct bank transfer (local test)',
	'description' => 'Offline test gateway for headless checkout. No real payment.',
] );
update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
update_option( 'woocommerce_calc_taxes', 'no' );
wc_delete_product_transients();
StoreFuse_Bridge_Cache::flush_all();

$hints = [
	'store_url'        => home_url(),
	'status_endpoint'  => rest_url( 'storefuse/v1/status' ),
	'products'         => $products,
	'product_skus'     => array_map(
		static fn( $kind ) => 'sfb-audit-' . $kind,
		array_keys( $products )
	),
	'accounts'         => array_map(
		static fn( $row ) => [ 'id' => $row['id'], 'email' => $row['email'] ],
		$accounts
	),
	'shipping_zone'    => [
		'id'        => $zone_id,
		'name'      => 'StoreFuse local test delivery',
		'locations' => [ 'IN (country)' ],
		'method'    => 'flat_rate - Test delivery (5.00)',
	],
	'payment_gateways' => [
		'cod'  => 'Cash on delivery (local test) - enabled for redirect and headless smoke',
		'bacs' => 'Direct bank transfer (local test) - optional headless offline gateway',
	],
	'mail'             => 'All wp_mail suppressed during fixture run; use a local mail sink for checkout emails if you test delivery.',
	'next_steps'       => [
		'Sync Bridge: ./scripts/deploy-local.ps1 -WordPressPath ' . $argv[1],
		'Record baseline: curl -sS ' . rest_url( 'storefuse/v1/status' ),
		'Keep fixture-credentials.json private (passwords); do not commit.',
	],
];

file_put_contents(
	$out . '/fixture-credentials.json',
	wp_json_encode(
		[
			'accounts' => $accounts,
			'products' => $products,
			'hints'    => $hints,
		],
		JSON_PRETTY_PRINT
	)
);
file_put_contents( $out . '/fixture-hints.json', wp_json_encode( $hints, JSON_PRETTY_PRINT ) );

echo "Database backed up; disposable SKUs sfb-audit-* refreshed; two customers; IN shipping zone; COD + BACS enabled.\n";
echo "Credentials: {$out}/fixture-credentials.json\n";
echo "Hints (no passwords): {$out}/fixture-hints.json\n";
echo "Record Bridge build_id from GET /status after deploy-local.ps1.\n";
