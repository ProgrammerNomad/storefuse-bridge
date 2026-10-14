# StoreFuse Bridge - WordPress admin documentation

Documentation separates the current plugin UI from the proposed refactor so the planned changes can be reviewed before implementation.

## Directory layout

```
docs/admin/
├── README.md                 ← you are here
├── fields-registry.md        ← all setting keys (current + v2 target column)
├── client-mapping.md         ← admin → Flutter / Next.js
├── ajax-and-actions.md       ← shared AJAX / admin.js
├── ux-patterns.md            ← shared UI conventions
│
├── current/                  ← As-shipped plugin (11 submenu pages)
│   ├── README.md
│   ├── sidebar.md
│   └── pages/                ← one spec per live admin screen
│
└── refactor-v2/              ← Refactor proposal (not implemented)
    ├── README.md
    ├── IMPLEMENTATION-PLAN.md  ← full v2 build plan
    ├── target-sidebar.md
    ├── refactor-acceptance.md
    └── pages/                ← full specifications for all 13 v2 screens
```

The proposal reuses the current `storefuse_bridge_settings` option and its existing keys. **No database migration, backfill, or settings conversion is in scope.**

## Where to look manually

| Goal | Open |
|------|------|
| Today’s menus & slugs | [current/sidebar.md](current/sidebar.md) |
| Every input field & API key | [fields-registry.md](fields-registry.md) |
| Each **current** screen in detail | [current/pages/](current/pages/) |
| **Refactor** menu, visibility, redirects | [refactor-v2/target-sidebar.md](refactor-v2/target-sidebar.md) |
| **Refactor** implementation steps | [refactor-v2/IMPLEMENTATION-PLAN.md](refactor-v2/IMPLEMENTATION-PLAN.md) |
| Review the scope and decisions before coding | [refactor-v2/IMPLEMENTATION-PLAN.md](refactor-v2/IMPLEMENTATION-PLAN.md) |
| Acceptance checklist for implementation | [refactor-v2/refactor-acceptance.md](refactor-v2/refactor-acceptance.md) |
| **v2 page specs (all 13 screens)** | [refactor-v2/pages/](refactor-v2/pages/) |

## Short index

[admin-guide.md](../admin-guide.md) at `docs/` root links here.

**Capability:** `manage_woocommerce`. **Option:** `storefuse_bridge_settings`.
