<?php
defined( 'ABSPATH' ) || exit;

/**
 * Per-request resolved context.
 *
 * Resolved once at the start of each REST request and shared across all modules.
 * Modules receive this object - they never call is_user_logged_in(), WC()->session,
 * get_woocommerce_currency(), etc. directly.
 */
class StoreFuse_Bridge_Request_Context {

    public readonly ?WP_User    $user;
    public readonly bool        $is_logged_in;
    public readonly string      $currency;
    public readonly string      $language;
    public readonly string      $device;
    public readonly string      $cart_token;

    private function __construct() {}

    /**
     * Build a context object from the current request.
     */
    public static function from_request( WP_REST_Request $request ): self {
        $ctx = new self();

        $current_user       = wp_get_current_user();
        $ctx->is_logged_in  = is_user_logged_in();
        $ctx->user          = $ctx->is_logged_in ? $current_user : null;
        $ctx->currency      = get_woocommerce_currency();
        $ctx->language      = self::resolve_language();
        $ctx->device        = self::detect_device( $request );
        $ctx->cart_token    = ( WC()->session instanceof WC_Session )
            ? (string) WC()->session->get_customer_id()
            : '';

        return $ctx;
    }

    // ── Helpers 

    private static function resolve_language(): string {
        // WPML support
        if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
            return (string) ICL_LANGUAGE_CODE;
        }
        // Polylang support
        if ( function_exists( 'pll_current_language' ) ) {
            return (string) pll_current_language( 'slug' );
        }
        // Fall back to WordPress locale (e.g. 'en_US' → 'en')
        $locale = get_locale();
        return strtolower( substr( $locale, 0, 2 ) );
    }

    private static function detect_device( WP_REST_Request $request ): string {
        $ua = (string) ( $request->get_header( 'User-Agent' ) ?? '' );
        if ( $ua === '' ) {
            return 'unknown';
        }
        if ( stripos( $ua, 'Mobile' ) !== false || stripos( $ua, 'Android' ) !== false ) {
            return 'mobile';
        }
        if ( stripos( $ua, 'Tablet' ) !== false || stripos( $ua, 'iPad' ) !== false ) {
            return 'tablet';
        }
        return 'desktop';
    }
}
