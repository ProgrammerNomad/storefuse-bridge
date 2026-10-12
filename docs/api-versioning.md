# API versioning and deprecation

StoreFuse Bridge exposes **`/wp-json/storefuse/v1`**. Response bodies include `api_version` (plugin semver) and `schema` (resource contract id).

## Response headers (v1)

Every `/storefuse/v1/*` success response includes:

| Header | Value | Purpose |
|--------|-------|---------|
| `X-StoreFuse-Bridge-Version` | Plugin semver | Support diagnostics |
| `X-StoreFuse-API-Version` | `v1` | Namespace generation |

When **`storefuse/v2`** ships:

1. Register `storefuse/v2` alongside v1 for one major Bridge release cycle.
2. Set filters on v1 responses (from a small mu-plugin or theme if needed):

```php
add_filter( 'storefuse_bridge_v1_sunset', fn () => 'Wed, 01 Jan 2027 00:00:00 GMT' );
add_filter( 'storefuse_bridge_api_successor_link', fn () => home_url( '/wp-json/storefuse/v2' ) );
```

3. v1 responses then include `Deprecation: true`, `Sunset: …`, and `Link: …; rel="successor-version"`.

Clients should treat **`schema`** as the shape contract (`storefuse.product.v1` vs future `.v2`).

## Conditional GET (ETag)

Public, cacheable JSON responses (`Cache-Control: public…`) include a weak **`ETag`**. Send **`If-None-Match`** with the prior ETag to receive **`304 Not Modified`** (headers preserved; empty body).

Coordinate with **`Cache-Control`** max-age and CDN rules documented in [architecture.md](architecture.md).

## Password reset URL priority

Reset links in emails resolve in this order:

1. Filter **`storefuse_bridge_password_reset_url`** (highest - use for Flutter deep links)
2. Admin **Storefront URL** + **Reset path**
3. WordPress default login URL

See [extensions.md](extensions.md) and [clients/mobile-flutter.md](clients/mobile-flutter.md).
