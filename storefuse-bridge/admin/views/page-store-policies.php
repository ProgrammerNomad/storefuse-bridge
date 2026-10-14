<?php
defined( 'ABSPATH' ) || exit;

$settings = StoreFuse_Bridge_Settings::all();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Store Policies', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Store policy details', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'These values provide store information and display copy. They do not create or enforce a legal policy.', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_return_policy_days"><?php esc_html_e( 'Return policy days', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="number" id="sfb_return_policy_days" name="storefuse_bridge_settings[return_policy_days]" value="<?php echo esc_attr( (string) ( $settings['return_policy_days'] ?? 7 ) ); ?>" min="0" step="1" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Use 0 if you do not want a return period displayed.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_free_shipping_label"><?php esc_html_e( 'Free shipping label', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_free_shipping_label" name="storefuse_bridge_settings[free_shipping_threshold_label]" value="<?php echo esc_attr( $settings['free_shipping_threshold_label'] ?? '' ); ?>" class="regular-text" placeholder="Free shipping on orders over $50" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_free_shipping_amount"><?php esc_html_e( 'Free shipping threshold amount', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="number" id="sfb_free_shipping_amount" name="storefuse_bridge_settings[free_shipping_threshold_amount]" value="<?php echo esc_attr( (string) ( $settings['free_shipping_threshold_amount'] ?? 0 ) ); ?>" min="0" step="0.01" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Amount uses the store currency configured in WooCommerce. Enter 0 to disable the threshold display.', 'storefuse-bridge' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ); ?>"><?php esc_html_e( 'Review WooCommerce currency settings', 'storefuse-bridge' ); ?></a></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php submit_button( __( 'Save Store Policies', 'storefuse-bridge' ) ); ?>
    </form>
</div>
