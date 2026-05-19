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
