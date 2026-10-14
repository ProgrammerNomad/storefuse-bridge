# Local baseline record (Phase 0 / F09)

Use this whenever you sync the development plugin to WordPress or before acceptance runs. Keeps **source**, **installed plugin**, and **live API** aligned.

## Sync checklist

1. Back up the store database (fixtures also write `before-fixtures.sql` when used).
2. From the **storefuse-bridge** repository root:

   ```powershell
   ./scripts/deploy-local.ps1
   # or
   ./scripts/deploy-local.ps1 -WordPressPath D:\Websites\wordpress
   # XAMPP:
   ./scripts/deploy-local.ps1 -WordPressPath C:\xampp\htdocs
   ```

3. Confirm the script reports **Build ID** (git short SHA) and no hash mismatches.
4. Verify under `wp-content/plugins/storefuse-bridge/`:
   - `includes/class-rest-schema.php`
   - `includes/class-checkout-handoff.php`
   - `includes/class-checkout-idempotency.php`
   - `build-info.php` (generated on deploy only)
5. Optional populated catalog (**loopback hosts only**):

   ```powershell
   php scripts/local-fixtures.php D:\Websites\wordpress "$env:TEMP\storefuse-fixtures"
   ```

6. Capture `GET /status` and paste into [VERIFIED_CHECKLIST.md](../../storefuse-flutter/docs/VERIFIED_CHECKLIST.md) or your release ticket.

   Optional auto-append (WordPress running):

   ```powershell
   ./scripts/deploy-local.ps1 -WordPressPath D:\Websites\wordpress -RecordBaseline
   ```

   Appends to `docs/BASELINE-RECORD.md` (local, gitignored).

## Record from GET /status

```bash
curl -sS "http://localhost/wp-json/storefuse/v1/status"
```

From envelope `data`:

| Field | Notes |
|-------|--------|
| `version` | Bridge semver |
| `build_id` | Git SHA after `deploy-local.ps1` |
| `build_time` | UTC deploy timestamp |
| `wordpress` | WP version |
| `woocommerce` | WC version |
| `status` | `ok` or `degraded` |
| `health` | DB / HPOS hints when present |

Also note bridge repo `git rev-parse HEAD`, Flutter repo commit, and deploy backup path (`%TEMP%\storefuse-installed-*`).

## When baseline fails

- **`build_id` missing** - Re-run `deploy-local.ps1`; copying PHP by hand skips `build-info.php`.
- **Installed drift** - Re-deploy; do not patch single files on the server.
- **Stale `* - Copy` folders on disk** - Current deploy mirrors from source and excludes `* - Copy` trees.

## Fixtures

```bash
php scripts/local-fixtures.php PATH_TO_WORDPRESS PRIVATE_OUTPUT_DIR
python scripts/integration-local.py PRIVATE_OUTPUT_DIR/fixture-credentials.json
```

Keep `PRIVATE_OUTPUT_DIR` (passwords) out of git.
