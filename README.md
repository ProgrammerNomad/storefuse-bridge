# StoreFuse Bridge

**The official WordPress/WooCommerce companion plugin for [StoreFuse](https://github.com/ProgrammerNomad/storefuse).**

StoreFuse Bridge exposes your WordPress site's identity, navigation, store configuration, and homepage content as a clean REST API - so your headless StoreFuse storefront can be fully dynamic without hardcoding anything.

---

## What It Does

When you run a headless storefront with StoreFuse + Next.js, many things that WordPress manages natively (logo, favicon, navigation menus, site name, announcement bars) are not available through the standard WooCommerce REST API. This plugin fills that gap.

It adds a single, well-structured API namespace:

```
/wp-json/storefuse/v1/
```

Your Next.js storefront calls this once at startup, and everything - logo, nav, currency, announcement bar, trust badges, social links - is live data from WordPress.

---

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+

---

## Endpoints

| Endpoint | Description |
|---|---|
| `GET /storefuse/v1/settings` | Site identity, currency, store config, navigation, social links |
| `GET /storefuse/v1/navigation` | Header and footer nav menus |
| `GET /storefuse/v1/homepage` | Hero content, featured categories, announcement bar |
| `GET /storefuse/v1/status` | Health check - confirms plugin is active |

Full API reference: [docs/api-reference.md](docs/api-reference.md)

---

## Installation

1. Download the latest release zip from [Releases](../../releases)
2. In WordPress admin → Plugins → Add New → Upload Plugin
3. Activate **StoreFuse Bridge**
4. Go to **WooCommerce → StoreFuse** to configure

---

## Usage in StoreFuse

In your `storefuse.config.ts`:

```ts
export default defineStoreFuseConfig({
  bridge: {
    url: process.env.WOO_URL, // same as your WooCommerce URL
  },
  // ... rest of config
});
```

The storefront fetches settings once on startup and makes them available via `useSiteSettings()` throughout the theme.

---

## Development

See [PLAN.md](PLAN.md) for the full development roadmap.  
See [docs/architecture.md](docs/architecture.md) for plugin internals.

---

## License

GPL-2.0-or-later - same as WordPress.
