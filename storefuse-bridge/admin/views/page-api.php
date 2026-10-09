<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$api_base = get_site_url() . '/wp-json/storefuse/v1';
$last_flush = get_option( 'storefuse_bridge_last_flush_at', [] );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - API & Tools', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'CORS (browser clients)', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Allow credentialed fetch() from your storefront origin. Do not use * when cookies are required - list each origin explicitly.', 'storefuse-bridge' ); ?>
            </p>
            <?php if ( ! empty( $s['cors_enabled'] ) && trim( (string) ( $s['cors_allowed_origins'] ?? '' ) ) === '' ) : ?>
                <div class="notice notice-warning inline"><p><?php esc_html_e( 'CORS is enabled but no origins are listed. Browsers will not receive Access-Control-Allow-Origin.', 'storefuse-bridge' ); ?></p></div>
            <?php endif; ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable CORS', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="storefuse_bridge_settings[cors_enabled]" value="1" <?php checked( ! empty( $s['cors_enabled'] ) ); ?> />
                            <?php esc_html_e( 'Send CORS headers on /storefuse/v1/* responses', 'storefuse-bridge' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_cors_origins"><?php esc_html_e( 'Allowed origins', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <textarea id="sfb_cors_origins" name="storefuse_bridge_settings[cors_allowed_origins]" rows="5" class="large-text code"><?php echo esc_textarea( $s['cors_allowed_origins'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'One origin per line, e.g. https://shop.example.com', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save CORS Settings', 'storefuse-bridge' ) ); ?>
    </form>

    <div class="sfb-card sfb-card--api">
        <h2><?php esc_html_e( 'API base URL', 'storefuse-bridge' ); ?></h2>
        <div class="sfb-api-url-row">
            <code id="sfb-api-url"><?php echo esc_html( $api_base ); ?></code>
            <button type="button" class="button sfb-copy-btn" data-target="sfb-api-url"><?php esc_html_e( 'Copy', 'storefuse-bridge' ); ?></button>
            <a href="<?php echo esc_url( $api_base . '/status' ); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e( 'Open /status', 'storefuse-bridge' ); ?></a>
            <a href="<?php echo esc_url( $api_base . '/settings' ); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e( 'View /settings', 'storefuse-bridge' ); ?></a>
        </div>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Cache control', 'storefuse-bridge' ); ?></h2>
        <?php if ( ! empty( $last_flush['time'] ) ) : ?>
            <p class="description">
                <?php
                printf(
                    esc_html__( 'Last flush: %1$s (%2$s)', 'storefuse-bridge' ),
                    esc_html( $last_flush['time'] ),
                    esc_html( $last_flush['group'] ?? 'all' )
                );
                ?>
            </p>
        <?php endif; ?>
        <p><?php esc_html_e( 'Targeted flush avoids clearing unrelated transients.', 'storefuse-bridge' ); ?></p>
        <p>
            <?php
            $groups = [
                'all'        => __( 'Flush all', 'storefuse-bridge' ),
                'products'   => __( 'Products', 'storefuse-bridge' ),
                'settings'   => __( 'Settings', 'storefuse-bridge' ),
                'navigation' => __( 'Navigation', 'storefuse-bridge' ),
                'homepage'   => __( 'Homepage', 'storefuse-bridge' ),
                'search'     => __( 'Search', 'storefuse-bridge' ),
            ];
            foreach ( $groups as $group => $label ) :
            ?>
                <button type="button" class="button button-secondary sfb-flush-group" data-group="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $label ); ?></button>
            <?php endforeach; ?>
        </p>
        <span id="sfb-flush-group-result"></span>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Session & auth checklist', 'storefuse-bridge' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Use HTTPS in production for WordPress and the storefront.', 'storefuse-bridge' ); ?></li>
            <li><?php esc_html_e( 'Configure CORS origins to match your storefront URL.', 'storefuse-bridge' ); ?></li>
            <li><?php esc_html_e( 'Test logged-in cart with browser credentials (include cookies).', 'storefuse-bridge' ); ?></li>
            <li><?php esc_html_e( 'Mobile: follow cart token + nonce flow in the Flutter client doc.', 'storefuse-bridge' ); ?></li>
        </ol>
        <p>
            <a href="<?php echo esc_url( plugins_url( '../docs/verified-routes.md', dirname( __FILE__ ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Verified routes (source of truth) →', 'storefuse-bridge' ); ?></a>
        </p>
    </div>
</div>
