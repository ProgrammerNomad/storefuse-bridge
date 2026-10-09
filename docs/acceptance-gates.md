# Production acceptance gates (14-section scorecard)

Use this after [staging-smoke.md](staging-smoke.md) on a real WP+WC site. Mark each gate **Pass / Fail / N/A** before calling Bridge **1.0.0** production-ready.

| # | Gate | Pass criteria | Verified |
|---|------|---------------|----------|
| 1 | Architecture / SSOT | Nonces/cart helpers centralized; `Format::address()` + `Format::cart_item()`; docs hub on GitHub | ☐ |
| 2 | Security | CORS expose headers; auth throttle; notify hardening; webhook SSRF; `GET /auth/nonce` rate limit | ☐ |
| 3 | WooCommerce integration | Checkout idempotency; stock/shipping validation; WC hooks fired | ☐ |
| 4 | API contract | [verified-routes.md](verified-routes.md) matches PHP; error catalog in [api-reference.md](api-reference.md) | ☐ |
| 5 | Web client | Next.js guide + cookie/BFF pattern documented | ☐ |
| 6 | Mobile client | [auth-strategy.md](auth-strategy.md) + Application Passwords path | ☐ |
| 7 | Data integrity | Migration runner; idempotency replay does not duplicate orders | ☐ |
| 8 | Performance | Product/wishlist batch load; paginated orders; review stats query | ☐ |
| 9 | Compatibility | [compatibility-matrix.md](compatibility-matrix.md) supported stack | ☐ |
| 10 | Quality | CI: `php -l`, PHPUnit, scoped PHPCS green | ☐ |
| 11 | Release ops | [release-checklist.md](release-checklist.md) upgrade/rollback exercised | ☐ |
| 12 | Documentation | OpenAPI skeleton + architecture cache recovery + security threat model | ☐ |
| 13 | Admin UX | Save notices; AJAX confirm/loading; homepage collapsible sections | ☐ |
| 14 | Observability | Logger on checkout/webhook failures; no secrets in logs | ☐ |

**Target:** 11+ gates **Pass** for production storefront traffic (per merged readiness plan).
