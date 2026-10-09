# Changelog

All notable changes to StoreFuse Bridge follow [Semantic Versioning](https://semver.org/).

## [1.0.2] - 2026-03-20

### Fixed

- **Guest cart bootstrap:** `GET /auth/nonce` and `GET /cart` now expose `cart_nonce` (`wc_store_api`); cart responses also send `X-WC-Nonce`. Guest `GET /auth/me` includes the same nonce pair when logged out.
- **Checkout idempotency locks:** atomic acquire via DB table (option fallback); all post-lock checkout paths release the lock in `try/finally` (validation failures no longer hold locks for 120s).
- **Webhook URL validation:** fail closed when DNS returns no addresses; outbound `wp_remote_post` uses `redirection => 0`.

### Added

- `docs/cart-token-restore.md` staging matrix for signed `X-StoreFuse-Cart-Token` (experimental until WC checklist passes).
- PHPUnit: guest nonce bootstrap, idempotency lock acquire/release, empty DNS webhook rejection.

## [1.0.1] - 2026-03-20

### Fixed

- Checkout **Idempotency-Key** scoped to WC session/user; request fingerprint mismatch returns `idempotency_key_reused`; in-progress lock; cached payload omits `order_key`.
- Removed duplicate **`woocommerce_checkout_order_created`** dispatch; safer checkout/payment error responses; pending/redirect gateway handling.
- **`GET /settings`** no longer exposes `admin_email` unless `storefuse_bridge_expose_admin_email` filter is true.

### Added

- Signed **`X-StoreFuse-Cart-Token`** restore path (`StoreFuse_Bridge_Cart_Session_Token`) for mobile session continuity.
- Trusted-proxy **client IP** helper; login throttle counts failures after bad password, not before attempt.
- **Settings sanitizer** for trust badges and featured categories JSON.
- Webhook **endpoint re-validation** immediately before outbound HTTP.

### Security

- Throttling uses trusted `REMOTE_ADDR` unless `storefuse_bridge_trusted_proxies` is configured.

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
