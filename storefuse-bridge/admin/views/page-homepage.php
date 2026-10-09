<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$featured = [];
if ( ! empty( $s['featured_categories'] ) ) {
    $raw = is_string( $s['featured_categories'] ) ? json_decode( $s['featured_categories'], true ) : $s['featured_categories'];
    if ( is_array( $raw ) ) {
        $featured = $raw;
    }
}
$product_cats = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'number'     => 200,
    'orderby'    => 'name',
] );
if ( is_wp_error( $product_cats ) ) {
    $product_cats = [];
}
$api_home = esc_url( get_site_url() . '/wp-json/storefuse/v1/homepage' );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Homepage', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <p class="description">
        <?php
        printf(
            wp_kses_post( __( 'Announcement bar is configured under <a href="%s">General</a> only (single source). After saving, <a href="%s" target="_blank" rel="noopener">view GET /homepage</a>.', 'storefuse-bridge' ) ),
            esc_url( admin_url( 'admin.php?page=storefuse-bridge-general' ) ),
            $api_home
        );
        ?>
    </p>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Hero section', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'API: GET /homepage → hero', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="sfb_hero_badge"><?php esc_html_e( 'Badge text', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_hero_badge" name="storefuse_bridge_settings[hero_badge_text]" value="<?php echo esc_attr( $s['hero_badge_text'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_headline"><?php esc_html_e( 'Headline', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_hero_headline" name="storefuse_bridge_settings[hero_headline]" value="<?php echo esc_attr( $s['hero_headline'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_highlight"><?php esc_html_e( 'Headline highlight', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_hero_highlight" name="storefuse_bridge_settings[hero_headline_highlight]" value="<?php echo esc_attr( $s['hero_headline_highlight'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_sub"><?php esc_html_e( 'Subheadline', 'storefuse-bridge' ); ?></label></th>
                    <td><textarea id="sfb_hero_sub" name="storefuse_bridge_settings[hero_subheadline]" class="large-text" rows="2"><?php echo esc_textarea( $s['hero_subheadline'] ?? '' ); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Primary CTA', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[hero_cta_primary_label]" value="<?php echo esc_attr( $s['hero_cta_primary_label'] ?? 'Shop Now' ); ?>" class="regular-text" placeholder="Label" /><br>
                        <input type="text" name="storefuse_bridge_settings[hero_cta_primary_href]" value="<?php echo esc_attr( $s['hero_cta_primary_href'] ?? '/shop' ); ?>" class="regular-text" placeholder="/shop" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Secondary CTA', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[hero_cta_secondary_label]" value="<?php echo esc_attr( $s['hero_cta_secondary_label'] ?? '' ); ?>" class="regular-text" /><br>
                        <input type="text" name="storefuse_bridge_settings[hero_cta_secondary_href]" value="<?php echo esc_attr( $s['hero_cta_secondary_href'] ?? '' ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Hero image', 'storefuse-bridge' ); ?></th>
                    <td>
                        <?php
                        $hero_id  = (int) ( $s['hero_image_id'] ?? 0 );
                        $hero_url = $hero_id ? wp_get_attachment_image_url( $hero_id, 'medium' ) : null;
                        ?>
                        <img id="sfb-hero-preview" src="<?php echo esc_url( $hero_url ?: '' ); ?>" style="max-width:240px;<?php echo $hero_url ? '' : 'display:none;'; ?>margin-bottom:8px;" alt="" />
                        <input type="hidden" id="sfb-hero-image-id" name="storefuse_bridge_settings[hero_image_id]" value="<?php echo esc_attr( $hero_id ?: '' ); ?>" />
                        <button type="button" id="sfb-hero-upload" class="button"><?php esc_html_e( 'Select image', 'storefuse-bridge' ); ?></button>
                        <button type="button" id="sfb-hero-remove" class="button button-link-delete" style="<?php echo $hero_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'storefuse-bridge' ); ?></button>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_rating"><?php esc_html_e( 'Rating text', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_hero_rating" name="storefuse_bridge_settings[hero_rating_text]" value="<?php echo esc_attr( $s['hero_rating_text'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_hero_shipping"><?php esc_html_e( 'Shipping text', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_hero_shipping" name="storefuse_bridge_settings[hero_shipping_text]" value="<?php echo esc_attr( $s['hero_shipping_text'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Featured categories', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Up to 6 categories. API: GET /homepage → featured_categories. Icons use product_cat term meta storefuse_icon unless overridden.', 'storefuse-bridge' ); ?></p>
            <div id="sfb-featured-categories">
                <?php
                if ( empty( $featured ) ) {
                    $featured = [ [ 'category_id' => '', 'label' => '', 'icon' => '', 'color' => '' ] ];
                }
                foreach ( $featured as $i => $row ) :
                ?>
                <div class="sfb-featured-cat-row" data-index="<?php echo (int) $i; ?>">
                    <select class="sfb-cat-select" title="<?php esc_attr_e( 'Category', 'storefuse-bridge' ); ?>">
                        <option value=""><?php esc_html_e( '- Select category -', 'storefuse-bridge' ); ?></option>
                        <?php foreach ( $product_cats as $term ) :
                            if ( $term->slug === 'uncategorized' ) {
                                continue;
                            }
                            $sel = (int) ( $row['category_id'] ?? 0 ) === (int) $term->term_id;
                        ?>
                        <option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $sel ); ?>><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="sfb-cat-label regular-text" placeholder="<?php esc_attr_e( 'Label override', 'storefuse-bridge' ); ?>" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" />
                    <input type="text" class="sfb-cat-icon small-text" placeholder="<?php esc_attr_e( 'Icon', 'storefuse-bridge' ); ?>" value="<?php echo esc_attr( $row['icon'] ?? '' ); ?>" />
                    <input type="text" class="sfb-cat-color sfb-color-picker" placeholder="#hex" value="<?php echo esc_attr( $row['color'] ?? '' ); ?>" />
                    <button type="button" class="button sfb-remove-featured-cat">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="sfb-add-featured-cat" class="button button-secondary" style="margin-top:8px;"><?php esc_html_e( '+ Add category', 'storefuse-bridge' ); ?></button>
            <input type="hidden" id="sfb-featured-categories-json" name="storefuse_bridge_settings[featured_categories]" value="<?php echo esc_attr( wp_json_encode( $featured ) ); ?>" />
            <template id="sfb-featured-cat-template">
                <div class="sfb-featured-cat-row">
                    <select class="sfb-cat-select"><option value=""><?php esc_html_e( '- Select category -', 'storefuse-bridge' ); ?></option>
                    <?php foreach ( $product_cats as $term ) :
                        if ( $term->slug === 'uncategorized' ) {
                            continue;
                        }
                    ?>
                    <option value="<?php echo esc_attr( (string) $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                    </select>
                    <input type="text" class="sfb-cat-label regular-text" placeholder="<?php esc_attr_e( 'Label override', 'storefuse-bridge' ); ?>" />
                    <input type="text" class="sfb-cat-icon small-text" placeholder="<?php esc_attr_e( 'Icon', 'storefuse-bridge' ); ?>" />
                    <input type="text" class="sfb-cat-color sfb-color-picker" placeholder="#hex" />
                    <button type="button" class="button sfb-remove-featured-cat">✕</button>
                </div>
            </template>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Product sections', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'API: GET /homepage → best_sellers, new_arrivals, promo_banner', 'storefuse-bridge' ); ?></p>
            <h3><?php esc_html_e( 'Best sellers', 'storefuse-bridge' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="sfb_bs_heading"><?php esc_html_e( 'Heading', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_bs_heading" name="storefuse_bridge_settings[homepage_best_sellers_heading]" value="<?php echo esc_attr( $s['homepage_best_sellers_heading'] ?? 'Best Sellers' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_bs_source"><?php esc_html_e( 'Source', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <select id="sfb_bs_source" name="storefuse_bridge_settings[homepage_best_sellers_source]">
                            <?php foreach ( [ 'best-selling' => 'Best selling', 'featured' => 'Featured products', 'manual' => 'Manual product IDs' ] as $val => $lab ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $s['homepage_best_sellers_source'] ?? 'best-selling', $val ); ?>><?php echo esc_html( $lab ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_bs_count"><?php esc_html_e( 'Count', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="number" id="sfb_bs_count" name="storefuse_bridge_settings[homepage_best_sellers_count]" value="<?php echo esc_attr( $s['homepage_best_sellers_count'] ?? 8 ); ?>" min="1" max="24" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_bs_ids"><?php esc_html_e( 'Manual IDs', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_bs_ids" name="storefuse_bridge_settings[homepage_best_sellers_ids]" value="<?php echo esc_attr( $s['homepage_best_sellers_ids'] ?? '' ); ?>" class="regular-text" placeholder="12,34,56" /></td>
                </tr>
            </table>
            <h3><?php esc_html_e( 'New arrivals', 'storefuse-bridge' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="sfb_na_heading"><?php esc_html_e( 'Heading', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_na_heading" name="storefuse_bridge_settings[homepage_new_arrivals_heading]" value="<?php echo esc_attr( $s['homepage_new_arrivals_heading'] ?? 'New Arrivals' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_na_count"><?php esc_html_e( 'Count', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="number" id="sfb_na_count" name="storefuse_bridge_settings[homepage_new_arrivals_count]" value="<?php echo esc_attr( $s['homepage_new_arrivals_count'] ?? 8 ); ?>" min="1" max="24" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_na_cat"><?php esc_html_e( 'Category slug filter', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_na_cat" name="storefuse_bridge_settings[homepage_new_arrivals_category]" value="<?php echo esc_attr( $s['homepage_new_arrivals_category'] ?? '' ); ?>" class="regular-text" placeholder="optional" /></td>
                </tr>
            </table>
            <h3><?php esc_html_e( 'Promo banner', 'storefuse-bridge' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?></th>
                    <td><input type="checkbox" name="storefuse_bridge_settings[homepage_promo_banner_enabled]" value="1" <?php checked( ! empty( $s['homepage_promo_banner_enabled'] ) ); ?> /></td>
                </tr>
                <tr>
                    <th><label for="sfb_promo_headline"><?php esc_html_e( 'Headline', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_promo_headline" name="storefuse_bridge_settings[homepage_promo_banner_headline]" value="<?php echo esc_attr( $s['homepage_promo_banner_headline'] ?? '' ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="sfb_promo_body"><?php esc_html_e( 'Body', 'storefuse-bridge' ); ?></label></th>
                    <td><textarea id="sfb_promo_body" name="storefuse_bridge_settings[homepage_promo_banner_body]" class="large-text" rows="3"><?php echo esc_textarea( $s['homepage_promo_banner_body'] ?? '' ); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'CTA', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[homepage_promo_banner_cta_label]" value="<?php echo esc_attr( $s['homepage_promo_banner_cta_label'] ?? '' ); ?>" class="regular-text" placeholder="Label" /><br>
                        <input type="text" name="storefuse_bridge_settings[homepage_promo_banner_cta_href]" value="<?php echo esc_attr( $s['homepage_promo_banner_cta_href'] ?? '' ); ?>" class="regular-text" placeholder="/shop" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_promo_color"><?php esc_html_e( 'Background colour', 'storefuse-bridge' ); ?></label></th>
                    <td><input type="text" id="sfb_promo_color" name="storefuse_bridge_settings[homepage_promo_banner_bg_color]" value="<?php echo esc_attr( $s['homepage_promo_banner_bg_color'] ?? '#1e293b' ); ?>" class="sfb-color-picker" /></td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
