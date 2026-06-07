# Setup (Task 1)

Document the exact steps you took to get the site running and the plugin active, starting
from a clean machine. Include anything a teammate would trip over.

_Your steps:_

The repo ships a `docker-compose.yml`, but I ran this exercise on **WordPress Studio** (Automattic's local WordPress app for macOs/Windows) instead. Two reasons:

1. Studio was already installed on my machine and gives me WordPress + SQLite + WP-CLI in one click, so I avoided needing Docker Desktop running just for this exercise.
2. From assignment asks for *my* setup steps, not the standard ones — so this file documents the Studio-based path. But the Docker path in `docker-compose.yml` is still available for anyone who prefers that; nothing has been changed in the plugin or repo to lock you into Studio.

The trade-off: Studio doesn't ship the `mock-api` service that `docker-compose.yml` provides, so step 3 below sets up an equivalent manually.

## Prerequisites

- **WordPress Studio** — https://developer.wordpress.com/studio/
- **Node.js 22+** — used to run the mock authority API. Check with `node --version`. If you don't have it, `brew install node` on macOS.

That's it. No Docker, no MAMP, no Homebrew PHP required.

## 1. Add the repo as a Studio site

1. Open WordPress Studio → **Add site** → **Add existing site**.
2. Point it at this folder: `/Users/<you>/Studio/tomo-digital` (anywhere on disk is fine — Studio just needs the path to the WordPress install).
3. Studio detects WordPress, provisions SQLite, and serves the site at a `http://localhost:<port>` URL it picks for you (mine landed on the default port — yours may differ).
4. Open the site once in the browser so Studio finalises the install.

## 2. Activate the plugin

The bundled plugin lives at `wp-content/plugins/agency-client-plugin/` already, so no copying or symlinking is needed.

1. Studio → your site → **WP Admin** → **Plugins**.
2. Add Plugin → `wp-content/plugins/agency-client-plugin/`
3. Activate **Agency Client Plugin**.

## 3. Run the mock authority API

To run the API, it's automatic once you've activated the **Agency Client Plugin**.

## 4. Wire the plugin to the mock API

The plugin reads the API base URL from the `ACP_AUTHORITY_API` constant and falls back to a placeholder production URL if it's not defined. I added the constant (plus debug logging) in `wp-config.php` in the "Add any custom values" block:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'ACP_AUTHORITY_API', 'http://localhost:8081' ); // define port Authority API 
```

Debug logs land in `wp-content/debug.log`.

## 5. Seed sample content

**A landing page** named `Home` containing the shortcodes from the demo (set as the static front page in Settings → Reading):

```text
[acp_partner_feed]
[acp_newsletter]
```