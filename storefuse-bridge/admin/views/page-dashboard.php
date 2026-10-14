<?php
defined( 'ABSPATH' ) || exit;

$settings     = StoreFuse_Bridge_Settings::all();
$modules      = StoreFuse_Bridge_Module_Status::get_module_map();
$labels       = StoreFuse_Bridge_Admin::module_labels();
$features     = StoreFuse_Bridge_WC_Compat::features();
$locations    = get_nav_menu_locations();
$last_flush   = get_option( 'storefuse_bridge_last_flush_at', [] );
$docs_url     = StoreFuse_Bridge_Admin::documentation_url();
$client       = $settings['primary_client'] ?? 'other';
$client_names = [ 'flutter' => __( 'Flutter app', 'storefuse-bridge' ), 'nextjs' => __( 'Next.js storefront', 'storefuse-bridge' ), 'other' => __( 'Other client', 'storefuse-bridge' ) ];
$client_name  = $client_names[ $client ] ?? $client_names['other'];

$product_count = wp_count_posts( 'product' );
$has_products  = is_object( $product_count ) && (int) ( $product_count->publish ?? 0 ) > 0;
$has_physical  = false;
if ( function_exists( 'wc_get_products' ) ) {
    $physical_products = wc_get_products( [ 'status' => 'publish', 'limit' => 1, 'virtual' => false, 'return' => 'ids' ] );
    $has_physical      = is_array( $physical_products ) && ! empty( $physical_products );
}

$enabled_gateways = [];
if ( function_exists( 'WC' ) && WC() && WC()->payment_gateways() ) {
    $gateways         = WC()->payment_gateways()->payment_gateways();
    $enabled_gateways = array_filter( $gateways, static fn( $gateway ) => isset( $gateway->enabled ) && 'yes' === $gateway->enabled );
}

$shipping_configured = false;
if ( class_exists( 'WC_Shipping_Zones' ) ) {
    foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
        foreach ( $zone['shipping_methods'] ?? [] as $method ) {
            if ( isset( $method->enabled ) && 'yes' === $method->enabled ) {
                $shipping_configured = true;
                break 2;
            }
        }
    }
    if ( ! $shipping_configured && is_callable( [ 'WC_Shipping_Zones', 'get_zone' ] ) ) {
        $default_zone = WC_Shipping_Zones::get_zone( 0 );
        if ( $default_zone && is_callable( [ $default_zone, 'get_shipping_methods' ] ) ) {
            foreach ( $default_zone->get_shipping_methods() as $method ) {
                if ( isset( $method->enabled ) && 'yes' === $method->enabled ) {
                    $shipping_configured = true;
                    break;
                }
            }
        }
    }
}

$origin_for = static function ( string $url ): string {
    $parts = wp_parse_url( $url );
    if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
        return '';
    }
    $port = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
    return strtolower( $parts['scheme'] . '://' . $parts['host'] . $port );
};
$api_origin       = $origin_for( get_site_url() );
$storefront_origin = $origin_for( (string) ( $settings['storefront_url'] ?? '' ) );
$cross_origin     = '' !== $api_origin && '' !== $storefront_origin && $api_origin !== $storefront_origin;
$cors_origins     = preg_split( '/\r\n|\r|\n/', (string) ( $settings['cors_allowed_origins'] ?? '' ) ) ?: [];
$cors_origins     = array_map( static fn( $origin ) => rtrim( trim( $origin ), '/' ), $cors_origins );
$cors_ready       = ! empty( $settings['cors_enabled'] ) && in_array( $storefront_origin, $cors_origins, true );

$readiness = [];
$add_readiness = static function ( string $label, string $requirement, string $state, string $detail, string $url = '' ) use ( &$readiness ): void {
    $readiness[] = compact( 'label', 'requirement', 'state', 'detail', 'url' );
};

$products_enabled = ! empty( $modules['products'] );
$add_readiness(
    __( 'Published products', 'storefuse-bridge' ),
    $products_enabled ? __( 'Required', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    ! $products_enabled ? __( 'Not applicable', 'storefuse-bridge' ) : ( $has_products ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ),
    $products_enabled ? __( 'Needed when launching a product catalog.', 'storefuse-bridge' ) : __( 'The Products module is disabled.', 'storefuse-bridge' ),
    admin_url( 'edit.php?post_type=product' )
);

$checkout_enabled = ! empty( $modules['checkout'] );
$add_readiness(
    __( 'Payment method', 'storefuse-bridge' ),
    $checkout_enabled ? __( 'Required', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    ! $checkout_enabled ? __( 'Not applicable', 'storefuse-bridge' ) : ( ! empty( $enabled_gateways ) ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ),
    $checkout_enabled ? __( 'Required before accepting online orders through checkout.', 'storefuse-bridge' ) : __( 'The Checkout module is disabled.', 'storefuse-bridge' ),
    admin_url( 'admin.php?page=wc-settings&tab=checkout' )
);

$add_readiness(
    __( 'Shipping methods', 'storefuse-bridge' ),
    $has_physical ? __( 'Required', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    ! $has_physical ? __( 'Not applicable', 'storefuse-bridge' ) : ( $shipping_configured ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ),
    $has_physical ? __( 'Applies because published shippable products are present.', 'storefuse-bridge' ) : __( 'No published shippable products were found.', 'storefuse-bridge' ),
    admin_url( 'admin.php?page=wc-settings&tab=shipping' )
);

$privacy_url = get_privacy_policy_url();
$add_readiness(
    __( 'Privacy policy', 'storefuse-bridge' ),
    __( 'Recommended', 'storefuse-bridge' ),
    $privacy_url ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ),
    __( 'Review the privacy information appropriate for your store and jurisdiction.', 'storefuse-bridge' ),
    admin_url( 'options-privacy.php' )
);

$webhooks_enabled = ! empty( $modules['webhooks'] );
$storefront_url   = trim( (string) ( $settings['storefront_url'] ?? '' ) );
$add_readiness(
    __( 'Webhook storefront URL', 'storefuse-bridge' ),
    $webhooks_enabled ? __( 'Required', 'storefuse-bridge' ) : __( 'Optional', 'storefuse-bridge' ),
    $webhooks_enabled ? ( '' !== $storefront_url ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ) : ( '' !== $storefront_url ? __( 'Ready', 'storefuse-bridge' ) : __( 'Not applicable', 'storefuse-bridge' ) ),
    $webhooks_enabled ? __( 'Used as the destination host when the Webhooks module is enabled.', 'storefuse-bridge' ) : __( 'Configure this only if you use outgoing storefront revalidation webhooks.', 'storefuse-bridge' ),
    admin_url( 'admin.php?page=storefuse-bridge-setup' )
);

$https_ready = is_ssl();
$add_readiness(
    __( 'HTTPS', 'storefuse-bridge' ),
    __( 'Recommended', 'storefuse-bridge' ),
    $https_ready ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ),
    __( 'Use HTTPS for production storefront and API traffic.', 'storefuse-bridge' )
);

$menus_ready = ! empty( $locations['storefuse-header'] ) && ! empty( $locations['storefuse-footer'] );
$add_readiness(
    __( 'Bridge navigation menus', 'storefuse-bridge' ),
    'nextjs' === $client ? __( 'Required', 'storefuse-bridge' ) : __( 'Optional', 'storefuse-bridge' ),
    'nextjs' === $client ? ( $menus_ready ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ) : ( $menus_ready ? __( 'Ready', 'storefuse-bridge' ) : __( 'Not applicable', 'storefuse-bridge' ) ),
    'nextjs' === $client ? __( 'Assign these locations if your browser storefront uses Bridge navigation.', 'storefuse-bridge' ) : __( 'Only needed when a browser storefront uses the Bridge header/footer menu locations.', 'storefuse-bridge' ),
    admin_url( 'nav-menus.php?action=locations' )
);

$cors_required = 'nextjs' === $client && $cross_origin;
$add_readiness(
    __( 'Cross-origin browser access', 'storefuse-bridge' ),
    $cors_required ? __( 'Required', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    $cors_required ? ( $cors_ready ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ) : __( 'Not applicable', 'storefuse-bridge' ),
    $cors_required ? __( 'The configured Next.js storefront and WordPress API use different origins.', 'storefuse-bridge' ) : __( 'CORS is only needed for browser requests from a different origin. Native Flutter apps do not use browser CORS; Flutter Web may need it.', 'storefuse-bridge' ),
    admin_url( 'admin.php?page=storefuse-bridge-api' )
);

$required_attention = array_filter( $readiness, static fn( $item ) => __( 'Required', 'storefuse-bridge' ) === $item['requirement'] && __( 'Needs attention', 'storefuse-bridge' ) === $item['state'] );
$api_base = get_site_url() . '/wp-json/storefuse/v1';
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Dashboard', 'storefuse-bridge' ); ?> <span class="sfb-version">v<?php echo esc_html( STOREFUSE_BRIDGE_VERSION ); ?></span></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <div class="sfb-dashboard-intro">
        <p><span class="sfb-client-badge"><?php echo esc_html( $client_name ); ?></span> <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-setup' ) ); ?>"><?php esc_html_e( 'Change client', 'storefuse-bridge' ); ?> →</a></p>
        <?php if ( empty( $required_attention ) ) : ?>
            <p class="sfb-readiness-summary"><?php esc_html_e( 'No applicable required setup checks need attention.', 'storefuse-bridge' ); ?></p>
        <?php else : ?>
            <p class="sfb-readiness-summary"><?php esc_html_e( 'Some required setup checks need attention before launch.', 'storefuse-bridge' ); ?></p>
        <?php endif; ?>
    </div>

    <nav class="sfb-setup-steps" aria-label="<?php esc_attr_e( 'Store setup', 'storefuse-bridge' ); ?>">
        <?php foreach ( [ 'setup' => __( 'Connect client', 'storefuse-bridge' ), 'branding' => __( 'Branding', 'storefuse-bridge' ), 'homepage' => __( 'Homepage', 'storefuse-bridge' ), 'checkout' => __( 'Checkout', 'storefuse-bridge' ), 'api' => __( 'API & CORS', 'storefuse-bridge' ) ] as $slug => $label ) : ?>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-' . $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="sfb-dashboard-grid">
        <section class="sfb-card sfb-card--status">
            <h2><?php esc_html_e( 'Store readiness', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Required checks depend on the configured store and frontend. Recommended and informational items never block readiness.', 'storefuse-bridge' ); ?></p>
            <div class="sfb-table-scroll">
                <table class="sfb-status-table widefat">
                    <thead><tr>
                        <th scope="col"><?php esc_html_e( 'Check', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Requirement', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Status', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'When it applies', 'storefuse-bridge' ); ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ( $readiness as $item ) :
                            $state_class = 'Ready' === $item['state'] ? 'on' : ( 'Needs attention' === $item['state'] ? 'attention' : 'neutral' );
                            ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $item['label'] ); ?></th>
                                <td><?php echo esc_html( $item['requirement'] ); ?></td>
                                <td><span class="sfb-badge sfb-badge--<?php echo esc_attr( $state_class ); ?>"><?php echo esc_html( $item['state'] ); ?></span></td>
                                <td>
                                    <?php echo esc_html( $item['detail'] ); ?>
                                    <?php if ( $item['url'] && 'Not applicable' !== $item['state'] ) : ?>
                                        <a href="<?php echo esc_url( $item['url'] ); ?>"><?php esc_html_e( 'Configure', 'storefuse-bridge' ); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sfb-card sfb-card--api">
            <h2><?php esc_html_e( 'API diagnostics', 'storefuse-bridge' ); ?></h2>
            <div class="sfb-api-url-row">
                <code id="sfb-api-url"><?php echo esc_html( $api_base ); ?></code>
                <button type="button" class="button sfb-copy-btn" data-target="sfb-api-url"><?php esc_html_e( 'Copy URL', 'storefuse-bridge' ); ?></button>
                <a href="<?php echo esc_url( $api_base . '/status' ); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e( 'Open /status', 'storefuse-bridge' ); ?></a>
            </div>
            <p><a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open documentation', 'storefuse-bridge' ); ?></a></p>
        </section>

        <section class="sfb-card sfb-card--cache">
            <h2><?php esc_html_e( 'Cache management', 'storefuse-bridge' ); ?></h2>
            <?php if ( ! empty( $last_flush['time'] ) ) : ?>
                <p class="description">
                    <?php
                    printf(
                        esc_html__( 'Last manual flush: %1$s (%2$s)', 'storefuse-bridge' ),
                        esc_html( $last_flush['time'] ),
                        esc_html( $last_flush['group'] ?? 'all' )
                    );
                    ?>
                </p>
            <?php endif; ?>
            <p><?php esc_html_e( 'Flush a single cache group when possible; use Flush all for a full content-cache reset.', 'storefuse-bridge' ); ?></p>
            <div class="sfb-cache-actions">
                <button type="button" id="sfb-flush-cache" class="button button-secondary" data-group="all"><?php esc_html_e( 'Flush all', 'storefuse-bridge' ); ?></button>
                <?php foreach ( [ 'products' => __( 'Products', 'storefuse-bridge' ), 'settings' => __( 'Settings', 'storefuse-bridge' ), 'navigation' => __( 'Navigation', 'storefuse-bridge' ), 'homepage' => __( 'Homepage', 'storefuse-bridge' ), 'search' => __( 'Search', 'storefuse-bridge' ) ] as $group => $label ) : ?>
                    <button type="button" class="button button-secondary sfb-flush-group" data-group="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $label ); ?></button>
                <?php endforeach; ?>
            </div>
            <p id="sfb-flush-result" class="sfb-inline-result" role="status" aria-live="polite"></p>
            <p id="sfb-flush-group-result" class="sfb-inline-result" role="status" aria-live="polite"></p>
        </section>

        <section class="sfb-card sfb-card--modules">
            <h2><?php esc_html_e( 'Module status', 'storefuse-bridge' ); ?></h2>
            <div class="sfb-table-scroll">
                <table class="sfb-modules-table widefat">
                    <thead><tr><th scope="col"><?php esc_html_e( 'Module', 'storefuse-bridge' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'storefuse-bridge' ); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ( $modules as $key => $enabled ) : ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $labels[ $key ] ?? ucfirst( $key ) ); ?></th>
                                <td><span class="sfb-badge sfb-badge--<?php echo $enabled ? 'on' : 'off'; ?>"><?php echo $enabled ? esc_html__( 'Enabled', 'storefuse-bridge' ) : esc_html__( 'Disabled', 'storefuse-bridge' ); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-modules' ) ); ?>"><?php esc_html_e( 'Manage modules', 'storefuse-bridge' ); ?></a></p>
        </section>

        <section class="sfb-card">
            <h2><?php esc_html_e( 'WooCommerce features', 'storefuse-bridge' ); ?></h2>
            <table class="widefat"><tbody>
                <?php
                $feature_labels = [ 'headless_checkout' => __( 'Headless checkout mode', 'storefuse-bridge' ), 'hpos' => __( 'WooCommerce HPOS', 'storefuse-bridge' ), 'store_api' => __( 'WooCommerce Store API', 'storefuse-bridge' ) ];
                foreach ( $feature_labels as $feature => $label ) :
                    $enabled = ! empty( $features[ $feature ] );
                    ?>
                    <tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><?php echo $enabled ? esc_html__( 'Available', 'storefuse-bridge' ) : esc_html__( 'Unavailable', 'storefuse-bridge' ); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
        </section>
    </div>
</div>
