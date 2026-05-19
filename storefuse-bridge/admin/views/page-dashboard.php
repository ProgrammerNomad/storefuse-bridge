<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap sfb-admin">
    <h1>
        <?php esc_html_e( 'StoreFuse Bridge', 'storefuse-bridge' ); ?>
        <span class="sfb-version">v<?php echo esc_html( STOREFUSE_BRIDGE_VERSION ); ?></span>
    </h1>

    <div class="sfb-dashboard-grid">

        <!-- Status Card -->
        <div class="sfb-card sfb-card--status">
            <h2><?php esc_html_e( 'System Status', 'storefuse-bridge' ); ?></h2>
            <table class="sfb-status-table">
                <tr>
                    <td><?php esc_html_e( 'Plugin', 'storefuse-bridge' ); ?></td>
                    <td><span class="sfb-ok">OK</span> StoreFuse Bridge <?php echo esc_html( STOREFUSE_BRIDGE_VERSION ); ?></td>
                </tr>
                <tr>
                    <td>WordPress</td>
                    <td><span class="sfb-ok">OK</span> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                </tr>
                <tr>
                    <td>WooCommerce</td>
                    <td><span class="sfb-ok">OK</span> <?php echo esc_html( StoreFuse_Bridge_WC_Compat::wc_version() ); ?></td>
                </tr>
                <tr>
                    <td>PHP</td>
                    <td><span class="sfb-ok">OK</span> <?php echo esc_html( PHP_VERSION ); ?></td>
                </tr>
            </table>
        </div>

        <!-- API URL Card -->
        <div class="sfb-card sfb-card--api">
            <h2><?php esc_html_e( 'API Base URL', 'storefuse-bridge' ); ?></h2>
            <div class="sfb-api-url-row">
                <code id="sfb-api-url"><?php echo esc_html( get_site_url() . '/wp-json/storefuse/v1' ); ?></code>
                <button type="button" class="button sfb-copy-btn" data-target="sfb-api-url">
                    <?php esc_html_e( 'Copy', 'storefuse-bridge' ); ?>
                </button>
                <a href="<?php echo esc_url( get_site_url() . '/wp-json/storefuse/v1/status' ); ?>" target="_blank" class="button">
                    <?php esc_html_e( 'Test Status', 'storefuse-bridge' ); ?>
                </a>
            </div>
        </div>

        <!-- Modules Card -->
        <div class="sfb-card sfb-card--modules">
            <h2><?php esc_html_e( 'Modules', 'storefuse-bridge' ); ?></h2>
            <table class="sfb-modules-table widefat">
                <thead><tr>
                    <th><?php esc_html_e( 'Module', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'storefuse-bridge' ); ?></th>
                </tr></thead>
                <tbody>
                <?php
                $modules = [
                    'settings'   => __( 'Settings / Navigation / Homepage', 'storefuse-bridge' ),
                    'products'   => __( 'Products & Categories', 'storefuse-bridge' ),
                    'search'     => __( 'Search', 'storefuse-bridge' ),
                    'cart'       => __( 'Cart', 'storefuse-bridge' ),
                    'checkout'   => __( 'Checkout', 'storefuse-bridge' ),
                    'content'    => __( 'Reviews & Posts', 'storefuse-bridge' ),
                    'webhooks'   => __( 'ISR Webhooks', 'storefuse-bridge' ),
                ];
                foreach ( $modules as $key => $label ) :
                    $enabled = (bool) StoreFuse_Bridge_Settings::get( "module_{$key}_enabled", $key !== 'webhooks' );
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-advanced' ) ); ?>" class="button">
                    <?php esc_html_e( 'Manage Modules', 'storefuse-bridge' ); ?>
                </a>
            </p>
        </div>

        <!-- Cache Card -->
        <div class="sfb-card sfb-card--cache">
            <h2><?php esc_html_e( 'Cache', 'storefuse-bridge' ); ?></h2>
            <p><?php esc_html_e( 'Product and category data is served from WordPress transient cache. Cache is invalidated automatically when products or categories are saved.', 'storefuse-bridge' ); ?></p>
            <button type="button" id="sfb-flush-cache" class="button button-secondary">
                <?php esc_html_e( 'Flush All Cache', 'storefuse-bridge' ); ?>
            </button>
            <span id="sfb-flush-result" style="margin-left:10px;"></span>
        </div>

    </div><!-- .sfb-dashboard-grid -->

    <!-- Quick Links -->
    <div class="sfb-quick-links">
        <h2><?php esc_html_e( 'Settings', 'storefuse-bridge' ); ?></h2>
        <div class="sfb-link-grid">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-general' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'General Settings', 'storefuse-bridge' ); ?></strong>
                <span><?php esc_html_e( 'Announcement bar, policies', 'storefuse-bridge' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-homepage' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'Homepage', 'storefuse-bridge' ); ?></strong>
                <span><?php esc_html_e( 'Hero, featured categories', 'storefuse-bridge' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-social' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'Social & Trust', 'storefuse-bridge' ); ?></strong>
                <span><?php esc_html_e( 'Social links, trust badges', 'storefuse-bridge' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-advanced' ) ); ?>" class="sfb-link-card">
                <strong><?php esc_html_e( 'Advanced', 'storefuse-bridge' ); ?></strong>
                <span><?php esc_html_e( 'Module toggles', 'storefuse-bridge' ); ?></span>
            </a>
        </div>
    </div>

</div><!-- .wrap.sfb-admin -->
