<?php defined( 'ABSPATH' ) || exit;
$s    = StoreFuse_Bridge_Settings::all();
$mode = $s['checkout_mode'] ?? 'redirect';

// Build a list of active payment gateways for the headless-mode info panel.
$gateways = [];
if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
    foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
        $gateways[] = $gateway->get_title();
    }
}
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Checkout', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <!-- ── Checkout Mode ─────────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Checkout Mode', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Choose how checkout is handled. "Redirect" sends customers to the native WooCommerce checkout page. "Headless" keeps the customer on the Next.js storefront throughout.', 'storefuse-bridge' ); ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Mode', 'storefuse-bridge' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                       name="storefuse_bridge_settings[checkout_mode]"
                                       value="redirect"
                                       id="sfb-mode-redirect"
                                       <?php checked( $mode, 'redirect' ); ?> />
                                <?php esc_html_e( 'Redirect — Send customers to the native WooCommerce checkout', 'storefuse-bridge' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio"
                                       name="storefuse_bridge_settings[checkout_mode]"
                                       value="headless"
                                       id="sfb-mode-headless"
                                       <?php checked( $mode, 'headless' ); ?> />
                                <?php esc_html_e( 'Headless — Handle checkout entirely within the Next.js storefront', 'storefuse-bridge' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ── Redirect Mode Settings ────────────────────────────────── -->
        <div class="sfb-card" id="sfb-panel-redirect">
            <h2><?php esc_html_e( 'Redirect Mode Settings', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sfb-checkout-redirect-label">
                            <?php esc_html_e( 'Checkout Button Label', 'storefuse-bridge' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text"
                               id="sfb-checkout-redirect-label"
                               name="storefuse_bridge_settings[checkout_redirect_label]"
                               value="<?php echo esc_attr( $s['checkout_redirect_label'] ?? 'Proceed to Checkout' ); ?>"
                               class="regular-text" />
                        <p class="description">
                            <?php esc_html_e( 'Label shown on the cart page "Proceed to Checkout" button. Defaults to "Proceed to Checkout".', 'storefuse-bridge' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sfb-checkout-page-url">
                            <?php esc_html_e( 'Checkout Page URL Override', 'storefuse-bridge' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="url"
                               id="sfb-checkout-page-url"
                               name="storefuse_bridge_settings[checkout_page_url]"
                               value="<?php echo esc_attr( $s['checkout_page_url'] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( wc_get_checkout_url() ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Optional. Override the WooCommerce checkout URL returned by POST /checkout/redirect-url. Leave empty to use the default WooCommerce checkout page.', 'storefuse-bridge' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ── Headless Mode Settings ────────────────────────────────── -->
        <div class="sfb-card" id="sfb-panel-headless">
            <h2><?php esc_html_e( 'Headless Mode Settings', 'storefuse-bridge' ); ?></h2>

            <div class="notice notice-warning inline" style="margin-bottom:16px;">
                <p>
                    <strong><?php esc_html_e( 'Compatibility Note:', 'storefuse-bridge' ); ?></strong>
                    <?php esc_html_e( 'Headless checkout requires that your payment gateways support the WooCommerce Store API (Block Checkout). Gateways that rely on redirect-based flows or custom payment pages may not work correctly in headless mode.', 'storefuse-bridge' ); ?>
                </p>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Active Payment Gateways', 'storefuse-bridge' ); ?></th>
                    <td>
                        <?php if ( $gateways ) : ?>
                            <ul style="margin:0;padding:0;list-style:disc inside;">
                                <?php foreach ( $gateways as $gw ) : ?>
                                    <li><?php echo esc_html( $gw ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="description">
                                <?php esc_html_e( 'Verify each gateway supports Block Checkout before enabling headless mode.', 'storefuse-bridge' ); ?>
                            </p>
                        <?php else : ?>
                            <p class="description">
                                <?php esc_html_e( 'No active payment gateways found. Configure payment methods in WooCommerce > Settings > Payments.', 'storefuse-bridge' ); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>

<script>
( function () {
    var radios     = document.querySelectorAll( 'input[name="storefuse_bridge_settings[checkout_mode]"]' );
    var panelRedir = document.getElementById( 'sfb-panel-redirect' );
    var panelHead  = document.getElementById( 'sfb-panel-headless' );

    function toggle() {
        var val = document.querySelector( 'input[name="storefuse_bridge_settings[checkout_mode]"]:checked' );
        if ( ! val ) return;
        panelRedir.style.display = ( val.value === 'redirect'  ) ? '' : 'none';
        panelHead.style.display  = ( val.value === 'headless' ) ? '' : 'none';
    }

    radios.forEach( function ( r ) { r.addEventListener( 'change', toggle ); } );
    toggle();
} )();
</script>
