# Navigation (v2 - planned)

## Purpose

Manages WordPress navigation menu locations mapped to StoreFuse endpoints (`storefuse-header` and `storefuse-footer`), previews the JSON menu hierarchy served by `GET /navigation`, and allows clearing cached navigation transients.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge-navigation` |
| **Menu Title** | `Navigation` |
| **Parent Group** | `APPEARANCE` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

- **Visible in sidebar**: `primary_client === 'nextjs'` or `'other'`
- **Hidden from sidebar**: `primary_client === 'flutter'`
- **Direct URL Access**: Fully accessible by direct URL (`admin.php?page=storefuse-bridge-navigation`) for all clients.
  - When accessed directly while `primary_client === 'flutter'`, displays a contextual info bar:
    > **Note:** This screen configures web navigation menus (`storefuse-header`, `storefuse-footer`). It is hidden from the sidebar because your primary client is set to **Flutter**. The mobile app uses bottom tabs configured under App Layout (`admin.php?page=storefuse-bridge-app-layout`). If your store also serves a web client, you can manage web menus here.

---

## Planned Sections & Layout

### 1. Menu Location Assignment Status
Displays status for the two theme locations registered by StoreFuse Bridge:
- `storefuse-header` (Main storefront header navigation)
- `storefuse-footer` (Storefront footer navigation)
- Status badge per location:
  - `Assigned` (Green badge with menu name)
  - `Not Assigned` (Amber badge)
- Action link: `Assign menus in Appearance → Menus` (`admin_url( 'nav-menus.php?action=locations' )`).

### 2. Live Navigation JSON Tree Preview
- Shows the current hierarchical JSON tree returned by `GET /navigation`.
- Displays menu item title, URL, target, and child menu structure.

### 3. Flush Navigation Cache Action
- Standalone button: `Flush Navigation Cache` (`#sfb-flush-nav-cache`).
- Performs AJAX call `storefuse_bridge_flush_cache_group` with `group: 'navigation'`.
- Status feedback container: `#sfb-flush-nav-result`.

---

## Technical Notes

- **Non-form page**: No `submit_button` or `options.php` form. Menu content is saved via standard WordPress `nav-menus.php`.
- **AJAX action used**: `storefuse_bridge_flush_cache_group`.
