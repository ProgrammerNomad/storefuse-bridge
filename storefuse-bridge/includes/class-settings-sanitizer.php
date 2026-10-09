<?php
defined( 'ABSPATH' ) || exit;

/**
 * Validated shapes for JSON-backed admin settings.
 */
class StoreFuse_Bridge_Settings_Sanitizer {

    public const MAX_TRUST_BADGES = 12;
    public const MAX_FEATURED_CATEGORIES = 6;

    /**
     * @return list<array{icon:string,title:string,description:string,enabled:bool}>
     */
    public static function parse_trust_badges( mixed $raw ): array {
        $decoded = self::decode_json_list( $raw );
        if ( $decoded === null ) {
            return [];
        }
        $out = [];
        foreach ( array_slice( $decoded, 0, self::MAX_TRUST_BADGES ) as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $out[] = [
                'icon'        => sanitize_text_field( (string) ( $row['icon'] ?? '' ) ),
                'title'       => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
                'description' => sanitize_text_field( (string) ( $row['description'] ?? '' ) ),
                'enabled'     => ! empty( $row['enabled'] ),
            ];
        }
        return $out;
    }

    /**
     * @return list<array{term_id:int,label:string,icon:string,color:string}>
     */
    public static function parse_featured_categories( mixed $raw ): array {
        $decoded = self::decode_json_list( $raw );
        if ( $decoded === null ) {
            return [];
        }
        $out = [];
        foreach ( array_slice( $decoded, 0, self::MAX_FEATURED_CATEGORIES ) as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $term_id = absint( $row['term_id'] ?? $row['category_id'] ?? $row['id'] ?? 0 );
            if ( $term_id <= 0 ) {
                continue;
            }
            $color = sanitize_hex_color( (string) ( $row['color'] ?? '' ) );
            $out[] = [
                'category_id' => $term_id,
                'term_id'     => $term_id,
                'label'       => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
                'icon'        => sanitize_text_field( (string) ( $row['icon'] ?? '' ) ),
                'color'       => $color ?: '',
            ];
        }
        return $out;
    }

    /**
     * @return list<mixed>|null
     */
    private static function decode_json_list( mixed $raw ): ?array {
        if ( is_array( $raw ) ) {
            return $raw;
        }
        if ( ! is_string( $raw ) || $raw === '' ) {
            return [];
        }
        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            return null;
        }
        return $decoded;
    }
}
