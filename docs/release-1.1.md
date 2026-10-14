# Bridge 1.1 / Flutter 1.1 integration contract

This release changes authentication and checkout behavior. Upgrade both repositories together. Flutter requires Bridge 1.1.0. Existing repository work is preserved; this is a locally validated implementation, not an app-store submission or certification for every gateway.

## Supported commerce

| Capability | Core behavior |
|---|---|
| Simple and variable products | Catalog, variation selection, stock and sale prices |
| Web checkout | One-use session handoff to the same-origin WooCommerce checkout; configure gateway navigation origins |
| Native checkout | COD, BACS and cheque, standard WooCommerce address fields; address quote and each package's shipping selection |
| Custom checkout fields / mandatory registration | Native form falls back to WooCommerce checkout |
| Orders | Ownership checks, server-confirmed status, cancellation/reorder under existing WooCommerce rules |
| Returns | Hidden without an adapter; when enabled, 30-day default window and durable `_storefuse_return_request` order metadata |
| Stock alerts | Hidden and rejected without an adapter; core does not deliver notification emails |
| Invoices / push / pincode serviceability | Hidden unless the corresponding capability provider declares support |
| Localization | Flutter localization framework and English navigation strings; full translations and RTL acceptance are not supplied |
| Subscriptions, bundles, bookings | Require dedicated adapters and acceptance tests |

## Session contract

On cold start POST form data `action=storefuse_session` to `/wp-admin/admin-ajax.php` using the persistent cookie jar. This same-origin/native endpoint validates WordPress's login cookie independently of REST nonce validation. It returns `{success:true,data:{nonce,cart_nonce,logged_in}}` and is not a cross-origin browser login API. Send `X-WP-Nonce` on authenticated reads **and** writes, and `X-WC-Nonce` on cart/checkout writes. Retry nonce failures once through session bootstrap. Logout cancels pending client requests, clears cookies, nonces, cached personal state and stored checkout references.

Flutter cookies and checkout attempts use OS-protected storage, scoped by site and build flavor. Old unencrypted cookie stores are no longer read; upgrading users sign in again. HTTP request/response bodies and credentials are not logged. Android backups and release cleartext are disabled; HTTP is only for local debug development.

## Checkout and recovery

`POST /checkout/quote` accepts billing, shipping, ship_to_different_address and shipping_method. It returns `packages` and an authoritative `cart` with totals. Native checkout must review a quote before submission. Missing or undeliverable packages fail validation.

`POST /checkout` requires an 8–128 character `Idempotency-Key`. A key identifies one purchase, scoped to a customer or WC guest session. Reuse it with the original body after timeout/restart. Reusing a key intentionally returns its original order, even if the current cart changed; a new purchase needs a new key. Changed addresses/payment/shipping/notes with the same key return conflict. Replay precedes empty-cart validation. Durable records live in non-autoloaded options and are not removed by content-cache flush. They are retained until explicitly reconciled by the operator; do not bulk-delete these options during a live checkout.

MySQL/MariaDB `GET_LOCK` is the authoritative lock and must run on the same connection/database writer as checkout. Contention never switches lock backends. A crash before the order reference is persisted fails closed with an unresolved attempt; an operator must reconcile it before another purchase. Payment exceptions after order creation recover the existing pending order and do not automatically call the gateway again. The app displays server status instead of claiming that pending orders are paid.

`POST /checkout/redirect-url` returns `handoff_url`, `handoff_token`, `payment_origins` and the checkout URL. POST the token in a form body to handoff_url within 60 seconds. It restores HttpOnly/Lax WordPress/WC cookies and redirects to checkout. Never put the token in a URL or log it. The token is single-use. An expired token requires a fresh handoff.

Use `storefuse_bridge_payment_origins` to supply exact HTTPS origins needed by a chosen gateway. Default navigation stays on the store origin. External payment-app schemes require an adapter; they are blocked by core. Test the gateway's full success/failure/cancel and return flow before enabling it for customers. No real payment-provider transaction was performed locally.

## Privacy operations

Authenticated GET/POST `/account/deletion-request` reads or initiates a confirmed WordPress personal-data erasure request. The app displays the status; staff accounts require manual handling. An administrator completes requests under **Tools → Erase Personal Data**. WooCommerce's erasers apply its configured order retention behavior before StoreFuse removes the customer account. Publish the actual merchant retention policy. The public entry point `/?storefuse_account_deletion=1` links to login and permits account-deletion requests without an installed app.

## Developer extension contracts

- `storefuse_bridge_modules`: add `StoreFuse_Bridge_Module` instances when the registry is constructed. Register filters before Bridge initializes. Existing module permissions and response envelopes still apply.
- `storefuse_bridge_headless_gateways`: adding a gateway requires a complete native adapter and tests; a flag alone does not implement payment input or 3DS.
- `storefuse_bridge_stock_alerts_available`, `storefuse_bridge_returns_available`, `storefuse_bridge_invoice_available`, `storefuse_bridge_push_available`, `storefuse_bridge_pincode_serviceability_available`: only return true with a working provider.
- Return providers consume `storefuse_bridge_order_return_requested` and update `_storefuse_return_request` status. Stock providers must implement delivery, consent, unsubscribe, cleanup and failure handling.
- Use `storefuse_bridge_order_tracking` and invoice filters for fulfillment/invoice adapters.

## Reproduce local verification

Back up your own store first. Fixtures refuse non-loopback sites and create a database backup and dedicated private credentials. Keep output outside the repository. Existing test SKUs/accounts are refreshed on rerun; do not run against client data.

```powershell
php scripts/local-fixtures.php D:\Websites\wordpress "$env:TEMP\storefuse-fixtures"
./scripts/deploy-local.ps1 -WordPressPath D:\Websites\wordpress
php scripts/integration-wordpress.php D:\Websites\wordpress
python scripts/integration-local.py "$env:TEMP\storefuse-fixtures\fixture-credentials.json"
```

The HTTP suite creates COD test orders and checks isolated customers, shipping validation, concurrent requests, replay, HttpOnly handoff, token reuse rejection, CORS denial and logout. Use a local mail sink while running; real delivery is not tested. The PHP integration suite creates and removes its own disposable privacy account/request and checks settings capabilities/content-cache separation.

Local evidence: PHP 8.3.30, WordPress 7.1.3, WooCommerce 11.2.0, HPOS; Android emulator API 36. PHP unit tests use stubs and are supplemented by real HTTP requests. Scoped PHPCS covers tests, while plugin source receives syntax checks; no claim of full WPCS compliance. External Redis/Memcached, other supported version combinations, load, iOS runtime, signed distribution and real payment/email providers remain release acceptance gates.
