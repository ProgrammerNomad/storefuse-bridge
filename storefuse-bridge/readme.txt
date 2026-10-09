=== StoreFuse Bridge ===
Contributors: nomadprogrammer
Tags: woocommerce, headless, rest-api, storefuse
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Headless WooCommerce REST API for StoreFuse storefronts (web and mobile).

== Description ==

StoreFuse Bridge exposes a versioned `/storefuse/v1` REST namespace with normalized product, cart, checkout, and account payloads for headless clients.

Documentation: https://github.com/ProgrammerNomad/storefuse-bridge/blob/main/docs/README.md

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/storefuse-bridge/`
2. Activate through the Plugins menu
3. Requires WooCommerce 7.0+
4. Configure under StoreFuse in wp-admin

== Changelog ==

= 0.2.0 =
Production readiness fixes: cache flush, CORS, auth/me guest contract, logging, security hardening, idempotent checkout.

= 0.1.0 =
Initial release.
