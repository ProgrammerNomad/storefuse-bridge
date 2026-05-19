<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Homepage', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <!-- ── Announcement Bar (mirror of general, for convenience) ── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Announcement Bar', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Also configurable in General Settings.', 'storefuse-bridge' ); ?>
            </p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="checkbox" name="storefuse_bridge_settings[announcement_bar_enabled]" value="1"
                               <?php checked( ! empty( $s['announcement_bar_enabled'] ) ); ?> />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hp_bar_text"><?php esc_html_e( 'Text', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_hp_bar_text" name="storefuse_bridge_settings[announcement_bar_text]"
                               value="<?php echo esc_attr( $s['announcement_bar_text'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>
            </table>
        </div>

        <!-- ── Hero Section ────────────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Hero Section', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="sfb_hero_badge"><?php esc_html_e( 'Badge Text', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_hero_badge" name="storefuse_bridge_settings[hero_badge_text]"
                               value="<?php echo esc_attr( $s['hero_badge_text'] ?? '' ); ?>"
                               class="regular-text" placeholder="🪔 New Festive Collection 2026" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_headline"><?php esc_html_e( 'Headline', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_hero_headline" name="storefuse_bridge_settings[hero_headline]"
                               value="<?php echo esc_attr( $s['hero_headline'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_highlight"><?php esc_html_e( 'Headline Highlight', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_hero_highlight" name="storefuse_bridge_settings[hero_headline_highlight]"
                               value="<?php echo esc_attr( $s['hero_headline_highlight'] ?? '' ); ?>"
                               class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Exact portion of the headline to display in the accent colour.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_sub"><?php esc_html_e( 'Subheadline', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <textarea id="sfb_hero_sub" name="storefuse_bridge_settings[hero_subheadline]"
                                  class="large-text" rows="2"><?php echo esc_textarea( $s['hero_subheadline'] ?? '' ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Primary CTA', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[hero_cta_primary_label]"
                               value="<?php echo esc_attr( $s['hero_cta_primary_label'] ?? 'Shop Now' ); ?>"
                               class="regular-text" placeholder="Label" style="margin-bottom:4px;" />
                        <input type="text" name="storefuse_bridge_settings[hero_cta_primary_href]"
                               value="<?php echo esc_attr( $s['hero_cta_primary_href'] ?? '/shop' ); ?>"
                               class="regular-text" placeholder="/shop" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Secondary CTA', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[hero_cta_secondary_label]"
                               value="<?php echo esc_attr( $s['hero_cta_secondary_label'] ?? '' ); ?>"
                               class="regular-text" placeholder="Label (optional)" style="margin-bottom:4px;" />
                        <input type="text" name="storefuse_bridge_settings[hero_cta_secondary_href]"
                               value="<?php echo esc_attr( $s['hero_cta_secondary_href'] ?? '' ); ?>"
                               class="regular-text" placeholder="/shop?sort=newest" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Hero Image', 'storefuse-bridge' ); ?></th>
                    <td>
                        <?php
                        $hero_id  = (int) ( $s['hero_image_id'] ?? 0 );
                        $hero_url = $hero_id ? wp_get_attachment_image_url( $hero_id, 'medium' ) : null;
                        ?>
                        <?php if ( $hero_url ) : ?>
                            <img id="sfb-hero-preview" src="<?php echo esc_url( $hero_url ); ?>"
                                 style="max-width:240px;display:block;margin-bottom:8px;" />
                        <?php else : ?>
                            <img id="sfb-hero-preview" src="" style="max-width:240px;display:none;margin-bottom:8px;" />
                        <?php endif; ?>
                        <input type="hidden" id="sfb-hero-image-id" name="storefuse_bridge_settings[hero_image_id]"
                               value="<?php echo esc_attr( $hero_id ?: '' ); ?>" />
                        <button type="button" id="sfb-hero-upload" class="button">
                            <?php esc_html_e( 'Select Image', 'storefuse-bridge' ); ?>
                        </button>
                        <?php if ( $hero_id ) : ?>
                            <button type="button" id="sfb-hero-remove" class="button button-link-delete" style="margin-left:6px;">
                                <?php esc_html_e( 'Remove', 'storefuse-bridge' ); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_rating"><?php esc_html_e( 'Rating Text', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_hero_rating" name="storefuse_bridge_settings[hero_rating_text]"
                               value="<?php echo esc_attr( $s['hero_rating_text'] ?? '' ); ?>"
                               class="regular-text" placeholder="4.8/5 from 2,400+ reviews" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_shipping"><?php esc_html_e( 'Shipping Text', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_hero_shipping" name="storefuse_bridge_settings[hero_shipping_text]"
                               value="<?php echo esc_attr( $s['hero_shipping_text'] ?? '' ); ?>"
                               class="regular-text" placeholder="Free shipping over ₹999" />
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
