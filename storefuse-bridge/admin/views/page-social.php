<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$platforms = [
    'instagram' => [ 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/yourstore' ],
    'facebook'  => [ 'label' => 'Facebook',  'placeholder' => 'https://facebook.com/yourstore' ],
    'twitter'   => [ 'label' => 'Twitter / X', 'placeholder' => 'https://twitter.com/yourstore' ],
    'youtube'   => [ 'label' => 'YouTube',   'placeholder' => 'https://youtube.com/@yourstore' ],
    'pinterest' => [ 'label' => 'Pinterest', 'placeholder' => 'https://pinterest.com/yourstore' ],
    'whatsapp'  => [ 'label' => 'WhatsApp',  'placeholder' => 'https://wa.me/919876543210' ],
];
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Social & Trust', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <!-- ── Social Links ─────────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Social Media Links', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Leave blank to hide a platform link from the storefront footer.', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <?php foreach ( $platforms as $key => $cfg ) : ?>
                <tr>
                    <th><label for="sfb_social_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $cfg['label'] ); ?></label></th>
                    <td>
                        <input type="url" id="sfb_social_<?php echo esc_attr( $key ); ?>"
                               name="storefuse_bridge_settings[social_<?php echo esc_attr( $key ); ?>]"
                               value="<?php echo esc_attr( $s[ "social_{$key}" ] ?? '' ); ?>"
                               class="regular-text" placeholder="<?php echo esc_attr( $cfg['placeholder'] ); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- ── Trust Badges ─────────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Trust Badges', 'storefuse-bridge' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Shown below the hero section and on the product page. Each badge has an icon (emoji or text), a title, and a description.', 'storefuse-bridge' ); ?>
            </p>

            <?php
            $badges = [];
            if ( isset( $s['trust_badges'] ) ) {
                $raw = is_string( $s['trust_badges'] ) ? json_decode( $s['trust_badges'], true ) : $s['trust_badges'];
                if ( is_array( $raw ) ) {
                    $badges = $raw;
                }
            }
            if ( empty( $badges ) ) {
                // Default 4 badges for first setup
                $badges = [
                    [ 'icon' => '', 'title' => 'Free Shipping', 'description' => '' ],
                    [ 'icon' => '', 'title' => 'Easy Returns',  'description' => '' ],
                    [ 'icon' => '', 'title' => 'Secure Payment', 'description' => '' ],
                    [ 'icon' => '', 'title' => 'Quality Guarantee', 'description' => '' ],
                ];
            }
            ?>

            <div id="sfb-trust-badges">
                <?php foreach ( $badges as $i => $badge ) : ?>
                <div class="sfb-trust-badge-row" data-index="<?php echo (int) $i; ?>">
                    <input type="text" name="sfb_badges[<?php echo (int) $i; ?>][icon]"
                           value="<?php echo esc_attr( $badge['icon'] ?? '' ); ?>"
                           class="small-text" placeholder="Icon" title="Icon" />
                    <input type="text" name="sfb_badges[<?php echo (int) $i; ?>][title]"
                           value="<?php echo esc_attr( $badge['title'] ?? '' ); ?>"
                           class="regular-text" placeholder="Title" title="Title" />
                    <input type="text" name="sfb_badges[<?php echo (int) $i; ?>][description]"
                           value="<?php echo esc_attr( $badge['description'] ?? '' ); ?>"
                           class="regular-text" placeholder="Description" title="Description" />
                    <button type="button" class="button sfb-remove-badge">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="sfb-add-badge" class="button button-secondary" style="margin-top:8px;">
                <?php esc_html_e( '+ Add Badge', 'storefuse-bridge' ); ?>
            </button>

            <!-- Hidden field that stores badges as JSON -->
            <input type="hidden" id="sfb-trust-badges-json" name="storefuse_bridge_settings[trust_badges]"
                   value="<?php echo esc_attr( json_encode( $badges, JSON_UNESCAPED_UNICODE ) ); ?>" />
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
