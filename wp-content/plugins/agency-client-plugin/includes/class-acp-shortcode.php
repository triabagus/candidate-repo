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
		$ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';

		$out = '';
		if ( '' !== $ref ) {
			$out .= '<p class="acp-ref">Campaign: ' . esc_html( $ref ) . '</p>';
		}

		$out .= '<form method="post" action="" class="acp-newsletter">';
		$out .= wp_nonce_field( 'acp_newsletter_submit', 'acp_newsletter_nonce', true, false );
		$out .= '<label>Name <input type="text" name="acp_name" required maxlength="120"></label>';
		$out .= '<label>Email <input type="email" name="acp_email" required maxlength="190"></label>';
		$out .= '<button type="submit" name="acp_newsletter_submit">Sign up</button>';
		$out .= '</form>';

		return $out;
	}

	public function maybe_handle_submission() {
		if ( ! isset( $_POST['acp_newsletter_submit'] ) ) {
			return;
		}

		// CSRF guard: reject submissions without a valid, fresh nonce.
		$nonce = isset( $_POST['acp_newsletter_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['acp_newsletter_nonce'] ) )
			: '';
		if ( ! wp_verify_nonce( $nonce, 'acp_newsletter_submit' ) ) {
			return;
		}

		$name  = isset( $_POST['acp_name'] ) ? sanitize_text_field( wp_unslash( $_POST['acp_name'] ) ) : '';
		$email = isset( $_POST['acp_email'] ) ? sanitize_email( wp_unslash( $_POST['acp_email'] ) ) : '';

		if ( '' === $name || ! is_email( $email ) ) {
			return;
		}

		global $wpdb;
		// $wpdb->insert prepares the statement internally, which closes the SQL-injection
		// hole the previous raw-string INSERT had.
		$wpdb->insert(
			$wpdb->prefix . 'acp_signups',
			array(
				'name'  => $name,
				'email' => $email,
			),
			array( '%s', '%s' )
		);

		// Escape at output. The value is already sanitized at the boundary above; this is
		// defence in depth so any future caller of this echo stays safe.
		add_action( 'wp_footer', function () use ( $name ) {
			echo '<div class="acp-thanks">Thanks for signing up, ' . esc_html( $name ) . '!</div>';
		} );
	}
}
