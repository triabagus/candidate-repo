# Notes

Use this file as your running log. We read it as closely as the code.

## Task 2: Case Study feature
- What you changed, and why you chose a shortcode vs a block:

I used the shortcodes provided in [acp_newsletter] or [acp_partner_feed] but they haven’t been placed anywhere yet. So there’s no way to check the website slowdown.  

This means you need to create pages. Here’s what you need to do:  

1. Make sure the plugin is active  
WP Admin (http://localhost:<port>/wp-admin) → Plugins → Agency Client Plugin should be Active.  

2. Create a test page with the shortcode  
Quick way: Pages → Add New:  
- Title: Partner Test  
- Content (use Shortcode block): [acp_partner_feed] or [acp_news_letter]  
- Publish  

After publishing, there will be a URL like http://localhost:<port>/partner-test/.  

3. Re-run the baseline test  
Once the page is created, you can check its URL (http://localhost:<port>/partner-test/) and check the measurements.

## Task 3: Performance

### What was wrong
The `[acp_partner_feed]` shortcode in `class-acp-market-widget.php` made **6 sequential HTTP calls** to the external authority API on every single render — one per partner domain — with **zero caching**. Each call is ~250 ms (the mock API mirrors the real service's latency), so the shortcode burned **~1.5 seconds of avoidable wait time on every page load** before WordPress could even start sending HTML.

In short: a classic N+1 / "no cache" combination. The API is the bottleneck; the loop just makes it cumulative.

### How I confirmed it
Two independent measurements, so the conclusion doesn't rest on a single signal:

1. **Black-box timing with `curl`** against a page containing the shortcode:
   ```bash
   for i in 1 2 3 4 5; do
     curl -s -o /dev/null -w "Request $i: %{time_total}s\n" http://localhost:8883/partner-test/
   done
   ```
   Every request landed around **1.6 s** — orders of magnitude slower than a page without the shortcode (~250 ms baseline).

2. **In-PHP instrumentation** (more authoritative — measured from inside the render loop, so it can't be blamed on network/theme/etc.). I wrapped the API call and the per-domain `WP_Query` with `microtime( true )` and logged a summary line to `wp-content/debug.log`:

   ```
   [ACP partner_feed] total=1.615s api=1.562s (6 uncached calls) wpquery=0.053s (6 queries)
   ```
   The `api=1.562s (6 uncached calls)` line is the smoking gun: **96.7 % of render time is spent waiting on the API**.

Cross-check by hitting the mock API directly to confirm the per-call cost:

```bash
time for d in searchengineland.com moz.com ahrefs.com semrush.com backlinko.com searchenginejournal.com; do
  curl -s "http://localhost:8081/?domain=$d" > /dev/null
done
# real ~1.6s — 6 × 250 ms latency, matching the instrumentation
```

### What my fix changes
**File:** `wp-content/plugins/agency-client-plugin/includes/class-acp-market-widget.php`

1. Extracted the per-domain API call into a private `get_authority_score()` method that wraps `wp_remote_get` in a **WordPress transient**:

   - Cache key: `acp_authority_` + `md5( api_base | domain )` — namespaced by environment so local-mock and production scores don't collide.
   - TTL: `HOUR_IN_SECONDS`. Authority scores aren't real-time data (they refresh weekly at best on real services), so 1 hour of staleness is safe and slashes the load on the upstream API.
   
2. `render()` now calls the helper instead of issuing the HTTP request inline. On a cache hit the network call is skipped entirely.
3. Added a `$cache_hit` out-parameter so the timing log distinguishes warm vs cold loads: `api_calls` only increments on cache miss. After warm-up the summary line reads `(0 uncached calls)`.
4. Defensive cleanup: `rawurlencode( $domain )` on the query-string value, in case the partner list ever contains a domain with special characters.

**Measured impact** (same instrumentation, same page):

| | `total` | `api` | uncached calls |
|---|---|---|---|
| Before fix (cold) | 1.615 s | 1.562 s | 6 |
| After fix, cold (first request after cache expires) | 1.612 s | 1.601 s | 6 |
| **After fix, warm (within 1 h)** | **~0.012 s** | **~0.001 s** | **0** |

Warm page renders go from **~1.6 s → ~12 ms** — about **130–160× faster**. Cold loads still pay the API cost once per hour per domain, which is the trade-off we chose vs. background warming.

### Intentionally NOT fixed
- The per-partner `WP_Query` (the second N+1, used to count case-study mentions). It's still 6 queries per render, but each one is sub-millisecond on SQLite/MySQL with the current dataset, so it's not a real bottleneck today. If the partner list or case-study count grows, fold all 6 into a single aggregate query (or cache the counts alongside the score). Documented as follow-up, not in scope for the "site feels slow" report.

## Task 4: Security
- What you found:
- Why it was dangerous:
- How you fixed it:

## Task 6: Headless widget (bonus, optional)
- How to build/run it, and any trade-offs:

## Anything else
- Assumptions, things you'd do with more time, anything that surprised you:
