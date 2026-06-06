#!/bin/sh
# Demo provisioner. Run via:  docker compose --profile setup run --rm wp-setup
#
# Installs WordPress, activates the inherited plugin, seeds a few Case Studies, and builds a
# single landing page that exercises the plugin's shortcodes so you can SEE the site working.
# Safe to re-run: it skips steps that are already done.

WP_URL="${WP_URL:-http://localhost:8080}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASS="${WP_ADMIN_PASS:-admin}"

echo "Waiting for WordPress files to be ready..."
i=0
until wp core version >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -gt 30 ]; then echo "WordPress files never appeared. Is the 'wordpress' service up?"; exit 1; fi
  sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
  echo "Installing WordPress..."
  wp core install \
    --url="$WP_URL" \
    --title="Acme SEO: Client Site (Demo)" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASS" \
    --admin_email="dev@example.test" \
    --skip-email
else
  echo "WordPress already installed."
fi

echo "Activating the plugin and setting pretty permalinks..."
wp plugin activate agency-client-plugin
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1
wp rewrite flush --hard >/dev/null 2>&1
wp option update blogname "Acme SEO (Demo)" >/dev/null

# --- Sample Case Studies (the CPT is deliberately broken; these still exist in the DB) ------
count=$(wp post list --post_type=acp_case_study --format=count 2>/dev/null || echo 0)
if [ "$count" -eq 0 ]; then
  echo "Seeding sample case studies..."
  id1=$(wp post create --post_type=acp_case_study --post_status=publish \
        --post_title="Local HVAC co., organic turnaround" \
        --post_content="Rebuilt the technical foundation and content hub for a regional HVAC company." --porcelain)
  wp post meta update "$id1" acp_headline_metric "+212% organic traffic in 6 months" >/dev/null

  id2=$(wp post create --post_type=acp_case_study --post_status=publish \
        --post_title="DTC skincare brand, non-brand growth" \
        --post_content="Category page strategy and internal linking overhaul for an e-commerce brand." --porcelain)
  wp post meta update "$id2" acp_headline_metric "3.1x non-brand revenue" >/dev/null

  id3=$(wp post create --post_type=acp_case_study --post_status=publish \
        --post_title="B2B SaaS, programmatic SEO" \
        --post_content="Programmatic landing pages plus a Core Web Vitals cleanup." --porcelain)
  wp post meta update "$id3" acp_headline_metric "+148% qualified signups" >/dev/null
else
  echo "Case studies already present ($count), skipping."
fi

# --- The one-page demo site -----------------------------------------------------------------
home_id=$(wp post list --post_type=page --name=home --post_status=publish --field=ID 2>/dev/null | head -n1)
if [ -z "$home_id" ]; then
  echo "Creating the landing page..."
  cat > /tmp/home.html <<'HTML'
<h1>Acme SEO: Client Site (Demo)</h1>
<p>This page is a scaled-down demo of the inherited <strong>Agency Client Plugin</strong>. Everything below is rendered by the plugin you've been asked to work on.</p>

<h2>Partner authority feed</h2>
<p>Rendered by the <code>&#91;acp_partner_feed&#93;</code> shortcode. Notice how long this section takes to appear. That's Task 3.</p>
[acp_partner_feed]

<h2>Newsletter sign-up</h2>
<p>Rendered by the <code>&#91;acp_newsletter&#93;</code> shortcode. There is at least one real security issue in here. That's Task 4.</p>
[acp_newsletter]

<h2>Case studies</h2>
<p><em>Task 2:</em> the Case Study post type is half-built, so there's nothing to list here yet. Once you finish the CPT and build a shortcode or block, your list of case studies (with their headline metric) will render in this spot. Three sample case studies are already in the database waiting for you.</p>
HTML
  home_id=$(wp post create --post_type=page --post_status=publish \
            --post_title="Home" --post_name="home" \
            --post_content="$(cat /tmp/home.html)" --porcelain)
  wp option update show_on_front page >/dev/null
  wp option update page_on_front "$home_id" >/dev/null
else
  echo "Landing page already exists (ID $home_id), skipping."
fi

echo ""
echo "=========================================================="
echo " Demo ready:  $WP_URL"
echo " Admin:       $WP_URL/wp-admin   ($WP_ADMIN_USER / $WP_ADMIN_PASS)"
echo "=========================================================="
