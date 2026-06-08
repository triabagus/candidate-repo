# Tomo Digital — Inherited Client Site

A take-home exercise: an "inherited" WordPress client site shipped with a half-finished
agency plugin. The goal was to make the plugin work end-to-end (CPT, performance, security,
headless REST) and to document the kind of decisions a teammate would need to follow.

This README is just an entry point. The real content lives in `docs/`.

---

## Documentation map

| File | What's inside |
|---|---|
| [`docs/SETUP.md`](docs/SETUP.md) | How to get the site running locally on **WordPress Studio** (no Docker required). Includes prerequisites, plugin activation, the `ACP_AUTHORITY_API` constant, and seeding sample content. |
| [`docs/NOTES.md`](docs/NOTES.md) | The running log: per-task explanations of *what* was changed, *why*, and *how it was verified*. Read this if you want to grade the work. |
| [`docs/PROMOTION-PLAN-TASK.md`](docs/PROMOTION-PLAN-TASK.md) | Task 5: the staging → production promotion plan (code, database, secrets, safety net). |

If you only have time for one document, it's **`docs/NOTES.md`** — that's where the
reasoning behind every change is captured.

---

## Quick start

The full walkthrough is in [`docs/SETUP.md`](docs/SETUP.md). The five-line version:

```bash
# 1. Open WordPress Studio → Add site → Add existing site
#    Point it at this folder. Studio provisions SQLite and gives you a localhost URL.

# 2. In WP Admin → Plugins, activate "Agency Client Plugin".

# 3. Edit wp-config.php (in the "Add any custom values" block) and add:
#       define( 'WP_DEBUG', true );
#       define( 'WP_DEBUG_LOG', true );
#       define( 'WP_DEBUG_DISPLAY', false );
#       define( 'ACP_AUTHORITY_API', 'http://localhost:8081' );

# 4. Start the mock authority API (it stands in for the external service the partner
#    feed talks to). Requires Node 22+:
node tools/mock-api/server.js

# 5. Create a page with the shortcodes you want to exercise, e.g.:
#       [acp_partner_feed]
#       [acp_newsletter]
#       [acp_case_studies_widget]
```

You can also run the bundled `docker-compose.yml` instead of Studio — it's unmodified and
still works. Pick whichever your machine is happier with.

---

## What's been delivered

Per-task summary. Full reasoning, before/after numbers, and trade-offs are in
[`docs/NOTES.md`](docs/NOTES.md).

| Task | Status | Where the work lives |
|---|---|---|
| **1 — Setup** | Done | [`docs/SETUP.md`](docs/SETUP.md) |
| **2 — Case Study CPT** | Done | `wp-content/plugins/agency-client-plugin/includes/class-acp-cpt.php` |
| **3 — Performance** | Done | `…/includes/class-acp-market-widget.php` (transient cache, ~1.6 s → ~12 ms warm) |
| **4 — Security** | Done | `…/includes/class-acp-shortcode.php` (SQL-i, two XSS, CSRF, validation) |
| **5 — Promotion plan** | Done | [`docs/PROMOTION-PLAN-TASK.md`](docs/PROMOTION-PLAN-TASK.md) |
| **6 — Headless widget** (bonus) | Done | `…/includes/class-acp-rest.php`, `…/assets/widget/index.js` |

---

## Verifying it works

Once the site is up (Studio gives you the port; the examples use `8883` — replace with
yours) and the mock API is running on `:8081`:

```bash
# Partner feed (Task 3): first hit ~1.6 s, subsequent hits ~12 ms while cache is warm
curl -s -o /dev/null -w "Cold: %{time_total}s\n" http://localhost:8883/partner-test/
curl -s -o /dev/null -w "Warm: %{time_total}s\n" http://localhost:8883/partner-test/

# REST endpoint (Task 6)
curl -s "http://localhost:8883/wp-json/acp/v1/case-studies" | head -c 400
curl -sI "http://localhost:8883/wp-json/acp/v1/case-studies" | grep -i "x-wp-total"

# Mock authority API (used by the partner feed)
curl -s "http://localhost:8081/?domain=moz.com"
```

The plugin also writes a per-request timing line to `wp-content/debug.log` while the Task 3
instrumentation is in place — `tail -f wp-content/debug.log` to see it.

---

## Project layout (the parts that matter)

```
tomo-digital/
├── docs/
│   ├── SETUP.md                 # local setup walkthrough
│   ├── NOTES.md                 # per-task reasoning + verification
│   └── PROMOTION-PLAN-TASK.md   # staging → prod runbook
├── tools/mock-api/
│   ├── router.php               # original PHP mock API (used by docker-compose)
│   └── server.js                # Node port for environments without system PHP
├── bin/seed.sh                  # demo provisioner (Docker path)
├── docker-compose.yml           # the original Docker setup (unchanged)
└── wp-content/plugins/agency-client-plugin/
    ├── agency-client-plugin.php
    ├── assets/
    │   ├── css/agency-client.css
    │   └── widget/index.js      # React widget (Task 6)
    └── includes/
        ├── class-acp-cpt.php           # Case Study CPT (Task 2)
        ├── class-acp-market-widget.php # Partner feed (Task 3)
        ├── class-acp-shortcode.php     # Newsletter (Task 4)
        └── class-acp-rest.php          # REST endpoint + widget shortcode (Task 6)
```

Everything outside `wp-content/plugins/agency-client-plugin/`, `docs/`, `tools/`, `bin/`,
and the repo's root-level config files is stock WordPress / WordPress Studio scaffolding
and is excluded from version control via `.gitignore`.
