<?php defined( 'ABSPATH' ) || exit;

$locations      = get_nav_menu_locations();
$header_menu_id = $locations['storefuse-header'] ?? 0;
$footer_menu_id = $locations['storefuse-footer'] ?? 0;
$header_menu    = $header_menu_id ? wp_get_nav_menu_object( $header_menu_id ) : null;
$footer_menu    = $footer_menu_id ? wp_get_nav_menu_object( $footer_menu_id ) : null;
$menus_url      = admin_url( 'nav-menus.php' );

$header_items = StoreFuse_Bridge_Admin::get_menu_items_for_location( 'storefuse-header' );
$footer_items = StoreFuse_Bridge_Admin::get_menu_items_for_location( 'storefuse-footer' );
$header_tree    = StoreFuse_Bridge_Admin::nest_nav_items( $header_items );
$footer_tree    = StoreFuse_Bridge_Admin::nest_nav_items( $footer_items );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Navigation', 'storefuse-bridge' ); ?></h1>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Navigation menus', 'storefuse-bridge' ); ?></h2>
        <p class="description"><?php esc_html_e( 'API: GET /navigation or GET /settings → navigation. Assign menus to StoreFuse theme locations.', 'storefuse-bridge' ); ?></p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Header menu', 'storefuse-bridge' ); ?></th>
                <td>
                    <?php if ( $header_menu ) : ?>
                        <strong><?php echo esc_html( $header_menu->name ); ?></strong>
                        - <a href="<?php echo esc_url( add_query_arg( 'menu', $header_menu_id, $menus_url ) ); ?>"><?php esc_html_e( 'Edit', 'storefuse-bridge' ); ?></a>
                    <?php else : ?>
                        <span class="sfb-notice sfb-notice--warning"><?php esc_html_e( 'No menu assigned to storefuse-header.', 'storefuse-bridge' ); ?></span>
                        <br><a href="<?php echo esc_url( $menus_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Assign a menu', 'storefuse-bridge' ); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Footer menu', 'storefuse-bridge' ); ?></th>
                <td>
                    <?php if ( $footer_menu ) : ?>
                        <strong><?php echo esc_html( $footer_menu->name ); ?></strong>
                        - <a href="<?php echo esc_url( add_query_arg( 'menu', $footer_menu_id, $menus_url ) ); ?>"><?php esc_html_e( 'Edit', 'storefuse-bridge' ); ?></a>
                    <?php else : ?>
                        <span class="sfb-notice sfb-notice--warning"><?php esc_html_e( 'No menu assigned to storefuse-footer.', 'storefuse-bridge' ); ?></span>
                        <br><a href="<?php echo esc_url( $menus_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Assign a menu', 'storefuse-bridge' ); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Live preview', 'storefuse-bridge' ); ?></h2>
        <h3><?php esc_html_e( 'Header', 'storefuse-bridge' ); ?></h3>
        <?php StoreFuse_Bridge_Admin::render_nav_preview_list( $header_tree ); ?>
        <h3 style="margin-top:16px;"><?php esc_html_e( 'Footer', 'storefuse-bridge' ); ?></h3>
        <?php StoreFuse_Bridge_Admin::render_nav_preview_list( $footer_tree ); ?>
    </div>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'Navigation cache', 'storefuse-bridge' ); ?></h2>
        <p><?php esc_html_e( 'Flushes navigation and related settings transients only (not full product cache).', 'storefuse-bridge' ); ?></p>
        <button type="button" id="sfb-flush-nav-cache" class="button button-secondary sfb-flush-group" data-group="navigation">
            <?php esc_html_e( 'Flush navigation cache', 'storefuse-bridge' ); ?>
        </button>
        <span id="sfb-flush-nav-result" style="margin-left:10px;"></span>
    </div>
</div>
