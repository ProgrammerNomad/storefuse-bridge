<?php
defined( 'ABSPATH' ) || exit;

$settings   = StoreFuse_Bridge_Settings::all();
$modules    = StoreFuse_Bridge_Module_Status::get_module_map();
$labels     = StoreFuse_Bridge_Admin::module_labels();
$route_map  = StoreFuse_Bridge_Admin::module_route_map();
$warn_ids   = StoreFuse_Bridge_Admin::module_disable_warn_ids();
$always_on  = StoreFuse_Bridge_Module_Status::always_on_module_ids();
$groups     = [
    __( 'Core & System', 'storefuse-bridge' )         => [ 'settings', 'utils' ],
    __( 'Catalog & Discovery', 'storefuse-bridge' )   => [ 'products', 'categories', 'search', 'attributes', 'tags' ],
    __( 'Commerce & Checkout', 'storefuse-bridge' )   => [ 'cart', 'checkout', 'orders', 'addresses' ],
    __( 'Customer & Social', 'storefuse-bridge' )     => [ 'account', 'wishlist', 'reviews' ],
    __( 'Content & Integrations', 'storefuse-bridge' ) => [ 'posts', 'downloads', 'webhooks' ],
];
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Modules', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
        <input type="hidden" name="storefuse_bridge_settings[_sfb_save_modules]" value="1" />

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Always available', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Status and authentication are core services and do not have disable switches.', 'storefuse-bridge' ); ?></p>
            <ul class="sfb-always-on-list">
                <?php foreach ( $always_on as $key ) : ?>
                    <li><span class="sfb-badge sfb-badge--on"><?php esc_html_e( 'Always on', 'storefuse-bridge' ); ?></span> <?php echo esc_html( $labels[ $key ] ?? ucfirst( $key ) ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php foreach ( $groups as $group_title => $module_ids ) : ?>
            <section class="sfb-card sfb-module-group" aria-label="<?php echo esc_attr( $group_title ); ?>">
                <h2><?php echo esc_html( $group_title ); ?></h2>
                <table class="form-table sfb-modules-advanced">
                    <tbody>
                        <?php foreach ( $module_ids as $key ) :
                            $label   = $labels[ $key ] ?? ucfirst( $key );
                            $enabled = ! empty( $modules[ $key ] );
                            $routes  = $route_map[ $key ] ?? [];
                            $warn    = in_array( $key, $warn_ids, true );
                            ?>
                            <tr class="sfb-module-row">
                                <th scope="row"><?php echo esc_html( $label ); ?></th>
                                <td>
                                    <label class="sfb-module-toggle" for="sfb-module-<?php echo esc_attr( $key ); ?>">
                                        <input type="checkbox" id="sfb-module-<?php echo esc_attr( $key ); ?>" name="storefuse_bridge_settings[module_<?php echo esc_attr( $key ); ?>_enabled]" value="1" <?php checked( $enabled ); ?> />
                                        <?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?>
                                    </label>
                                    <?php if ( $warn ) : ?>
                                        <p class="description sfb-module-warning">
                                            <?php
                                            printf(
                                                /* translators: %s: module label */
                                                esc_html__( 'Disabling %s removes the routes listed below and may affect connected client flows.', 'storefuse-bridge' ),
                                                esc_html( $label )
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ( $routes ) : ?>
                                        <details class="sfb-module-routes">
                                            <summary><?php esc_html_e( 'Affected routes', 'storefuse-bridge' ); ?></summary>
                                            <ul>
                                                <?php foreach ( $routes as $route ) : ?>
                                                    <li><code><?php echo esc_html( $route ); ?></code></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php endif; ?>
                                    <?php if ( 'webhooks' === $key && ! $enabled ) : ?>
                                        <p class="description"><?php esc_html_e( 'ISR revalidation requests will not be sent while this module is disabled.', 'storefuse-bridge' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-webhooks' ) ); ?>"><?php esc_html_e( 'Review Webhooks (ISR)', 'storefuse-bridge' ); ?></a></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach; ?>

        <?php submit_button( __( 'Save Modules', 'storefuse-bridge' ) ); ?>
    </form>
    <p class="description"><?php esc_html_e( 'The Dashboard module summary reflects the same status returned by GET /status.', 'storefuse-bridge' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge' ) ); ?>"><?php esc_html_e( 'Open Dashboard', 'storefuse-bridge' ); ?></a></p>
</div>
