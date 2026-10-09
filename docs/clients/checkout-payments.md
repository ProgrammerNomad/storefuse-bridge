# Checkout and async payments

Headless checkout uses **`POST /storefuse/v1/checkout`** with WooCommerce session + `X-WC-Nonce`. Some gateways return **pending** or **on-hold** until the customer completes 3DS or an external redirect.

## Client responsibilities

1. **Handle pending orders** — Treat `status` / payment state in the checkout response; show “payment processing” UI when WC leaves the order unpaid.
2. **Thank-you / recovery** — Poll or deep-link to `GET /orders/{key}` (order key from checkout response) for confirmation after redirect gateways.
3. **Do not double-submit** — Send a stable **`Idempotency-Key`** header on `POST /checkout` so network retries do not create duplicate orders.
4. **Webhooks** — Storefront revalidation webhooks are **outbound from WordPress** (see Storefront settings), not payment gateway webhooks ingested by Bridge.

## Gateway-specific

Configure gateways in **WooCommerce → Settings → Payments**. Bridge does not embed gateway SDKs; the client either uses WC’s redirect URL flow (`POST /checkout/redirect-url` when enabled) or native gateway tokens if you add a companion plugin.

See also [nextjs.md](nextjs.md) and [api-reference.md](../api-reference.md) checkout section.
