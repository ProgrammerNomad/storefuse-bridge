<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$client = $s['primary_client'] ?? 'other';
$webhooks_on = ! empty( $s['module_webhooks_enabled'] );
$docs_url    = StoreFuse_Bridge_Admin::documentation_url();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Storefront & Clients', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Headless storefront URL', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Used for cache revalidation webhooks, password-reset links, and CORS guidance. Works with any headless client (Next.js, Flutter web, custom SPA).', 'storefuse-bridge' ); ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_storefront_url"><?php esc_html_e( 'Storefront URL', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="url" id="sfb_storefront_url" name="storefuse_bridge_settings[storefront_url]"
                               value="<?php echo esc_attr( $s['storefront_url'] ?? '' ); ?>" class="regular-text"
                               placeholder="https://shop.example.com" />
                        <p class="description"><?php esc_html_e( 'API: used by auth reset emails and webhook delivery (not exposed in GET /settings).', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_storefront_reset_path"><?php esc_html_e( 'Password reset path', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_storefront_reset_path" name="storefuse_bridge_settings[storefront_reset_path]"
                               value="<?php echo esc_attr( $s['storefront_reset_path'] ?? '/reset-password' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Deep link path on web or mobile (e.g. /reset-password). Filter: storefuse_bridge_password_reset_url.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_primary_client"><?php esc_html_e( 'Primary client (guidance)', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <select id="sfb_primary_client" name="storefuse_bridge_settings[primary_client]">
                            <option value="nextjs" <?php selected( $client, 'nextjs' ); ?>><?php esc_html_e( 'Next.js / React web', 'storefuse-bridge' ); ?></option>
                            <option value="flutter" <?php selected( $client, 'flutter' ); ?>><?php esc_html_e( 'Flutter (mobile / web)', 'storefuse-bridge' ); ?></option>
                            <option value="other" <?php selected( $client, 'other' ); ?>><?php esc_html_e( 'Other headless client', 'storefuse-bridge' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Admin tips only - does not change API behaviour.', 'storefuse-bridge' ); ?></p>
                        <p class="description">
                            <a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation on GitHub →', 'storefuse-bridge' ); ?></a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card" id="sfb-webhook-panel">
            <h2><?php esc_html_e( 'Cache revalidation (ISR webhooks)', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Enable the Webhooks module under Advanced, then configure a shared secret. Your storefront should expose a signed POST endpoint to purge cached pages.', 'storefuse-bridge' ); ?>
            </p>
            <?php if ( ! $webhooks_on ) : ?>
                <p class="sfb-notice sfb-notice--warning">
                    <?php
                    printf(
                        wp_kses_post( __( 'Webhooks module is <strong>disabled</strong>. <a href="%s">Enable it in Advanced</a>.', 'storefuse-bridge' ) ),
                        esc_url( admin_url( 'admin.php?page=storefuse-bridge-advanced' ) )
                    );
                    ?>
                </p>
            <?php endif; ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_revalidate_path"><?php esc_html_e( 'Revalidate path', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_revalidate_path" name="storefuse_bridge_settings[storefront_revalidate_path]"
                               value="<?php echo esc_attr( $s['storefront_revalidate_path'] ?? '/api/revalidate' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Appended to storefront URL (default /api/revalidate).', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_revalidation_secret"><?php esc_html_e( 'Revalidation secret', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="password" id="sfb_revalidation_secret" name="storefuse_bridge_settings[revalidation_secret]"
                               value="<?php echo esc_attr( $s['revalidation_secret'] ?? '' ); ?>" class="regular-text" autocomplete="new-password" />
                        <p class="description"><?php esc_html_e( 'HMAC-SHA256 shared secret (32+ random characters). Must match your storefront env.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Test connection', 'storefuse-bridge' ); ?></th>
                    <td>
                        <button type="button" class="button button-secondary" id="sfb-test-webhook" <?php disabled( ! $webhooks_on ); ?>>
                            <?php esc_html_e( 'Send test webhook', 'storefuse-bridge' ); ?>
                        </button>
                        <span id="sfb-test-webhook-result" style="margin-left:10px;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>

<?php if ( $webhooks_on ) :
    $webhook_log = (array) get_option( 'storefuse_bridge_webhook_log', [] );
?>
<div class="sfb-card">
    <h2><?php esc_html_e( 'Webhook delivery log', 'storefuse-bridge' ); ?></h2>
    <?php if ( empty( $webhook_log ) ) : ?>
        <p><?php esc_html_e( 'No deliveries recorded yet.', 'storefuse-bridge' ); ?></p>
    <?php else : ?>
        <table class="widefat striped" style="max-width:900px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Time', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Slug', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Error', 'storefuse-bridge' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $webhook_log as $entry ) :
                    $status = esc_html( $entry['status'] ?? '' );
                    $is_ok  = ( $status === '200' || $status === '204' );
                ?>
                <tr>
                    <td><?php echo esc_html( $entry['time'] ?? '' ); ?></td>
                    <td><code><?php echo esc_html( $entry['type'] ?? '' ); ?></code></td>
                    <td><?php echo esc_html( $entry['slug'] ?? '-' ); ?></td>
                    <td><?php echo $status; ?></td>
                    <td><?php echo esc_html( $entry['error'] ?? '' ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:8px;">
            <button type="button" id="sfb-clear-webhook-log" class="button button-secondary"><?php esc_html_e( 'Clear log', 'storefuse-bridge' ); ?></button>
            <span id="sfb-clear-log-result"></span>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>
