<?php defined( 'ABSPATH' ) || exit;
$s         = StoreFuse_Bridge_Settings::all();
$modules   = StoreFuse_Bridge_Module_Status::get_module_map();
$labels    = StoreFuse_Bridge_Admin::module_labels();
$route_map = StoreFuse_Bridge_Admin::module_route_map();
$warn_ids  = StoreFuse_Bridge_Admin::module_disable_warn_ids();
$always_on = StoreFuse_Bridge_Module_Status::always_on_module_ids();
$toggleable = StoreFuse_Bridge_Module_Status::toggleable_module_ids();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Advanced', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
        <input type="hidden" name="storefuse_bridge_settings[_sfb_save_modules]" value="1" />

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Modules (matches GET /status)', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Disabled modules register no REST routes. Status and authentication are always available.', 'storefuse-bridge' ); ?>
            </p>
            <table class="form-table sfb-modules-advanced">
                <?php foreach ( $always_on as $key ) :
                    $label = $labels[ $key ] ?? ucfirst( $key );
                ?>
                <tr class="sfb-module-row sfb-module-row--always-on">
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td>
                        <span class="sfb-badge sfb-badge--on"><?php esc_html_e( 'Always on', 'storefuse-bridge' ); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php foreach ( $toggleable as $key ) :
                    $label   = $labels[ $key ] ?? ucfirst( $key );
                    $enabled = ! empty( $modules[ $key ] );
                    $routes  = $route_map[ $key ] ?? [];
                    $warn    = in_array( $key, $warn_ids, true );
                ?>
                <tr class="sfb-module-row">
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="storefuse_bridge_settings[module_<?php echo esc_attr( $key ); ?>_enabled]"
                                   value="1" <?php checked( $enabled ); ?> />
                            <?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?>
                        </label>
                        <?php if ( $warn ) : ?>
                            <p class="description" style="color:#b45309;"><?php esc_html_e( 'Disabling may break cart, checkout, login, or storefront content flows.', 'storefuse-bridge' ); ?></p>
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge' ) ); ?>"><?php esc_html_e( 'Dashboard module summary →', 'storefuse-bridge' ); ?></a>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-storefront' ) ); ?>"><?php esc_html_e( 'Storefront URL & webhooks →', 'storefuse-bridge' ); ?></a>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-api' ) ); ?>"><?php esc_html_e( 'API & cache tools →', 'storefuse-bridge' ); ?></a>
            </p>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
