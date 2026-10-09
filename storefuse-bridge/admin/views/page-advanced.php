<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$modules = [
    'products'   => [ 'label' => __( 'Products', 'storefuse-bridge' ), 'default' => true, 'warn' => false ],
    'categories' => [ 'label' => __( 'Categories', 'storefuse-bridge' ), 'default' => true, 'warn' => false ],
    'search'     => [ 'label' => __( 'Search', 'storefuse-bridge' ), 'default' => true, 'warn' => false ],
    'cart'       => [ 'label' => __( 'Cart', 'storefuse-bridge' ), 'default' => true, 'warn' => true ],
    'checkout'   => [ 'label' => __( 'Checkout', 'storefuse-bridge' ), 'default' => true, 'warn' => true ],
    'posts'      => [ 'label' => __( 'Blog posts', 'storefuse-bridge' ), 'default' => true, 'warn' => false ],
    'reviews'    => [ 'label' => __( 'Product reviews', 'storefuse-bridge' ), 'default' => true, 'warn' => false ],
    'webhooks'   => [ 'label' => __( 'ISR revalidation webhooks', 'storefuse-bridge' ), 'default' => false, 'warn' => false ],
];
$route_map = StoreFuse_Bridge_Admin::module_route_map();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Advanced', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Module toggles', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Disabled modules register no REST routes. Auth and status are always available.', 'storefuse-bridge' ); ?>
            </p>
            <table class="form-table">
                <?php foreach ( $modules as $key => $cfg ) :
                    $enabled = isset( $s[ "module_{$key}_enabled" ] )
                        ? (bool) $s[ "module_{$key}_enabled" ]
                        : $cfg['default'];
                    $routes  = $route_map[ $key ] ?? [];
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $cfg['label'] ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="storefuse_bridge_settings[module_<?php echo esc_attr( $key ); ?>_enabled]"
                                   value="1" <?php checked( $enabled ); ?> />
                            <?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?>
                        </label>
                        <?php if ( $cfg['warn'] ) : ?>
                            <p class="description" style="color:#b45309;"><?php esc_html_e( 'Disabling may break cart, checkout, or login flows on your storefront.', 'storefuse-bridge' ); ?></p>
                        <?php endif; ?>
                        <?php if ( $routes ) : ?>
                            <details style="margin-top:8px;">
                                <summary><?php esc_html_e( 'Affected routes', 'storefuse-bridge' ); ?></summary>
                                <ul style="margin:8px 0 0 1.2em;">
                                    <?php foreach ( $routes as $route ) : ?>
                                        <li><code><?php echo esc_html( $route ); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p class="description">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-storefront' ) ); ?>"><?php esc_html_e( 'Storefront URL & webhooks →', 'storefuse-bridge' ); ?></a>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-api' ) ); ?>"><?php esc_html_e( 'API & cache tools →', 'storefuse-bridge' ); ?></a>
            </p>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
