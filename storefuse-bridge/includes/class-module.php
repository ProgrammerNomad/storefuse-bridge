<?php
defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for all StoreFuse Bridge modules.
 *
 * Every module extends this class and implements register_routes().
 */
abstract class StoreFuse_Bridge_Module {

    protected string $namespace = 'storefuse/v1';

    /**
     * Module identifier - used to check enabled/disabled state in settings.
     * Subclasses should override this (e.g. 'products', 'cart').
     */
    protected string $id = '';

    /**
     * Register REST routes. Called on rest_api_init.
     */
    abstract public function register_routes(): void;

    /**
     * Whether this module is enabled. Defaults to true.
     * Store owners can disable modules via the Advanced settings page.
     */
    public function is_enabled(): bool {
        if ( $this->id === '' ) {
            return true;
        }
        return (bool) StoreFuse_Bridge_Settings::get( "module_{$this->id}_enabled", true );
    }

    /**
     * Build a success response with the standard envelope.
     *
     * @param mixed  $data
     * @param string $schema  e.g. 'storefuse.products.v1'
     * @param int    $status  HTTP status code
     */
    protected function success( mixed $data, string $schema, int $status = 200 ): WP_REST_Response {
        return StoreFuse_Bridge_Response::success( $data, $schema, $status );
    }
}
