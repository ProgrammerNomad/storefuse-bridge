<?php
/**
 * ISR Revalidation Webhooks Module
 *
 * Fires HTTP requests to the Next.js revalidation endpoint whenever WordPress/WooCommerce
 * data changes that the storefront caches. Supports product updates, category changes,
 * settings saves, and nav menu changes.
 *
 * Enabled via the `module_webhooks_enabled` setting and requires `storefront_url` and
 * `revalidation_secret` to be configured.
 *
 * @package StoreFuse_Bridge
 */

defined( 'ABSPATH' ) || exit;

class StoreFuse_Bridge_Module_Webhooks extends StoreFuse_Bridge_Module {

    /**
     * Module identifier.
     *
     * @var string
     */
    protected string $id = 'webhooks';

    /**
     * Constructor - registers WordPress action hooks when the module is enabled.
     *
     * Hooks are registered here (not in register_routes) because this module does not
     * expose REST endpoints; it only reacts to internal WP/WC data-change events.
     */
    public function __construct() {
        if ( $this->is_enabled() ) {
            $this->register_hooks();
        }
    }

    /**
     * No REST routes for this module.
     */
    public function register_routes(): void {
        // Intentionally empty. Webhooks are outgoing only.
    }

    /**
     * Bind to WP/WC lifecycle events.
     */
    private function register_hooks(): void {
        add_action( 'woocommerce_update_product',     [ $this, 'on_product_updated' ],  10, 1 );
        add_action( 'woocommerce_update_product_cat', [ $this, 'on_category_updated' ], 10, 1 );
        add_action( 'storefuse_bridge_settings_updated', [ $this, 'on_settings_updated' ] );
        add_action( 'wp_update_nav_menu',             [ $this, 'on_nav_updated' ] );
    }

    // ── Event Handlers ────────────────────────────────────────────────

    /**
     * Triggered when a product is saved/updated in WooCommerce.
     *
     * @param int $product_id
     */
    public function on_product_updated( int $product_id ): void {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }
        $this->send_revalidation( 'product', $product->get_slug() );
    }

    /**
     * Triggered when a product category term is updated.
     *
     * @param int $term_id
     */
    public function on_category_updated( int $term_id ): void {
        $term = get_term( $term_id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }
        $this->send_revalidation( 'category', $term->slug );
    }

    /**
     * Triggered when StoreFuse Bridge settings are saved.
     * Requests a full revalidation of all storefront pages.
     */
    public function on_settings_updated(): void {
        $this->send_revalidation( 'settings', '' );
    }

    /**
     * Triggered when any WordPress nav menu is updated.
     * Requests revalidation of the navigation data.
     */
    public function on_nav_updated(): void {
        $this->send_revalidation( 'navigation', '' );
    }

    // ── Revalidation ──────────────────────────────────────────────────

    /**
     * POST a revalidation request to the Next.js storefront.
     *
     * Uses non-blocking fire-and-forget (`'blocking' => false`).
     * The log entry is written regardless of whether the request succeeded so that
     * the admin can see attempted deliveries even when the storefront is unreachable.
     *
     * @param string $type  Payload type: product | category | settings | navigation
     * @param string $slug  Resource slug (empty for non-resource payloads like settings).
     */
    private function send_revalidation( string $type, string $slug ): void {
        $storefront_url = StoreFuse_Bridge_Settings::get( 'storefront_url', '' );
        $secret         = StoreFuse_Bridge_Settings::get( 'revalidation_secret', '' );

        if ( ! $storefront_url || ! $secret ) {
            // Not configured - silently bail.
            return;
        }

        $endpoint = trailingslashit( esc_url_raw( $storefront_url ) ) . 'api/revalidate';

        // Do NOT include the secret in the body - authenticate via HMAC signature header instead.
        $body    = wp_json_encode( [
            'type' => $type,
            'slug' => $slug,
        ] );
        $payload = (string) $body;

        // HMAC-SHA256 signature: Next.js verifies with crypto.timingSafeEqual against the same secret.
        $signature = hash_hmac( 'sha256', $payload, $secret );

        $response = wp_remote_post( $endpoint, [
            'headers'  => [
                'Content-Type'         => 'application/json',
                'X-StoreFuse-Signature' => $signature,
            ],
            'body'     => $payload,
            'timeout'  => 5,
            'blocking' => false, // fire-and-forget; do not block the WP request
        ] );

        $this->log_delivery( $type, $slug, $response );
    }

    // ── Delivery Log ──────────────────────────────────────────────────

    /**
     * Append one entry to the webhook delivery log (stored in wp_options).
     *
     * Keeps the last 20 entries only.
     *
     * @param string                    $type     Revalidation type.
     * @param string                    $slug     Resource slug.
     * @param array<mixed>|WP_Error     $response wp_remote_post() return value.
     */
    private function log_delivery( string $type, string $slug, array|WP_Error $response ): void {
        $log = (array) get_option( 'storefuse_bridge_webhook_log', [] );

        array_unshift( $log, [
            'time'   => current_time( 'mysql' ),
            'type'   => $type,
            'slug'   => $slug,
            'status' => is_wp_error( $response )
                ? 'error'
                : (string) wp_remote_retrieve_response_code( $response ),
            'error'  => is_wp_error( $response ) ? $response->get_error_message() : '',
        ] );

        update_option( 'storefuse_bridge_webhook_log', array_slice( $log, 0, 20 ), false );
    }
}
