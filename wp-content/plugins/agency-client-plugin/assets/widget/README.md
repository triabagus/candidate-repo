# Case Studies widget (bonus / Task 6)

This folder is where the optional front-end widget goes. It is **not required**. A strong
submission can skip it entirely.

If you take it on:

- Build a small **React or Vue** component that fetches `/wp-json/acp/v1/case-studies`
  (the endpoint you implement in `includes/class-acp-rest.php`) and renders the list.
- Keep the toolchain light. A single-file build, a Vite project, or even a CDN-loaded
  React/Vue with a small `index.js` is all fine. Use whatever you'd reach for on a real
  small agency widget.
- In `docs/NOTES.md`, jot down how to build/run it and any trade-offs you made.

We're looking for: can you wire a JS front end to the WP REST API cleanly, handle the
loading/empty/error states, and not over-engineer a small widget.
