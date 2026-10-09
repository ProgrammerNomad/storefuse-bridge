# Staging smoke log (template)

Copy this file or fill in your release ticket when running [staging-smoke.md](staging-smoke.md).

| Field | Value |
|-------|--------|
| Date | |
| Bridge version | 1.0.0 |
| WordPress | |
| WooCommerce | |
| Staging URL | |

## Automated (local/CI)

- [x] `php -l` on plugin PHP
- [x] PHPUnit (validator, URL SSRF, error envelope, version smoke)
- [x] PHPCS on `tests/` (PSR-12)

## Manual (staging)

| Check | Pass |
|-------|------|
| CORS from client origin | ☐ |
| Cache flush | ☐ |
| Guest cart add | ☐ |
| Checkout idempotency | ☐ |
| Auth nonce → login → `/auth/me` | ☐ |
| Order IDOR denial | ☐ |

**Sign-off:** ___________________
