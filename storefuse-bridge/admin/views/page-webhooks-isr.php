<?php
defined( 'ABSPATH' ) || exit;

$settings        = StoreFuse_Bridge_Settings::all();
$webhooks_on     = ! empty( $settings['module_webhooks_enabled'] );
$secret_configured = ! empty( $settings['revalidation_secret'] );
$webhook_log     = (array) get_option( 'storefuse_bridge_webhook_log', [] );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Webhooks (ISR)', 'storefuse-bridge' ); ?></h1>
    <?php StoreFuse_Bridge_Admin::render_client_context_notice( 'storefuse-bridge-webhooks' ); ?>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Webhook configuration', 'storefuse-bridge' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Bridge sends signed server-to-server requests to your storefront after relevant store data changes. These are not incoming payment-provider webhooks.', 'storefuse-bridge' ); ?></p>
        <?php if ( ! $webhooks_on ) : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e( 'The Webhooks module is currently disabled, so revalidation requests will not be sent.', 'storefuse-bridge' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-modules' ) ); ?>"><?php esc_html_e( 'Review Modules', 'storefuse-bridge' ); ?></a></p>
            </div>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e( 'Destination host:', 'storefuse-bridge' ); ?>
            <?php if ( ! empty( $settings['storefront_url'] ) ) : ?>
                <code><?php echo esc_html( $settings['storefront_url'] ); ?></code>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-setup' ) ); ?>"><?php esc_html_e( 'Edit in Setup', 'storefuse-bridge' ); ?></a>
            <?php else : ?>
                <em><?php esc_html_e( 'Not configured', 'storefuse-bridge' ); ?></em>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-setup' ) ); ?>"><?php esc_html_e( 'Configure in Setup', 'storefuse-bridge' ); ?></a>
            <?php endif; ?>
        </p>
        <form method="post" action="options.php">
            <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_revalidate_path"><?php esc_html_e( 'Revalidate path', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_revalidate_path" name="storefuse_bridge_settings[storefront_revalidate_path]" value="<?php echo esc_attr( $settings['storefront_revalidate_path'] ?? '/api/revalidate' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Appended to the storefront URL. Default: /api/revalidate.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_revalidation_secret"><?php esc_html_e( 'Signing secret', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="password" id="sfb_revalidation_secret" name="storefuse_bridge_settings[revalidation_secret]" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo esc_attr( $secret_configured ? __( 'Saved - enter a new value to replace it', 'storefuse-bridge' ) : __( 'Enter a shared secret', 'storefuse-bridge' ) ); ?>" />
                        <p class="description">
                            <?php echo $secret_configured ? esc_html__( 'A secret is configured. Leave this blank to keep it; the saved value is never displayed here.', 'storefuse-bridge' ) : esc_html__( 'Use a long random secret and configure the same value on your storefront.', 'storefuse-bridge' ); ?>
                        </p>
                        <label for="sfb_clear_revalidation_secret"><input type="checkbox" id="sfb_clear_revalidation_secret" name="storefuse_bridge_settings[_sfb_clear_revalidation_secret]" value="1" /> <?php esc_html_e( 'Clear the saved signing secret', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Webhook Settings', 'storefuse-bridge' ) ); ?>
        </form>
        <p>
            <button type="button" class="button button-secondary" id="sfb-test-webhook" <?php disabled( ! $webhooks_on ); ?>><?php esc_html_e( 'Send test webhook', 'storefuse-bridge' ); ?></button>
            <span id="sfb-test-webhook-result" class="sfb-inline-result" role="status" aria-live="polite"></span>
        </p>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Delivery log', 'storefuse-bridge' ); ?></h2>
        <?php if ( empty( $webhook_log ) ) : ?>
            <p><?php esc_html_e( 'No webhook deliveries have been recorded.', 'storefuse-bridge' ); ?></p>
        <?php else : ?>
            <div class="sfb-table-scroll">
                <table class="widefat striped">
                    <thead><tr>
                        <th scope="col"><?php esc_html_e( 'Time', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Type', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Item', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'HTTP status', 'storefuse-bridge' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Result', 'storefuse-bridge' ); ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ( $webhook_log as $entry ) : ?>
                            <tr>
                                <td><?php echo esc_html( $entry['time'] ?? '' ); ?></td>
                                <td><code><?php echo esc_html( $entry['type'] ?? '' ); ?></code></td>
                                <td><?php echo esc_html( $entry['slug'] ?? '-' ); ?></td>
                                <td><?php echo esc_html( $entry['status'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $entry['error'] ?? __( 'Delivered', 'storefuse-bridge' ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p>
                <button type="button" id="sfb-clear-webhook-log" class="button button-secondary"><?php esc_html_e( 'Clear delivery log', 'storefuse-bridge' ); ?></button>
                <span id="sfb-clear-log-result" class="sfb-inline-result" role="status" aria-live="polite"></span>
            </p>
        <?php endif; ?>
    </div>
</div>
