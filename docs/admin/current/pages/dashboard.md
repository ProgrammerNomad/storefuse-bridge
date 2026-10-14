# Dashboard

## Purpose

Give store managers a concise view of launch readiness, the StoreFuse API endpoint, enabled modules, cache controls, and WooCommerce compatibility. The dashboard is an overview; settings continue to be managed on their dedicated screens.

## Menu and access

| | |
|--|--|
| **Slug** | `storefuse-bridge` |
| **View** | `admin/views/page-dashboard.php` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` |
| **Saves settings** | No |

## Dashboard sections

1. **Header** - Plugin version, active primary client, and a link to change the client in Setup.
2. **Overview cards** - Required readiness count, API endpoint check state, enabled module count, and the number of required checks needing attention. Counts are computed from current WordPress, WooCommerce, and Bridge data. The API endpoint starts as **Not checked**; a successful `/status` request changes it to **Available**. This verifies the endpoint from the current admin browser and does not claim that a Flutter or Next.js client is connected.
3. **Store setup** - Applicable required checks appear as a short checklist with a direct **Fix** link when an item needs attention. Recommended, optional, informational, and not-applicable checks are available in a collapsed secondary list. The primary action opens the first required item needing attention; when all required checks are clear, it opens Setup.
4. **API diagnostics** - Copy the REST API base URL, test or open `/status`, and open documentation.
5. **Cache management** - Targeted flush buttons and Flush all are available under **Manage cache**. Flush all keeps its confirmation prompt. The last manual flush time and group are shown when recorded.
6. **Module status** - A compact enabled/total and disabled count links to the full Modules screen.
7. **WooCommerce compatibility** - Headless checkout, HPOS, and Store API availability appear in a collapsed details section.

## Readiness behavior

- Only applicable required checks affect the readiness count and **Attention needed** metric.
- Product, checkout, shipping, webhook, and cross-origin CORS checks use their current store/module conditions.
- Bridge navigation menus are an optional recommendation for Next.js storefronts because some storefronts use custom navigation. Flutter and Other clients do not receive a navigation launch blocker from an unset Bridge menu.
- Privacy policy and HTTPS remain recommendations, not technical launch blockers.
- Client mode changes guidance and the relevant readiness context. It does not disable API routes or alter saved settings.

## Operations and verification

The page is not a settings form and does not write the options array. Cache actions retain the existing AJAX endpoints, nonce, capability check, and confirmation behavior. API status checks call the existing `GET /storefuse/v1/status` endpoint. No database migration or REST contract change is part of this dashboard update.
