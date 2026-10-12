# Webhooks - Flutter consumer (stub)

StoreFuse Bridge can emit outbound webhooks when configured in WordPress admin (**StoreFuse → Extensions / Webhooks**). This document will describe how the Flutter app should react to server-driven events.

## Planned scope

- Event types exposed by `StoreFuse_Bridge_Module_Webhooks` (order status, stock, etc.)
- HMAC signature verification header name and shared secret rotation
- Idempotent handling and background delivery on mobile (FCM optional bridge)
- Retry semantics and deduplication keys

## Flutter integration checklist (draft)

1. Do **not** rely on webhooks for cart or checkout state - always reconcile via `GET /cart` and order APIs.
2. Register device tokens only after customer login unless the store enables guest marketing webhooks.
3. Validate webhook payloads against the same `schema` versioning rules as REST (`storefuse.*.v1`).

See also: [api-reference.md](api-reference.md), [clients/mobile-flutter.md](clients/mobile-flutter.md), and [verified-routes.md](verified-routes.md) for REST routes that mirror webhook payloads.
