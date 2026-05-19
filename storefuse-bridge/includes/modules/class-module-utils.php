<?php
defined( 'ABSPATH' ) || exit;

/**
 * Utils Module
 *
 * Routes (public, long cache):
 *   GET /storefuse/v1/utils/countries               - WC allowed countries + states + calling codes
 *   GET /storefuse/v1/utils/pincode/{pincode}        - serviceability check for a postal code
 */
class StoreFuse_Bridge_Module_Utils extends StoreFuse_Bridge_Module {

    protected string $id = 'utils';

    /** Minimal E.164-ish calling code map. Extendable via filter. */
    private const CALLING_CODES = [
        'AF' => '+93',  'AL' => '+355', 'DZ' => '+213', 'AD' => '+376', 'AO' => '+244',
        'AR' => '+54',  'AM' => '+374', 'AU' => '+61',  'AT' => '+43',  'AZ' => '+994',
        'BH' => '+973', 'BD' => '+880', 'BY' => '+375', 'BE' => '+32',  'BZ' => '+501',
        'BJ' => '+229', 'BT' => '+975', 'BO' => '+591', 'BA' => '+387', 'BW' => '+267',
        'BR' => '+55',  'BN' => '+673', 'BG' => '+359', 'BF' => '+226', 'BI' => '+257',
        'KH' => '+855', 'CM' => '+237', 'CA' => '+1',   'CV' => '+238', 'CF' => '+236',
        'TD' => '+235', 'CL' => '+56',  'CN' => '+86',  'CO' => '+57',  'KM' => '+269',
        'CG' => '+242', 'HR' => '+385', 'CU' => '+53',  'CY' => '+357', 'CZ' => '+420',
        'DK' => '+45',  'DJ' => '+253', 'DO' => '+1',   'EC' => '+593', 'EG' => '+20',
        'SV' => '+503', 'GQ' => '+240', 'ER' => '+291', 'EE' => '+372', 'ET' => '+251',
        'FJ' => '+679', 'FI' => '+358', 'FR' => '+33',  'GA' => '+241', 'GM' => '+220',
        'GE' => '+995', 'DE' => '+49',  'GH' => '+233', 'GR' => '+30',  'GT' => '+502',
        'GN' => '+224', 'GW' => '+245', 'GY' => '+592', 'HT' => '+509', 'HN' => '+504',
        'HK' => '+852', 'HU' => '+36',  'IS' => '+354', 'IN' => '+91',  'ID' => '+62',
        'IR' => '+98',  'IQ' => '+964', 'IE' => '+353', 'IL' => '+972', 'IT' => '+39',
        'JM' => '+1',   'JP' => '+81',  'JO' => '+962', 'KZ' => '+7',   'KE' => '+254',
        'KP' => '+850', 'KR' => '+82',  'KW' => '+965', 'KG' => '+996', 'LA' => '+856',
        'LV' => '+371', 'LB' => '+961', 'LS' => '+266', 'LR' => '+231', 'LY' => '+218',
        'LI' => '+423', 'LT' => '+370', 'LU' => '+352', 'MO' => '+853', 'MK' => '+389',
        'MG' => '+261', 'MW' => '+265', 'MY' => '+60',  'MV' => '+960', 'ML' => '+223',
        'MT' => '+356', 'MR' => '+222', 'MU' => '+230', 'MX' => '+52',  'MD' => '+373',
        'MC' => '+377', 'MN' => '+976', 'ME' => '+382', 'MA' => '+212', 'MZ' => '+258',
        'MM' => '+95',  'NA' => '+264', 'NP' => '+977', 'NL' => '+31',  'NZ' => '+64',
        'NI' => '+505', 'NE' => '+227', 'NG' => '+234', 'NO' => '+47',  'OM' => '+968',
        'PK' => '+92',  'PA' => '+507', 'PG' => '+675', 'PY' => '+595', 'PE' => '+51',
        'PH' => '+63',  'PL' => '+48',  'PT' => '+351', 'QA' => '+974', 'RO' => '+40',
        'RU' => '+7',   'RW' => '+250', 'SA' => '+966', 'SN' => '+221', 'RS' => '+381',
        'SL' => '+232', 'SG' => '+65',  'SK' => '+421', 'SI' => '+386', 'SO' => '+252',
        'ZA' => '+27',  'SS' => '+211', 'ES' => '+34',  'LK' => '+94',  'SD' => '+249',
        'SR' => '+597', 'SZ' => '+268', 'SE' => '+46',  'CH' => '+41',  'SY' => '+963',
        'TW' => '+886', 'TJ' => '+992', 'TZ' => '+255', 'TH' => '+66',  'TL' => '+670',
        'TG' => '+228', 'TT' => '+1',   'TN' => '+216', 'TR' => '+90',  'TM' => '+993',
        'UG' => '+256', 'UA' => '+380', 'AE' => '+971', 'GB' => '+44',  'US' => '+1',
        'UY' => '+598', 'UZ' => '+998', 'VE' => '+58',  'VN' => '+84',  'YE' => '+967',
        'ZM' => '+260', 'ZW' => '+263',
    ];

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/utils/countries', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_countries' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $this->namespace, '/utils/pincode/(?P<pincode>[0-9a-zA-Z -]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'check_pincode' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_countries( WP_REST_Request $request ): WP_REST_Response {
        $cache_key = 'utils_countries';
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    $this->success( $cached, 'storefuse.utils.v1' ),
                    3600
                )
            );
        }

        $wc_countries  = WC()->countries->get_allowed_countries();
        $wc_states     = WC()->countries->get_allowed_country_states();
        $calling_codes = apply_filters( 'storefuse_bridge_calling_codes', self::CALLING_CODES );

        $countries = [];
        foreach ( $wc_countries as $code => $name ) {
            $states = [];
            if ( ! empty( $wc_states[ $code ] ) ) {
                foreach ( $wc_states[ $code ] as $state_code => $state_name ) {
                    $states[] = [ 'code' => $state_code, 'name' => $state_name ];
                }
            }
            $countries[] = [
                'code'         => $code,
                'name'         => $name,
                'calling_code' => $calling_codes[ $code ] ?? '',
                'states'       => $states,
            ];
        }

        $data = [ 'countries' => $countries ];
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 3600 );

        return StoreFuse_Bridge_Response::with_public_cache(
            $this->success( $data, 'storefuse.utils.v1' ),
            3600
        );
    }

    public function check_pincode( WP_REST_Request $request ): WP_REST_Response {
        $pincode   = sanitize_text_field( $request->get_param( 'pincode' ) );
        $cache_key = 'utils_pincode_' . md5( $pincode );
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    $this->success( $cached, 'storefuse.utils.v1' ),
                    3600
                )
            );
        }

        // Allow external lookup via filter (return non-null array to override default logic).
        $override = apply_filters( 'storefuse_bridge_pincode_lookup', null, $pincode );
        if ( $override !== null && is_array( $override ) ) {
            StoreFuse_Bridge_Cache::set( $cache_key, $override, 3600 );
            return StoreFuse_Bridge_Response::with_public_cache(
                $this->success( $override, 'storefuse.utils.v1' ),
                3600
            );
        }

        $is_serviceable = $this->check_wc_zones( $pincode );

        $data = [
            'pincode'        => $pincode,
            'is_serviceable' => $is_serviceable,
            'city'           => '',
            'state'          => '',
            'country'        => '',
        ];

        StoreFuse_Bridge_Cache::set( $cache_key, $data, 3600 );

        return StoreFuse_Bridge_Response::with_public_cache(
            $this->success( $data, 'storefuse.utils.v1' ),
            3600
        );
    }

    // ── Helpers 

    /**
     * Check WooCommerce shipping zones for postcode serviceability.
     * Handles exact match, wildcard (*), and numeric range (NNN...MMM).
     */
    private function check_wc_zones( string $pincode ): bool {
        $zones = WC_Shipping_Zones::get_zones();

        foreach ( $zones as $zone_data ) {
            $zone      = new WC_Shipping_Zone( $zone_data['id'] );
            $locations = $zone->get_zone_locations();

            foreach ( $locations as $location ) {
                if ( $location->type !== 'postcode' ) {
                    continue;
                }

                $pattern = $location->code;

                if ( $pattern === '*' ) {
                    return true;
                }

                if ( strpos( $pattern, '...' ) !== false ) {
                    [ $start, $end ] = explode( '...', $pattern, 2 );
                    if ( is_numeric( $start ) && is_numeric( $end ) && is_numeric( $pincode ) ) {
                        if ( (int) $pincode >= (int) $start && (int) $pincode <= (int) $end ) {
                            return true;
                        }
                    }
                    continue;
                }

                // Wildcard glob match (e.g. "SW1*")
                if ( strpos( $pattern, '*' ) !== false ) {
                    $regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i';
                    if ( preg_match( $regex, $pincode ) ) {
                        return true;
                    }
                    continue;
                }

                if ( strcasecmp( $pattern, $pincode ) === 0 ) {
                    return true;
                }
            }
        }

        // Also check the "Rest of the World" zone (id = 0) - if any method exists, serviceable globally.
        $default_zone    = new WC_Shipping_Zone( 0 );
        $default_methods = $default_zone->get_shipping_methods( true );

        return ! empty( $default_methods );
    }
}
