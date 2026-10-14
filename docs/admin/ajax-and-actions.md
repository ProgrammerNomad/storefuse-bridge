# AJAX and admin actions

Registered in `StoreFuse_Bridge_Admin` constructor. Localized in `admin.js` as `sfbAdmin` (nonce, `flushUrl`, `apiBase`, i18n strings).

## Security

All handlers:

1. `check_ajax_referer( 'sfb_admin_nonce', 'nonce' )`  
2. `current_user_can( StoreFuse_Bridge_Admin::settings_capability() )` - default `manage_woocommerce`  

POST target: `admin-ajax.php`.

## Actions

| Action | UI trigger | Parameters | Success behaviour |
|--------|------------|------------|-------------------|
| `storefuse_bridge_flush_cache` | Legacy / unused in views? | - | Flush all transients |
| `storefuse_bridge_flush_cache_group` | `#sfb-flush-cache`, `.sfb-flush-group`, `#sfb-flush-nav-cache` | `group`: all, products, settings, navigation, homepage, search | Message in `#sfb-flush-result` or `#sfb-flush-group-result` or `#sfb-flush-nav-result`; updates `storefuse_bridge_last_flush_at` |
| `storefuse_bridge_test_webhook` | `#sfb-test-webhook` on Storefront / Webhooks v2 | - | HTTP status in `#sfb-test-webhook-result` |
| `storefuse_bridge_clear_webhook_log` | `#sfb-clear-webhook-log` | - | Reload page |
| `storefuse_bridge_dismiss_setup` | Dashboard setup notice dismiss | - | Clears transient |

## Client-side only (no AJAX)

| Feature | DOM / handler |
|---------|----------------|
| Copy API URL | `.sfb-copy-btn` + `data-target` |
| Checkout mode panels | `#sfb-panel-redirect`, `#sfb-panel-headless` |
| Hero media | `#sfb-hero-upload`, `#sfb-hero-remove` |
| Trust badges JSON | `#sfb-trust-badges`, `#sfb-trust-badges-json` |
| Featured categories JSON | `#sfb-featured-categories` |
| Homepage preview | `#sfb-preview-load` → GET `/homepage` |
| Unsaved changes | `beforeunload` on `.sfb-admin form` |
| Color pickers | `.sfb-color-picker` |

## Page references

| Control | Document |
|---------|----------|
| Cache flush | [dashboard.md](current/pages/dashboard.md), [api-tools.md](current/pages/api-tools.md) |
| Nav cache | [navigation.md](current/pages/navigation.md) |
| Webhooks | [storefront-clients.md](current/pages/storefront-clients.md), [webhooks-isr.md](refactor-v2/pages/webhooks-isr.md) |
