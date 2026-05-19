<?php defined( 'ABSPATH' ) || exit;

// Resolve assigned nav menus for StoreFuse locations.
$locations        = get_nav_menu_locations();
$header_menu_id   = $locations['storefuse-header'] ?? 0;
$footer_menu_id   = $locations['storefuse-footer'] ?? 0;
$header_menu      = $header_menu_id ? wp_get_nav_menu_object( $header_menu_id ) : null;
$footer_menu      = $footer_menu_id ? wp_get_nav_menu_object( $footer_menu_id ) : null;

$menus_url = admin_url( 'nav-menus.php' );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Navigation', 'storefuse-bridge' ); ?></h1>

    <!-- ── Assigned Menus ────────────────────────────────────────────── -->
    <div class="sfb-card">
        <h2><?php esc_html_e( 'Navigation Menus', 'storefuse-bridge' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'StoreFuse reads nav menus from two registered theme locations. Assign menus in Appearance > Menus, then click Flush Navigation Cache to push the latest menu structure to your storefront.', 'storefuse-bridge' ); ?>
        </p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Header Menu', 'storefuse-bridge' ); ?></th>
                <td>
                    <?php if ( $header_menu ) : ?>
                        <strong><?php echo esc_html( $header_menu->name ); ?></strong>
                        <span class="description"> &mdash;
                            <a href="<?php echo esc_url( add_query_arg( 'menu', $header_menu_id, $menus_url ) ); ?>">
                                <?php esc_html_e( 'Edit', 'storefuse-bridge' ); ?>
                            </a>
                        </span>
                    <?php else : ?>
                        <span class="sfb-notice sfb-notice--warning">
                            <?php esc_html_e( 'No menu assigned to the "storefuse-header" location.', 'storefuse-bridge' ); ?>
                        </span>
                        <br>
                        <a href="<?php echo esc_url( $menus_url ); ?>" class="button button-secondary" style="margin-top:6px;">
                            <?php esc_html_e( 'Assign a Menu', 'storefuse-bridge' ); ?>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Footer Menu', 'storefuse-bridge' ); ?></th>
                <td>
                    <?php if ( $footer_menu ) : ?>
                        <strong><?php echo esc_html( $footer_menu->name ); ?></strong>
                        <span class="description"> &mdash;
                            <a href="<?php echo esc_url( add_query_arg( 'menu', $footer_menu_id, $menus_url ) ); ?>">
                                <?php esc_html_e( 'Edit', 'storefuse-bridge' ); ?>
                            </a>
                        </span>
                    <?php else : ?>
                        <span class="sfb-notice sfb-notice--warning">
                            <?php esc_html_e( 'No menu assigned to the "storefuse-footer" location.', 'storefuse-bridge' ); ?>
                        </span>
                        <br>
                        <a href="<?php echo esc_url( $menus_url ); ?>" class="button button-secondary" style="margin-top:6px;">
                            <?php esc_html_e( 'Assign a Menu', 'storefuse-bridge' ); ?>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- ── Cache Controls ────────────────────────────────────────────── -->
    <div class="sfb-card">
        <h2><?php esc_html_e( 'Navigation Cache', 'storefuse-bridge' ); ?></h2>
        <p><?php esc_html_e( 'Navigation data is cached for performance. Click below to flush the navigation cache and force the API to return the latest menu structure.', 'storefuse-bridge' ); ?></p>
        <button type="button" id="sfb-flush-nav-cache" class="button button-secondary">
            <?php esc_html_e( 'Flush Navigation Cache', 'storefuse-bridge' ); ?>
        </button>
        <span id="sfb-flush-nav-result" style="margin-left:10px;"></span>
    </div>

    <!-- ── Menu Location Registration Note ──────────────────────────── -->
    <div class="sfb-card">
        <h2><?php esc_html_e( 'Theme Location Reference', 'storefuse-bridge' ); ?></h2>
        <p><?php esc_html_e( 'StoreFuse Bridge registers the following nav menu locations:', 'storefuse-bridge' ); ?></p>
        <table class="widefat striped" style="max-width:600px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Location Slug', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'API Key', 'storefuse-bridge' ); ?></th>
                    <th><?php esc_html_e( 'Used For', 'storefuse-bridge' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>storefuse-header</code></td>
                    <td><code>header</code></td>
                    <td><?php esc_html_e( 'Main site navigation', 'storefuse-bridge' ); ?></td>
                </tr>
                <tr>
                    <td><code>storefuse-footer</code></td>
                    <td><code>footer</code></td>
                    <td><?php esc_html_e( 'Footer link columns', 'storefuse-bridge' ); ?></td>
                </tr>
            </tbody>
        </table>
        <p class="description" style="margin-top:10px;">
            <?php esc_html_e( 'These are served via GET /storefuse/v1/settings in the "navigation" key.', 'storefuse-bridge' ); ?>
        </p>
    </div>
</div>

<script>
( function () {
    var btn    = document.getElementById( 'sfb-flush-nav-cache' );
    var result = document.getElementById( 'sfb-flush-nav-result' );
    if ( ! btn ) return;
    btn.addEventListener( 'click', function () {
        btn.disabled = true;
        result.textContent = '<?php echo esc_js( __( 'Flushing…', 'storefuse-bridge' ) ); ?>';
        fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
            method  : 'POST',
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
            body    : 'action=storefuse_bridge_flush_cache&_ajax_nonce=<?php echo esc_js( wp_create_nonce( 'storefuse_bridge_flush_cache' ) ); ?>',
        } )
        .then( function ( r ) { return r.json(); } )
        .then( function ( data ) {
            result.textContent = data.success
                ? '<?php echo esc_js( __( 'Cache flushed.', 'storefuse-bridge' ) ); ?>'
                : '<?php echo esc_js( __( 'Error flushing cache.', 'storefuse-bridge' ) ); ?>';
        } )
        .catch( function () {
            result.textContent = '<?php echo esc_js( __( 'Request failed.', 'storefuse-bridge' ) ); ?>';
        } )
        .finally( function () { btn.disabled = false; } );
    } );
} )();
</script>
