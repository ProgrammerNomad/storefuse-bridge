<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$modules = [
    'products'   => [ 'label' => __( 'Products & Categories', 'storefuse-bridge' ),   'default' => true ],
    'search'     => [ 'label' => __( 'Search', 'storefuse-bridge' ),                   'default' => true ],
    'cart'       => [ 'label' => __( 'Cart', 'storefuse-bridge' ),                     'default' => true ],
    'checkout'   => [ 'label' => __( 'Checkout', 'storefuse-bridge' ),                 'default' => true ],
    'content'    => [ 'label' => __( 'Reviews & Posts', 'storefuse-bridge' ),          'default' => true ],
    'webhooks'   => [ 'label' => __( 'ISR Revalidation Webhooks', 'storefuse-bridge' ), 'default' => false ],
];
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Advanced', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <!-- ── Module Toggles ───────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Module Toggles', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Disable modules you do not use. Disabled modules register no REST routes, reducing the API surface.', 'storefuse-bridge' ); ?>
            </p>
            <table class="form-table">
                <?php foreach ( $modules as $key => $cfg ) :
                    $enabled = isset( $s[ "module_{$key}_enabled" ] )
                        ? (bool) $s[ "module_{$key}_enabled" ]
                        : $cfg['default'];
                ?>
                <tr>
                    <th><?php echo esc_html( $cfg['label'] ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="storefuse_bridge_settings[module_<?php echo esc_attr( $key ); ?>_enabled]"
                                   value="1" <?php checked( $enabled ); ?> />
                            <?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- ── Danger Zone ──────────────────────────────────────────── -->
        <div class="sfb-card sfb-card--danger">
            <h2><?php esc_html_e( 'Cache', 'storefuse-bridge' ); ?></h2>
            <p><?php esc_html_e( 'Manually flush all StoreFuse Bridge transient cache. This happens automatically when products, categories, or settings are saved.', 'storefuse-bridge' ); ?></p>
            <button type="button" id="sfb-flush-cache" class="button button-secondary">
                <?php esc_html_e( 'Flush All Cache', 'storefuse-bridge' ); ?>
            </button>
            <span id="sfb-flush-result" style="margin-left:10px;"></span>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>

<?php
// Webhook delivery log - only render when the webhooks module is enabled.
if ( ! empty( $s['module_webhooks_enabled'] ) ) :
    $webhook_log = (array) get_option( 'storefuse_bridge_webhook_log', [] );
?>
<div class="sfb-card">
    <h2><?php esc_html_e( 'Webhook Delivery Log', 'storefuse-bridge' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Last 20 ISR revalidation attempts. Status shows the HTTP response code returned by the storefront, or "error" if the request could not be sent.', 'storefuse-bridge' ); ?>
    </p>
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
                    $status    = esc_html( $entry['status'] ?? '' );
                    $is_ok     = ( $status === '200' || $status === '204' );
                    $is_error  = ( $status === 'error' || ( is_numeric( $status ) && (int) $status >= 400 ) );
                    $row_style = $is_ok ? 'color:#0a6640;' : ( $is_error ? 'color:#c0392b;' : '' );
                ?>
                <tr>
                    <td><?php echo esc_html( $entry['time'] ?? '' ); ?></td>
                    <td><code><?php echo esc_html( $entry['type'] ?? '' ); ?></code></td>
                    <td><?php echo esc_html( $entry['slug'] ?? '—' ); ?></td>
                    <td style="<?php echo esc_attr( $row_style ); ?> font-weight:600;"><?php echo $status; ?></td>
                    <td style="color:#c0392b;"><?php echo esc_html( $entry['error'] ?? '' ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:8px;">
            <button type="button" id="sfb-clear-webhook-log" class="button button-secondary">
                <?php esc_html_e( 'Clear Log', 'storefuse-bridge' ); ?>
            </button>
            <span id="sfb-clear-log-result" style="margin-left:10px;"></span>
        </p>
    <?php endif; ?>
</div>

<script>
( function () {
    var btn    = document.getElementById( 'sfb-clear-webhook-log' );
    var result = document.getElementById( 'sfb-clear-log-result' );
    if ( ! btn ) return;
    btn.addEventListener( 'click', function () {
        if ( ! confirm( '<?php echo esc_js( __( 'Clear the webhook delivery log?', 'storefuse-bridge' ) ); ?>' ) ) return;
        btn.disabled = true;
        fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
            method  : 'POST',
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
            body    : 'action=storefuse_bridge_clear_webhook_log&nonce=' + encodeURIComponent( sfbAdmin.nonce ),
        } )
        .then( function ( r ) { return r.json(); } )
        .then( function ( data ) {
            if ( data.success ) {
                // Remove the table and replace with the empty-state message.
                var card = btn.closest( '.sfb-card' );
                var table = card.querySelector( 'table' );
                var p     = card.querySelector( 'p:not(.description)' );
                if ( table ) table.remove();
                if ( p )     p.remove();
                card.insertAdjacentHTML( 'beforeend', '<p><?php echo esc_js( __( 'No deliveries recorded yet.', 'storefuse-bridge' ) ); ?></p>' );
                result.textContent = '';
            } else {
                result.textContent = '<?php echo esc_js( __( 'Error clearing log.', 'storefuse-bridge' ) ); ?>';
                btn.disabled = false;
            }
        } )
        .catch( function () {
            result.textContent = '<?php echo esc_js( __( 'Request failed.', 'storefuse-bridge' ) ); ?>';
            btn.disabled = false;
        } );
    } );
} )();
</script>
<?php endif; ?>
