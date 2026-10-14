<?php
defined( 'ABSPATH' ) || exit;

$settings = StoreFuse_Bridge_Settings::all();
$client   = $settings['primary_client'] ?? 'other';
$api_base = get_site_url() . '/wp-json/storefuse/v1';
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Setup', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Choose your primary client', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'This selection changes admin navigation and guidance only. It does not disable API routes or clear settings.', 'storefuse-bridge' ); ?></p>
            <fieldset class="sfb-client-cards">
                <legend class="screen-reader-text"><?php esc_html_e( 'Primary client', 'storefuse-bridge' ); ?></legend>
                <label class="sfb-client-card" for="sfb-client-flutter">
                    <input type="radio" id="sfb-client-flutter" name="storefuse_bridge_settings[primary_client]" value="flutter" <?php checked( $client, 'flutter' ); ?> />
                    <span><strong><?php esc_html_e( 'Flutter app', 'storefuse-bridge' ); ?></strong><span><?php esc_html_e( 'Native app or Flutter Web client', 'storefuse-bridge' ); ?></span></span>
                </label>
                <label class="sfb-client-card" for="sfb-client-nextjs">
                    <input type="radio" id="sfb-client-nextjs" name="storefuse_bridge_settings[primary_client]" value="nextjs" <?php checked( $client, 'nextjs' ); ?> />
                    <span><strong><?php esc_html_e( 'Next.js storefront', 'storefuse-bridge' ); ?></strong><span><?php esc_html_e( 'Browser-based decoupled storefront', 'storefuse-bridge' ); ?></span></span>
                </label>
                <label class="sfb-client-card" for="sfb-client-other">
                    <input type="radio" id="sfb-client-other" name="storefuse_bridge_settings[primary_client]" value="other" <?php checked( $client, 'other' ); ?> />
                    <span><strong><?php esc_html_e( 'Other client', 'storefuse-bridge' ); ?></strong><span><?php esc_html_e( 'Any other integration; this is a valid default', 'storefuse-bridge' ); ?></span></span>
                </label>
            </fieldset>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Storefront connection', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_storefront_url"><?php esc_html_e( 'Storefront URL', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="url" id="sfb_storefront_url" name="storefuse_bridge_settings[storefront_url]" value="<?php echo esc_attr( $settings['storefront_url'] ?? '' ); ?>" class="regular-text" placeholder="https://shop.example.com" />
                        <p class="description"><?php esc_html_e( 'Used as the destination host by the outgoing Webhooks module. It is not exposed in the public settings response.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_storefront_reset_path"><?php esc_html_e( 'Password reset path', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_storefront_reset_path" name="storefuse_bridge_settings[storefront_reset_path]" value="<?php echo esc_attr( $settings['storefront_reset_path'] ?? '/reset-password' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'This existing value is retained for integrations, but Bridge’s built-in reset email uses the WordPress reset URL. A companion integration can customize that URL with the storefuse_bridge_password_reset_url filter.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Setup', 'storefuse-bridge' ) ); ?>
    </form>

    <div class="sfb-card sfb-card--api">
        <h2><?php esc_html_e( 'API connection', 'storefuse-bridge' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Use this base URL in your client configuration.', 'storefuse-bridge' ); ?></p>
        <div class="sfb-api-url-row">
            <code id="sfb-setup-api-url"><?php echo esc_html( $api_base ); ?></code>
            <button type="button" class="button sfb-copy-btn" data-target="sfb-setup-api-url"><?php esc_html_e( 'Copy URL', 'storefuse-bridge' ); ?></button>
            <button type="button" class="button" id="sfb-check-connection"><?php esc_html_e( 'Check connection', 'storefuse-bridge' ); ?></button>
        </div>
        <p id="sfb-connection-health" class="sfb-inline-result" role="status" aria-live="polite"></p>
    </div>
</div>
