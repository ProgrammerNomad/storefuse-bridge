<?php
defined( 'ABSPATH' ) || exit;

$modules   = StoreFuse_Bridge_Module_Status::get_module_map();
$labels    = StoreFuse_Bridge_Admin::module_labels();
$features  = StoreFuse_Bridge_WC_Compat::features();
$settings  = StoreFuse_Bridge_Settings::all();
$locations = get_nav_menu_locations();
$last_flush = get_option( 'storefuse_bridge_last_flush_at', [] );
$docs_url   = StoreFuse_Bridge_Admin::documentation_url();

$readiness = [
    [
        'label' => __( 'Header menu assigned (storefuse-header)', 'storefuse-bridge' ),
        'ok'    => ! empty( $locations['storefuse-header'] ),
        'link'  => admin_url( 'nav-menus.php' ),
    ],
    [
        'label' => __( 'Footer menu assigned (storefuse-footer)', 'storefuse-bridge' ),
        'ok'    => ! empty( $locations['storefuse-footer'] ),
        'link'  => admin_url( 'nav-menus.php' ),
    ],
    [
        'label' => __( 'Storefront URL configured', 'storefuse-bridge' ),
        'ok'    => ! empty( $settings['storefront_url'] ),
        'link'  => admin_url( 'admin.php?page=storefuse-bridge-storefront' ),
    ],
    [
        'label' => __( 'Site uses HTTPS (recommended)', 'storefuse-bridge' ),
        'ok'    => is_ssl(),
        'link'  => '',
    ],
    [
        'label' => __( 'CORS origins listed (when storefront is external)', 'storefuse-bridge' ),
        'ok'    => empty( $settings['storefront_url'] ) || ( ! empty( $settings['cors_enabled'] ) && trim( (string) ( $settings['cors_allowed_origins'] ?? '' ) ) !== '' ),
        'link'  => admin_url( 'admin.php?page=storefuse-bridge-api' ),
    ],
    [
        'label' => __( 'Primary client selected (Next.js / Flutter / Other)', 'storefuse-bridge' ),
        'ok'    => ! empty( $settings['primary_client'] ) && ( $settings['primary_client'] ?? 'other' ) !== 'other',
        'link'  => admin_url( 'admin.php?page=storefuse-bridge-storefront' ),
    ],
];
?>
<div class="wrap sfb-admin">
    <h1>
        <?php esc_html_e( 'StoreFuse Bridge', 'storefuse-bridge' ); ?>
        <span class="sfb-version">v<?php echo esc_html( STOREFUSE_BRIDGE_VERSION ); ?></span>
    </h1>

    <div class="sfb-dashboard-grid">

        <div class="sfb-card sfb-card--status">
            <h2><?php esc_html_e( 'Setup readiness', 'storefuse-bridge' ); ?></h2>
            <table class="sfb-status-table widefat">
                <?php foreach ( $readiness as $item ) : ?>
                <tr>
                    <td>
                        <?php if ( $item['ok'] ) : ?>
                            <span class="sfb-badge sfb-badge--on"><?php esc_html_e( 'OK', 'storefuse-bridge' ); ?></span>
                        <?php else : ?>
                            <span class="sfb-badge sfb-badge--off"><?php esc_html_e( 'Todo', 'storefuse-bridge' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html( $item['label'] ); ?>
                        <?php if ( ! $item['ok'] && $item['link'] ) : ?>
                            - <a href="<?php echo esc_url( $item['link'] ); ?>"><?php esc_html_e( 'Configure', 'storefuse-bridge' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="sfb-card sfb-card--api">
            <h2><?php esc_html_e( 'API base URL', 'storefuse-bridge' ); ?></h2>
            <div class="sfb-api-url-row">
                <code id="sfb-api-url"><?php echo esc_html( get_site_url() . '/wp-json/storefuse/v1' ); ?></code>
                <button type="button" class="button sfb-copy-btn" data-target="sfb-api-url"><?php esc_html_e( 'Copy', 'storefuse-bridge' ); ?></button>
                <a href="<?php echo esc_url( get_site_url() . '/wp-json/storefuse/v1/status' ); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e( 'Test /status', 'storefuse-bridge' ); ?></a>
            </div>
        </div>

        <div class="sfb-card sfb-card--modules">
            <h2><?php esc_html_e( 'Modules (matches GET /status)', 'storefuse-bridge' ); ?></h2>
            <table class="sfb-modules-table widefat">
                <thead><tr>
                    <th><?php esc_html_e( 'Module', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'storefuse-bridge' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $modules as $key => $enabled ) :
                    $label = $labels[ $key ] ?? ucfirst( $key );
                ?>
                <tr>
                    <td><?php echo esc_html( $label ); ?></td>
                    <td>
                        <?php if ( $enabled ) : ?>
                            <span class="sfb-badge sfb-badge--on"><?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?></span>
                        <?php else : ?>
                            <span class="sfb-badge sfb-badge--off"><?php esc_html_e( 'Disabled', 'storefuse-bridge' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-advanced' ) ); ?>" class="button"><?php esc_html_e( 'Manage modules on Advanced', 'storefuse-bridge' ); ?></a>
            </p>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Features', 'storefuse-bridge' ); ?></h2>
            <table class="widefat">
                <tbody>
                <?php
                $feature_labels = [
                    'headless_checkout' => __( 'Headless checkout mode', 'storefuse-bridge' ),
                    'hpos'              => __( 'WooCommerce HPOS', 'storefuse-bridge' ),
                    'store_api'         => __( 'WooCommerce Store API', 'storefuse-bridge' ),
                ];
                foreach ( $feature_labels as $fk => $fl ) :
                    $on = ! empty( $features[ $fk ] );
                ?>
                <tr>
                    <td><?php echo esc_html( $fl ); ?></td>
                    <td>
                        <span class="sfb-badge sfb-badge--<?php echo $on ? 'on' : 'off'; ?>">
                            <?php echo $on ? esc_html__( 'Yes', 'storefuse-bridge' ) : esc_html__( 'No', 'storefuse-bridge' ); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="sfb-card sfb-card--cache">
            <h2><?php esc_html_e( 'Cache', 'storefuse-bridge' ); ?></h2>
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
            <p><?php esc_html_e( 'Product and settings data is cached in transients. Use API & Tools for targeted flush groups.', 'storefuse-bridge' ); ?></p>
            <button type="button" id="sfb-flush-cache" class="button button-secondary" data-group="all"><?php esc_html_e( 'Flush all cache', 'storefuse-bridge' ); ?></button>
            <span id="sfb-flush-result" style="margin-left:10px;"></span>
            <p style="margin-top:10px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-api' ) ); ?>"><?php esc_html_e( 'Open API & Tools →', 'storefuse-bridge' ); ?></a>
            </p>
        </div>

    </div>

    <div class="sfb-quick-links">
        <h2><?php esc_html_e( 'Documentation', 'storefuse-bridge' ); ?></h2>
        <div class="sfb-link-grid">
            <a href="<?php echo esc_url( $docs_url ); ?>" class="sfb-link-card" target="_blank" rel="noopener">
                <strong><?php esc_html_e( 'Documentation on GitHub', 'storefuse-bridge' ); ?></strong>
                <span><?php esc_html_e( 'API reference, client guides, admin guide, extensions', 'storefuse-bridge' ); ?></span>
            </a>
            <a href="<?php echo esc_url( str_replace( 'README.md', 'staging-smoke.md', $docs_url ) ); ?>" class="sfb-link-card" target="_blank" rel="noopener">
                <strong><?php esc_html_e( 'Staging smoke checklist', 'storefuse-bridge' ); ?></strong>
                <span><?php esc_html_e( 'CORS, idempotency, cart session — run before production', 'storefuse-bridge' ); ?></span>
            </a>
        </div>
    </div>

    <div class="sfb-quick-links">
        <h2><?php esc_html_e( 'Settings', 'storefuse-bridge' ); ?></h2>
        <div class="sfb-link-grid">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-general' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'General', 'storefuse-bridge' ); ?></strong>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-homepage' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'Homepage', 'storefuse-bridge' ); ?></strong>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-storefront' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'Storefront & clients', 'storefuse-bridge' ); ?></strong>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-api' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'API & tools', 'storefuse-bridge' ); ?></strong>
            </a>
        </div>
    </div>
</div>
