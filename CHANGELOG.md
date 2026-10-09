# Changelog

All notable changes to StoreFuse Bridge follow [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-03-20

### Added

- `StoreFuse_Bridge_Validator`; `Format::cart_item()` SSOT; structured cart qty errors (`quantity_below_minimum`, `quantity_above_maximum`, `sold_individually`).
- Rate limit on `GET /auth/nonce`; scoped PHPCS in CI; expanded PHPUnit (validator, SSRF URL checks, error envelope).
- Production docs: acceptance gates, staging smoke, compatibility matrix, checkout-payments client guide, admin troubleshooting, data ownership + CSRF trade-off in architecture.

### Changed

- Plugin version **1.0.0** — production gate release after merged readiness audit.

## [0.2.0] - 2026-03-20

### Fixed

- Cache `flush_all()` now deletes `sfb_` transients correctly; removed unbounded `storefuse_bridge_cache_keys` tracking on every `set()`.
- CORS exposes required headers, `Max-Age` on OPTIONS, and conditional Allow-Methods/Headers for allowed origins.
- `GET /auth/me` returns `200` with `logged_in: false` for guests.
- Password minimum length validation on register and reset-password.
- Centralized cart/WP REST nonce helpers; removed duplicate checkout formatters.
- `Format::address()` canonical shape; uninstall SQL uses `$wpdb->prepare()`.

### Added

- `StoreFuse_Bridge_Logger` for checkout/webhook failures.
- Auth throttle hooks/actions; notify hardening; webhook SSRF validation; checkout idempotency key support.
- Application Password authentication path for mobile; `RequestContext` wired per REST request.
- Migration runner on `plugins_loaded`; security and auth-strategy docs; OpenAPI skeleton; CI scaffolding.

## [0.1.0] - Initial release

- StoreFuse `/storefuse/v1` module architecture and admin settings UI.
