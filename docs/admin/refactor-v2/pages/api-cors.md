# API & CORS (v2 - planned)

## Purpose

Configures HTTP Cross-Origin Resource Sharing (CORS) headers for decoupled browser frontends, provides API base URL reference and status inspection, and documents session authentication guidelines.

Renamed from **API & Tools** to **API & CORS** after cache tools are relocated to the Dashboard.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge-api` |
| **Menu Title** | `API & CORS` |
| **Parent Group** | `DEVELOPER` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

All clients (`flutter`, `nextjs`, `other`).

---

## Planned Sections & Layout

### 1. CORS Configuration (Form)
- `settings_fields( 'storefuse_bridge_settings_group' );`
- **Enable CORS Checkbox**: `cors_enabled`
  - Sends `Access-Control-Allow-Origin`, `Access-Control-Allow-Credentials`, and allowed header response headers on `/storefuse/v1/*` requests.
- **Allowed Origins Textarea**: `cors_allowed_origins`
  - Explicit list of allowed domains, one per line (e.g., `https://store.example.com`).
  - Warning banner if CORS is enabled but no origins are specified:
    *"CORS is enabled but no origins are listed. Browsers will block cross-origin requests."*
- **Client Relevance Notice**:
  > **Note for app developers:** Native mobile applications do not enforce browser CORS restrictions. A browser-based app, including Flutter Web, may need CORS when it calls the API from a different origin. Same-origin browser clients do not need CORS.

### 2. API Diagnostics & Base URLs
- API Base URL: `get_site_url() . '/wp-json/storefuse/v1'`
- Copy button (`.sfb-copy-btn` + `data-target="sfb-api-url"`)
- Direct links:
  - `Open /status` (new window)
  - `View /settings` (new window)
  - `View /homepage` (new window)

### 3. Session & Authentication Guidelines
Reference card outlining requirements for decoupled authentication:
1. **Production HTTPS**: Enforce HTTPS for cookie security and API transmission.
2. **Web Browser Clients**: Use credentialed requests (`credentials: 'include'`) and matching origin headers for logged-in sessions.
3. **Flutter Mobile Clients**: Follow the documented WordPress/WooCommerce cookie and nonce flow, including cart-token support where required. Native mobile clients do not rely on browser CORS rules; see [the Flutter client guide](../../../clients/mobile-flutter.md).
4. **Documentation Link**: Direct link to GitHub documentation via `StoreFuse_Bridge_Admin::documentation_url()`.

---

## What was Removed in v2

- **Cache Control**: All transient flush buttons (`Flush all`, `Products`, `Settings`, `Navigation`, `Homepage`, `Search`) have moved to the **Dashboard** page for centralized operations.

---

## Fields Registered

All fields map to `storefuse_bridge_settings`:
`cors_enabled`, `cors_allowed_origins`.
