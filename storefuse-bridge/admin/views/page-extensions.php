<?php defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Extensions', 'storefuse-bridge' ); ?></h1>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Companion plugins & hooks', 'storefuse-bridge' ); ?></h2>
        <p>
            <?php esc_html_e( 'Extend Bridge with WordPress filters and actions, or ship a companion plugin that registers additional REST modules through the storefuse_bridge_modules filter.', 'storefuse-bridge' ); ?>
        </p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url( StoreFuse_Bridge_Admin::documentation_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View documentation on GitHub', 'storefuse-bridge' ); ?></a>
        </p>
        <p class="description">
            <?php esc_html_e( 'Read the capability and adapter contracts before enabling optional services.', 'storefuse-bridge' ); ?>
        </p>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Optional capabilities (honest defaults)', 'storefuse-bridge' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Bridge stores signups and device tokens but does not send email or push until a companion plugin returns true on the matching filter.', 'storefuse-bridge' ); ?>
        </p>
        <table class="widefat striped" style="max-width:900px;">
            <thead><tr><th><?php esc_html_e( 'Filter', 'storefuse-bridge' ); ?></th><th><?php esc_html_e( 'When true', 'storefuse-bridge' ); ?></th></tr></thead>
            <tbody>
                <tr><td><code>storefuse_bridge_stock_alerts_available</code></td><td><?php esc_html_e( 'POST /products/{slug}/notify accepts signups', 'storefuse-bridge' ); ?></td></tr>
                <tr><td><code>storefuse_bridge_push_available</code></td><td><?php esc_html_e( 'POST /account/device-token stores FCM/APNs tokens', 'storefuse-bridge' ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Common integration hooks', 'storefuse-bridge' ); ?></h2>
        <table class="widefat striped" style="max-width:900px;">
            <thead><tr><th><?php esc_html_e( 'Hook', 'storefuse-bridge' ); ?></th><th><?php esc_html_e( 'Purpose', 'storefuse-bridge' ); ?></th></tr></thead>
            <tbody>
                <tr><td><code>storefuse_bridge_settings_response</code></td><td><?php esc_html_e( 'Filter GET /settings payload', 'storefuse-bridge' ); ?></td></tr>
                <tr><td><code>storefuse_bridge_homepage_response</code></td><td><?php esc_html_e( 'Filter GET /homepage blocks', 'storefuse-bridge' ); ?></td></tr>
                <tr><td><code>storefuse_bridge_status_response</code></td><td><?php esc_html_e( 'Filter GET /status modules/features', 'storefuse-bridge' ); ?></td></tr>
                <tr><td><code>storefuse_bridge_password_reset_url</code></td><td><?php esc_html_e( 'Override password reset link target', 'storefuse-bridge' ); ?></td></tr>
                <tr><td><code>storefuse_bridge_settings_updated</code></td><td><?php esc_html_e( 'Fires when wp-admin saves Bridge settings', 'storefuse-bridge' ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Registered companion modules', 'storefuse-bridge' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Companion modules are registered in code using storefuse_bridge_modules. Each module supplies its own permissions and API contract.', 'storefuse-bridge' ); ?></p>
    </div>
</div>
