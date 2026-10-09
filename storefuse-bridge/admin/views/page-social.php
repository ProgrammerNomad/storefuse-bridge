<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
$platforms = [
    'instagram' => [ 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/yourstore', 'type' => 'url' ],
    'facebook'  => [ 'label' => 'Facebook',  'placeholder' => 'https://facebook.com/yourstore', 'type' => 'url' ],
    'twitter'   => [ 'label' => 'Twitter / X', 'placeholder' => 'https://twitter.com/yourstore', 'type' => 'url' ],
    'youtube'   => [ 'label' => 'YouTube',   'placeholder' => 'https://youtube.com/@yourstore', 'type' => 'url' ],
    'pinterest' => [ 'label' => 'Pinterest', 'placeholder' => 'https://pinterest.com/yourstore', 'type' => 'url' ],
    'whatsapp'  => [ 'label' => 'WhatsApp',  'placeholder' => '+919876543210 or https://wa.me/…', 'type' => 'text' ],
];
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Social & Trust', 'storefuse-bridge' ); ?></h1>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Social media links', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'API: GET /settings → social_links. WhatsApp accepts a phone number (normalized to wa.me in the API).', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <?php foreach ( $platforms as $key => $cfg ) : ?>
                <tr>
                    <th scope="row"><label for="sfb_social_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $cfg['label'] ); ?></label></th>
                    <td>
                        <input type="<?php echo esc_attr( $cfg['type'] ); ?>" id="sfb_social_<?php echo esc_attr( $key ); ?>"
                               name="storefuse_bridge_settings[social_<?php echo esc_attr( $key ); ?>]"
                               value="<?php echo esc_attr( $s[ "social_{$key}" ] ?? '' ); ?>"
                               class="regular-text" placeholder="<?php echo esc_attr( $cfg['placeholder'] ); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Trust badges', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'API: GET /settings → trust_badges and GET /homepage → trust_items. Toggle each badge on or off.', 'storefuse-bridge' ); ?></p>

            <?php
            $badges = [];
            if ( isset( $s['trust_badges'] ) ) {
                $raw = is_string( $s['trust_badges'] ) ? json_decode( $s['trust_badges'], true ) : $s['trust_badges'];
                if ( is_array( $raw ) ) {
                    $badges = $raw;
                }
            }
            if ( empty( $badges ) ) {
                $badges = [
                    [ 'enabled' => true, 'icon' => '✓', 'title' => 'Free Shipping', 'description' => '' ],
                    [ 'enabled' => true, 'icon' => '↩', 'title' => 'Easy Returns', 'description' => '' ],
                    [ 'enabled' => true, 'icon' => '🔒', 'title' => 'Secure Payment', 'description' => '' ],
                    [ 'enabled' => true, 'icon' => '★', 'title' => 'Quality Guarantee', 'description' => '' ],
                ];
            }
            ?>

            <div id="sfb-trust-badges">
                <?php foreach ( $badges as $i => $badge ) :
                    $enabled = ! array_key_exists( 'enabled', $badge ) || ! empty( $badge['enabled'] );
                ?>
                <div class="sfb-trust-badge-row" data-index="<?php echo (int) $i; ?>">
                    <label class="sfb-badge-enabled" title="<?php esc_attr_e( 'Enabled', 'storefuse-bridge' ); ?>">
                        <input type="checkbox" class="sfb-badge-enabled-cb" <?php checked( $enabled ); ?> />
                    </label>
                    <input type="text" name="sfb_badges[<?php echo (int) $i; ?>][icon]" value="<?php echo esc_attr( $badge['icon'] ?? '' ); ?>" class="small-text" placeholder="Icon" />
                    <input type="text" name="sfb_badges[<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr( $badge['title'] ?? '' ); ?>" class="regular-text" placeholder="Title" />
                    <input type="text" name="sfb_badges[<?php echo (int) $i; ?>][description]" value="<?php echo esc_attr( $badge['description'] ?? '' ); ?>" class="regular-text" placeholder="Description" />
                    <button type="button" class="button sfb-remove-badge">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="sfb-add-badge" class="button button-secondary" style="margin-top:8px;"><?php esc_html_e( '+ Add badge', 'storefuse-bridge' ); ?></button>
            <input type="hidden" id="sfb-trust-badges-json" name="storefuse_bridge_settings[trust_badges]" value="<?php echo esc_attr( wp_json_encode( $badges ) ); ?>" />
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
