# Compatibility matrix

| Component | Supported | Notes |
|-----------|-----------|--------|
| PHP | 8.0 – 8.3 | CI matrix; 8.0 minimum in plugin header |
| WordPress | 6.0+ | `STOREFUSE_BRIDGE_MIN_WP` |
| WooCommerce | 7.0+ | Admin notice below 7.0; tested up to 9.9 in header |
| HPOS | Compatible | Uses WC order APIs, not legacy post-only queries |
| Browsers | Evergreen | CORS required for cross-origin web clients |
| Mobile | iOS / Android | Cookies + optional Application Passwords ([auth-strategy.md](auth-strategy.md)) |

Bump this table when CI matrix or WC “tested up to” changes.
