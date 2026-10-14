<?php
defined( 'ABSPATH' ) || exit;

$settings = StoreFuse_Bridge_Settings::all();
$logo_id  = get_theme_mod( 'custom_logo' );
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : null;
$favicon  = get_site_icon_url();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Branding', 'storefuse-bridge' ); ?></h1>
    <p class="notice notice-info inline sfb-context-notice">
        <?php esc_html_e( 'Return policy and free-shipping details are managed separately.', 'storefuse-bridge' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-policies' ) ); ?>"><?php esc_html_e( 'Open Store Policies', 'storefuse-bridge' ); ?></a>
    </p>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
        <div class="sfb-card">
            <h2><?php esc_html_e( 'App identity', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'These values are served to connected clients through GET /settings.', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_app_primary_color"><?php esc_html_e( 'Primary app color', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_app_primary_color" name="storefuse_bridge_settings[app_primary_color]" value="<?php echo esc_attr( $settings['app_primary_color'] ?? '#E85D04' ); ?>" class="sfb-color-picker" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_support_url"><?php esc_html_e( 'Support URL', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="url" id="sfb_app_support_url" name="storefuse_bridge_settings[app_support_url]" value="<?php echo esc_attr( $settings['app_support_url'] ?? '' ); ?>" class="regular-text" placeholder="https://example.com/support" /></td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Announcement bar', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'The announcement is available to all clients. Homepage merchandising remains on the Homepage screen.', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Visibility', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="hidden" name="storefuse_bridge_settings[announcement_bar_enabled]" value="0" />
                        <label for="sfb_announcement_enabled"><input type="checkbox" id="sfb_announcement_enabled" name="storefuse_bridge_settings[announcement_bar_enabled]" value="1" <?php checked( ! empty( $settings['announcement_bar_enabled'] ) ); ?> /> <?php esc_html_e( 'Show announcement bar', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_announcement_text"><?php esc_html_e( 'Announcement text', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_announcement_text" name="storefuse_bridge_settings[announcement_bar_text]" value="<?php echo esc_attr( $settings['announcement_bar_text'] ?? '' ); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_announcement_color"><?php esc_html_e( 'Background color', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_announcement_color" name="storefuse_bridge_settings[announcement_bar_bg_color]" value="<?php echo esc_attr( $settings['announcement_bar_bg_color'] ?? '#E85D04' ); ?>" class="sfb-color-picker" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_announcement_link"><?php esc_html_e( 'Link URL', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="url" id="sfb_announcement_link" name="storefuse_bridge_settings[announcement_bar_link]" value="<?php echo esc_attr( $settings['announcement_bar_link'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Branding', 'storefuse-bridge' ) ); ?>
    </form>

    <div class="sfb-card">
        <h2><?php esc_html_e( 'WordPress site identity', 'storefuse-bridge' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Site title, tagline, logo, and favicon are managed by WordPress Customizer and are not copied into Bridge settings.', 'storefuse-bridge' ); ?></p>
        <p>
            <a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>"><?php esc_html_e( 'Edit site title and tagline', 'storefuse-bridge' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[control]=custom_logo' ) ); ?>"><?php esc_html_e( 'Edit logo', 'storefuse-bridge' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[control]=site_icon' ) ); ?>"><?php esc_html_e( 'Edit favicon', 'storefuse-bridge' ); ?></a>
        </p>
        <table class="widefat striped sfb-site-identity">
            <tbody>
                <tr><th scope="row"><?php esc_html_e( 'Site name', 'storefuse-bridge' ); ?></th><td><?php echo esc_html( get_bloginfo( 'name' ) ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( 'Tagline', 'storefuse-bridge' ); ?></th><td><?php echo esc_html( get_bloginfo( 'description' ) ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( 'Logo URL', 'storefuse-bridge' ); ?></th><td><?php echo $logo_url ? '<code>' . esc_html( $logo_url ) . '</code>' : '<em>' . esc_html__( 'No logo set', 'storefuse-bridge' ) . '</em>'; ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( 'Favicon URL', 'storefuse-bridge' ); ?></th><td><?php echo $favicon ? '<code>' . esc_html( $favicon ) . '</code>' : '<em>' . esc_html__( 'No site icon set', 'storefuse-bridge' ) . '</em>'; ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
