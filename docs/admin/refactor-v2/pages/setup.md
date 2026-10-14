# Setup (v2 - planned)

## Purpose

First-time store connection: primary client selection, storefront URL, password reset path, and optional health check. This proposal moves existing fields from [Storefront & Clients](../../current/pages/storefront-clients.md); it does not add settings storage.

## Menu (planned)

| | |
|--|--|
| **Slug** | `storefuse-bridge-setup` |
| **Legacy redirect** | `storefuse-bridge-storefront` → here |

## v2 sidebar

All clients.

## Planned layout

1. **Primary client** - visual cards (Flutter / Next.js / Other) replacing `<select>`  
2. **Storefront URL** + **Password reset path** (same fields as today)  
3. **API base URL** - read-only copy (convenience duplicate of Dashboard)  
4. **Connection health** - optional GET `/status` indicator  
5. Explain that changing the client mode only changes admin presentation and sidebar visibility. It does not change API behavior or clear values on other pages.  

## Fields

Same keys as today - no renames:

- `primary_client`
- `storefront_url`
- `storefront_reset_path`

## Behaviour (v2)

- On save: show the normal WordPress settings confirmation and reflect the selected mode in the sidebar on the next render  
- Never auto-overwrite `primary_client` on plugin upgrade  

See [target-sidebar.md](../target-sidebar.md).
