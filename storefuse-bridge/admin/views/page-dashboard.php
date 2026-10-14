<?php
defined( 'ABSPATH' ) || exit;

$settings     = StoreFuse_Bridge_Settings::all();
$modules      = StoreFuse_Bridge_Module_Status::get_module_map();
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
    ! $products_enabled ? __( 'The Products module is disabled.', 'storefuse-bridge' ) : ( $has_products ? __( 'Your published product catalog is available.', 'storefuse-bridge' ) : __( 'Publish at least one product to make your catalog available.', 'storefuse-bridge' ) ),
    admin_url( 'edit.php?post_type=product' )
);

$checkout_enabled = ! empty( $modules['checkout'] );
$add_readiness(
    __( 'Payment method', 'storefuse-bridge' ),
    $checkout_enabled ? __( 'Required', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    ! $checkout_enabled ? __( 'Not applicable', 'storefuse-bridge' ) : ( ! empty( $enabled_gateways ) ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ),
    ! $checkout_enabled ? __( 'The Checkout module is disabled.', 'storefuse-bridge' ) : ( ! empty( $enabled_gateways ) ? __( 'An enabled payment method is configured.', 'storefuse-bridge' ) : __( 'Enable a payment method before accepting online orders.', 'storefuse-bridge' ) ),
    admin_url( 'admin.php?page=wc-settings&tab=checkout' )
);

$add_readiness(
    __( 'Shipping methods', 'storefuse-bridge' ),
    $has_physical ? __( 'Required', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    ! $has_physical ? __( 'Not applicable', 'storefuse-bridge' ) : ( $shipping_configured ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ),
    ! $has_physical ? __( 'No published shippable products were found.', 'storefuse-bridge' ) : ( $shipping_configured ? __( 'At least one shipping method is enabled.', 'storefuse-bridge' ) : __( 'Enable a shipping method for physical products.', 'storefuse-bridge' ) ),
    admin_url( 'admin.php?page=wc-settings&tab=shipping' )
);

$privacy_url = get_privacy_policy_url();
$add_readiness(
    __( 'Privacy policy', 'storefuse-bridge' ),
    __( 'Recommended', 'storefuse-bridge' ),
    $privacy_url ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ),
    $privacy_url ? __( 'A privacy policy page is selected. Review its content for your store and jurisdiction.', 'storefuse-bridge' ) : __( 'Choose a privacy policy page in WordPress.', 'storefuse-bridge' ),
    admin_url( 'options-privacy.php' )
);

$webhooks_enabled = ! empty( $modules['webhooks'] );
$storefront_url   = trim( (string) ( $settings['storefront_url'] ?? '' ) );
$add_readiness(
    __( 'Webhook storefront URL', 'storefuse-bridge' ),
    $webhooks_enabled ? __( 'Required', 'storefuse-bridge' ) : __( 'Optional', 'storefuse-bridge' ),
    $webhooks_enabled ? ( '' !== $storefront_url ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ) ) : ( '' !== $storefront_url ? __( 'Ready', 'storefuse-bridge' ) : __( 'Not applicable', 'storefuse-bridge' ) ),
    $webhooks_enabled ? ( '' !== $storefront_url ? __( 'The destination URL is set for outgoing revalidation.', 'storefuse-bridge' ) : __( 'Set the storefront URL in Setup to use outgoing webhooks.', 'storefuse-bridge' ) ) : ( '' !== $storefront_url ? __( 'A storefront URL is saved; outgoing webhooks are optional.', 'storefuse-bridge' ) : __( 'Configure this only if you use outgoing storefront revalidation webhooks.', 'storefuse-bridge' ) ),
    admin_url( 'admin.php?page=storefuse-bridge-setup' )
);

$https_ready = is_ssl();
$add_readiness(
    __( 'HTTPS', 'storefuse-bridge' ),
    __( 'Recommended', 'storefuse-bridge' ),
    $https_ready ? __( 'Ready', 'storefuse-bridge' ) : __( 'Needs attention', 'storefuse-bridge' ),
    $https_ready ? __( 'WordPress is currently served over HTTPS.', 'storefuse-bridge' ) : __( 'Production stores should use HTTPS for API and storefront traffic.', 'storefuse-bridge' )
);

$menus_ready = ! empty( $locations['storefuse-header'] ) && ! empty( $locations['storefuse-footer'] );
$add_readiness(
    __( 'Bridge navigation menus', 'storefuse-bridge' ),
    'nextjs' === $client ? __( 'Optional', 'storefuse-bridge' ) : __( 'Informational', 'storefuse-bridge' ),
    $menus_ready ? __( 'Ready', 'storefuse-bridge' ) : ( 'nextjs' === $client ? __( 'Needs attention', 'storefuse-bridge' ) : __( 'Not applicable', 'storefuse-bridge' ) ),
    'nextjs' === $client ? __( 'Assign these locations if your storefront consumes Bridge navigation; custom navigation does not need them.', 'storefuse-bridge' ) : __( 'These menu locations are only used by browser storefronts that consume Bridge navigation.', 'storefuse-bridge' ),
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

$required_label       = __( 'Required', 'storefuse-bridge' );
$ready_label          = __( 'Ready', 'storefuse-bridge' );
$attention_label      = __( 'Needs attention', 'storefuse-bridge' );
$not_applicable_label = __( 'Not applicable', 'storefuse-bridge' );
$required_items       = array_values( array_filter( $readiness, static fn( $item ) => $required_label === $item['requirement'] && $not_applicable_label !== $item['state'] ) );
$other_items          = array_values( array_filter( $readiness, static fn( $item ) => $required_label !== $item['requirement'] || $not_applicable_label === $item['state'] ) );
$required_attention   = array_values( array_filter( $required_items, static fn( $item ) => $attention_label === $item['state'] ) );
$required_total_count = count( $required_items );
$required_ready       = count( array_filter( $required_items, static fn( $item ) => $ready_label === $item['state'] ) );
$enabled_module_count = count( array_filter( $modules ) );
$total_module_count   = count( $modules );
$disabled_module_count = max( 0, $total_module_count - $enabled_module_count );
$api_base             = untrailingslashit( rest_url( 'storefuse/v1' ) );
$api_status_url       = rest_url( 'storefuse/v1/status' );
$setup_url            = admin_url( 'admin.php?page=storefuse-bridge-setup' );

$next_action_url   = $setup_url;
$next_action_label = __( 'Open setup', 'storefuse-bridge' );
if ( ! empty( $required_attention ) && ! empty( $required_attention[0]['url'] ) ) {
    $next_action_url   = $required_attention[0]['url'];
    $next_action_label = sprintf(
        /* translators: %s is the name of the required setup check that needs attention. */
        __( 'Fix: %s', 'storefuse-bridge' ),
        $required_attention[0]['label']
    );
}
?>
<div class="wrap sfb-admin sfb-dashboard">
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <header class="sfb-dashboard-header">
        <div>
            <h1><?php esc_html_e( 'StoreFuse - Dashboard', 'storefuse-bridge' ); ?> <span class="sfb-version">v<?php echo esc_html( STOREFUSE_BRIDGE_VERSION ); ?></span></h1>
            <p class="sfb-dashboard-subtitle"><?php esc_html_e( 'Your store connection and integration health at a glance.', 'storefuse-bridge' ); ?></p>
        </div>
        <div class="sfb-dashboard-client">
            <span class="sfb-client-badge"><?php echo esc_html( $client_name ); ?></span>
            <a href="<?php echo esc_url( $setup_url ); ?>"><?php esc_html_e( 'Change client', 'storefuse-bridge' ); ?></a>
        </div>
    </header>

    <section class="sfb-dashboard-stat-grid" aria-label="<?php esc_attr_e( 'Store overview', 'storefuse-bridge' ); ?>">
        <article class="sfb-dashboard-stat-card">
            <h2><?php esc_html_e( 'Store readiness', 'storefuse-bridge' ); ?></h2>
            <p class="sfb-dashboard-stat-value">
                <?php if ( $required_total_count ) : ?>
                    <?php echo esc_html( number_format_i18n( $required_ready ) . ' / ' . number_format_i18n( $required_total_count ) ); ?>
                <?php else : ?>
                    <span class="sfb-dashboard-stat-value--text"><?php esc_html_e( 'No required checks', 'storefuse-bridge' ); ?></span>
                <?php endif; ?>
            </p>
            <p class="sfb-dashboard-stat-note">
                <?php if ( ! $required_total_count ) : ?>
                    <?php esc_html_e( 'No required checks apply.', 'storefuse-bridge' ); ?>
                <?php else : ?>
                    <?php
                    printf(
                        /* translators: 1: completed required checks, 2: total applicable required checks. */
                        esc_html__( '%1$s of %2$s required checks ready', 'storefuse-bridge' ),
                        esc_html( number_format_i18n( $required_ready ) ),
                        esc_html( number_format_i18n( $required_total_count ) )
                    );
                    ?>
                <?php endif; ?>
            </p>
        </article>

        <article class="sfb-dashboard-stat-card">
            <h2><?php esc_html_e( 'API endpoint', 'storefuse-bridge' ); ?></h2>
            <p id="sfb-dashboard-api-state" class="sfb-dashboard-stat-value sfb-dashboard-api-state" data-state="idle" data-available="<?php esc_attr_e( 'Available', 'storefuse-bridge' ); ?>" data-unavailable="<?php esc_attr_e( 'Unavailable', 'storefuse-bridge' ); ?>">
                <?php esc_html_e( 'Not checked', 'storefuse-bridge' ); ?>
            </p>
            <p class="sfb-dashboard-stat-note"><?php esc_html_e( 'Run a live check below.', 'storefuse-bridge' ); ?></p>
        </article>

        <article class="sfb-dashboard-stat-card">
            <h2><?php esc_html_e( 'Modules', 'storefuse-bridge' ); ?></h2>
            <p class="sfb-dashboard-stat-value"><?php echo esc_html( number_format_i18n( $enabled_module_count ) . ' / ' . number_format_i18n( $total_module_count ) ); ?></p>
            <p class="sfb-dashboard-stat-note">
                <?php
                printf(
                    /* translators: %s is the number of disabled modules. */
                    esc_html__( '%s disabled', 'storefuse-bridge' ),
                    esc_html( number_format_i18n( $disabled_module_count ) )
                );
                ?>
            </p>
        </article>

        <article class="sfb-dashboard-stat-card <?php echo empty( $required_attention ) ? 'sfb-dashboard-stat-card--positive' : 'sfb-dashboard-stat-card--attention'; ?>">
            <h2><?php esc_html_e( 'Attention needed', 'storefuse-bridge' ); ?></h2>
            <p class="sfb-dashboard-stat-value"><?php echo esc_html( number_format_i18n( count( $required_attention ) ) ); ?></p>
            <p class="sfb-dashboard-stat-note">
                <?php if ( empty( $required_attention ) ) : ?>
                    <?php esc_html_e( 'Required checks are clear.', 'storefuse-bridge' ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Required checks need action.', 'storefuse-bridge' ); ?>
                <?php endif; ?>
            </p>
        </article>
    </section>

    <section class="sfb-card sfb-dashboard-readiness">
        <div class="sfb-dashboard-card-heading">
            <div>
                <h2><?php esc_html_e( 'Store setup', 'storefuse-bridge' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Resolve required checks before launch. Recommendations are listed separately.', 'storefuse-bridge' ); ?></p>
            </div>
            <?php if ( empty( $required_attention ) ) : ?>
                <span class="sfb-badge sfb-badge--on"><?php esc_html_e( 'Ready', 'storefuse-bridge' ); ?></span>
            <?php else : ?>
                <span class="sfb-badge sfb-badge--attention">
                    <?php
                    printf(
                        /* translators: %s is the number of required checks needing attention. */
                        esc_html__( '%s need attention', 'storefuse-bridge' ),
                        esc_html( number_format_i18n( count( $required_attention ) ) )
                    );
                    ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $required_items ) ) : ?>
            <ul class="sfb-dashboard-checklist">
                <?php foreach ( $required_items as $item ) :
                    $is_ready        = $ready_label === $item['state'];
                    $state_class     = $is_ready ? 'on' : ( $attention_label === $item['state'] ? 'attention' : 'neutral' );
                    ?>
                    <li class="sfb-dashboard-checklist-item">
                        <div class="sfb-dashboard-checklist-copy">
                            <strong><?php echo esc_html( $item['label'] ); ?></strong>
                            <span><?php echo esc_html( $item['detail'] ); ?></span>
                        </div>
                        <div class="sfb-dashboard-checklist-action">
                            <span class="sfb-badge sfb-badge--<?php echo esc_attr( $state_class ); ?>"><?php echo esc_html( $item['state'] ); ?></span>
                            <?php if ( $attention_label === $item['state'] && ! empty( $item['url'] ) ) : ?>
                                <a class="button button-small" href="<?php echo esc_url( $item['url'] ); ?>"><?php esc_html_e( 'Fix', 'storefuse-bridge' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="sfb-dashboard-empty"><?php esc_html_e( 'No required setup checks apply to this store configuration.', 'storefuse-bridge' ); ?></p>
        <?php endif; ?>

        <?php if ( ! empty( $other_items ) ) : ?>
            <details class="sfb-dashboard-secondary-checks">
                <summary>
                    <?php
                    printf(
                        /* translators: %s is the number of recommended, optional, informational, or not-applicable checks. */
                        esc_html__( 'Show %s recommendations and other checks', 'storefuse-bridge' ),
                        esc_html( number_format_i18n( count( $other_items ) ) )
                    );
                    ?>
                </summary>
                <ul class="sfb-dashboard-checklist sfb-dashboard-checklist--secondary">
                    <?php foreach ( $other_items as $item ) :
                        $state_class = $ready_label === $item['state'] ? 'on' : ( $attention_label === $item['state'] ? 'attention' : 'neutral' );
                        ?>
                        <li class="sfb-dashboard-checklist-item">
                            <div class="sfb-dashboard-checklist-copy">
                                <strong><?php echo esc_html( $item['label'] ); ?></strong>
                                <span><?php echo esc_html( $item['detail'] ); ?></span>
                            </div>
                            <div class="sfb-dashboard-checklist-action">
                                <span class="sfb-badge sfb-badge--<?php echo esc_attr( $state_class ); ?>"><?php echo esc_html( $item['state'] ); ?></span>
                                <?php if ( $attention_label === $item['state'] && ! empty( $item['url'] ) ) : ?>
                                    <a class="button button-small" href="<?php echo esc_url( $item['url'] ); ?>"><?php esc_html_e( 'Review', 'storefuse-bridge' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>

        <a class="button button-primary sfb-dashboard-primary-action" href="<?php echo esc_url( $next_action_url ); ?>"><?php echo esc_html( $next_action_label ); ?></a>
    </section>

    <div class="sfb-dashboard-operations">
        <section class="sfb-card sfb-dashboard-operation-card">
            <h2><?php esc_html_e( 'API diagnostics', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Check the StoreFuse status endpoint and copy the API base URL for your client.', 'storefuse-bridge' ); ?></p>
            <div class="sfb-api-url-row">
                <code id="sfb-api-url"><?php echo esc_html( $api_base ); ?></code>
                <button type="button" class="button sfb-copy-btn" data-target="sfb-api-url"><?php esc_html_e( 'Copy URL', 'storefuse-bridge' ); ?></button>
            </div>
            <div class="sfb-dashboard-operation-actions">
                <button type="button" class="button" id="sfb-check-connection"><?php esc_html_e( 'Test connection', 'storefuse-bridge' ); ?></button>
                <a class="button" href="<?php echo esc_url( $api_status_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open /status', 'storefuse-bridge' ); ?></a>
                <a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'storefuse-bridge' ); ?></a>
            </div>
            <p id="sfb-connection-health" class="sfb-inline-result" role="status" aria-live="polite"></p>
        </section>

        <section class="sfb-card sfb-dashboard-operation-card">
            <h2><?php esc_html_e( 'Cache management', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Clear a targeted cache after content changes, or flush all StoreFuse cache groups.', 'storefuse-bridge' ); ?></p>
            <?php if ( ! empty( $last_flush['time'] ) ) : ?>
                <p class="sfb-dashboard-last-flush">
                    <?php
                    printf(
                        esc_html__( 'Last manual flush: %1$s (%2$s)', 'storefuse-bridge' ),
                        esc_html( $last_flush['time'] ),
                        esc_html( $last_flush['group'] ?? 'all' )
                    );
                    ?>
                </p>
            <?php endif; ?>
            <details class="sfb-dashboard-cache-details">
                <summary><?php esc_html_e( 'Manage cache', 'storefuse-bridge' ); ?></summary>
                <div class="sfb-cache-actions">
                    <button type="button" id="sfb-flush-cache" class="button button-secondary" data-group="all"><?php esc_html_e( 'Flush all', 'storefuse-bridge' ); ?></button>
                    <?php foreach ( [ 'products' => __( 'Products', 'storefuse-bridge' ), 'settings' => __( 'Settings', 'storefuse-bridge' ), 'navigation' => __( 'Navigation', 'storefuse-bridge' ), 'homepage' => __( 'Homepage', 'storefuse-bridge' ), 'search' => __( 'Search', 'storefuse-bridge' ) ] as $group => $label ) : ?>
                        <button type="button" class="button button-secondary sfb-flush-group" data-group="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $label ); ?></button>
                    <?php endforeach; ?>
                </div>
                <p id="sfb-flush-result" class="sfb-inline-result" role="status" aria-live="polite"></p>
                <p id="sfb-flush-group-result" class="sfb-inline-result" role="status" aria-live="polite"></p>
            </details>
        </section>

        <section class="sfb-card sfb-dashboard-operation-card sfb-dashboard-module-summary">
            <div>
                <h2><?php esc_html_e( 'Module status', 'storefuse-bridge' ); ?></h2>
                <p class="sfb-dashboard-module-count">
                    <?php
                    printf(
                        /* translators: 1: enabled module count, 2: total module count. */
                        esc_html__( '%1$s of %2$s modules enabled', 'storefuse-bridge' ),
                        esc_html( number_format_i18n( $enabled_module_count ) ),
                        esc_html( number_format_i18n( $total_module_count ) )
                    );
                    ?>
                </p>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s is the number of disabled modules. */
                        esc_html__( '%s disabled. Review the full list to confirm it matches your store.', 'storefuse-bridge' ),
                        esc_html( number_format_i18n( $disabled_module_count ) )
                    );
                    ?>
                </p>
            </div>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-modules' ) ); ?>"><?php esc_html_e( 'Manage modules', 'storefuse-bridge' ); ?></a>
        </section>
    </div>

    <details class="sfb-card sfb-dashboard-compatibility">
        <summary class="sfb-dashboard-compatibility-summary"><?php esc_html_e( 'WooCommerce compatibility', 'storefuse-bridge' ); ?></summary>
        <div class="sfb-dashboard-compatibility-grid">
            <?php
            $feature_labels = [ 'headless_checkout' => __( 'Headless checkout mode', 'storefuse-bridge' ), 'hpos' => __( 'WooCommerce HPOS', 'storefuse-bridge' ), 'store_api' => __( 'WooCommerce Store API', 'storefuse-bridge' ) ];
            foreach ( $feature_labels as $feature => $label ) :
                $enabled = ! empty( $features[ $feature ] );
                ?>
                <div class="sfb-dashboard-compatibility-item">
                    <span><?php echo esc_html( $label ); ?></span>
                    <span class="sfb-badge sfb-badge--<?php echo $enabled ? 'on' : 'neutral'; ?>"><?php echo $enabled ? esc_html__( 'Available', 'storefuse-bridge' ) : esc_html__( 'Unavailable', 'storefuse-bridge' ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </details>
</div>
