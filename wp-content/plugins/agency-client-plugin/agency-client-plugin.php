<?php
/**
 * Plugin Name:       Agency Client Plugin
 * Description:       Inherited client functionality: Case Studies, newsletter sign-up, and a partner content feed.
 * Version:           1.3.0
 * Requires PHP:      8.0
 * Requires at least: 6.2
 * Author:            (previous developer, no longer with us)
 * Text Domain:       agency-client
 *
 * @package AgencyClient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'ACP_VERSION', '1.3.0' );
define( 'ACP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACP_URL', plugin_dir_url( __FILE__ ) );

require_once ACP_PATH . 'includes/class-acp-cpt.php';
require_once ACP_PATH . 'includes/class-acp-market-widget.php';
require_once ACP_PATH . 'includes/class-acp-shortcode.php';
require_once ACP_PATH . 'includes/class-acp-rest.php';

/**
 * Boot the plugin.
 */
function acp_bootstrap() {
	( new ACP_CPT() )->register();
	( new ACP_Market_Widget() )->register();
	( new ACP_Shortcode() )->register();
	( new ACP_Rest() )->register();
}
add_action( 'plugins_loaded', 'acp_bootstrap' );

/**
 * Front-end styles for the plugin's shortcodes.
 */
function acp_enqueue_assets() {
	wp_enqueue_style(
		'agency-client',
		ACP_URL . 'assets/css/agency-client.css',
		array(),
		ACP_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'acp_enqueue_assets' );

/**
 * Activation: create the signups table and refresh rewrite rules.
 */
function acp_activate() {
	global $wpdb;

	$table           = $wpdb->prefix . 'acp_signups';
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta(
		"CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name TEXT NOT NULL,
			email VARCHAR(190) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};"
	);

	( new ACP_CPT() )->register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'acp_activate' );
