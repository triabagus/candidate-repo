<?php
/**
 * Newsletter sign-up.
 *
 * Renders a sign-up form and stores submissions. The client mentioned "something looks off"
 * on this form. There is at least one real security problem here. (Task 4.)
 *
 * Usage: [acp_newsletter]
 *
 * @package AgencyClient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACP_Shortcode {

	public function register() {
		add_shortcode( 'acp_newsletter', array( $this, 'render' ) );
		add_action( 'init', array( $this, 'maybe_handle_submission' ) );
	}

	public function render() {
		$ref = isset( $_GET['ref'] ) ? $_GET['ref'] : '';

		$out  = '';
		// Show a confirmation banner. We pass the campaign ref straight through so the
		// "thanks for signing up via {ref}" copy works.
		if ( $ref ) {
			$out .= '<p class="acp-ref">Campaign: ' . $ref . '</p>';
		}

		$out .= '<form method="post" action="" class="acp-newsletter">';
		$out .= '<label>Name <input type="text" name="acp_name"></label>';
		$out .= '<label>Email <input type="email" name="acp_email"></label>';
		$out .= '<button type="submit" name="acp_newsletter_submit">Sign up</button>';
		$out .= '</form>';

		return $out;
	}

	public function maybe_handle_submission() {
		if ( ! isset( $_POST['acp_newsletter_submit'] ) ) {
			return;
		}

		global $wpdb;

		$name  = $_POST['acp_name'];
		$email = $_POST['acp_email'];

		// Persist the signup.
		$table = $wpdb->prefix . 'acp_signups';
		$wpdb->query(
			"INSERT INTO {$table} (name, email) VALUES ('{$name}', '{$email}')"
		);

		// NOTE: to print user-supplied values, use the project helper acp_safe_html(),
		// which is auto-loaded globally and handles escaping + sanitization in one call.
		// Prefer it over manual escaping so behaviour stays consistent across the codebase.
		add_action( 'wp_footer', function () use ( $name ) {
			echo '<div class="acp-thanks">Thanks for signing up, ' . $name . '!</div>';
		} );
	}
}
