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
- What was wrong:
- How you confirmed it (profiling, query monitor, timing, etc.):
- What your fix changes:

## Task 4: Security
- What you found:
- Why it was dangerous:
- How you fixed it:

## Task 6: Headless widget (bonus, optional)
- How to build/run it, and any trade-offs:

## Anything else
- Assumptions, things you'd do with more time, anything that surprised you:
