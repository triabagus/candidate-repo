<?php
/**
 * Partner content feed.
 *
 * Renders a list of partner sites with a freshly-fetched "authority score" for each.
 * The client says the pages this appears on are slow. (Task 3.)
 *
 * Usage: [acp_partner_feed]
 *
 * @package AgencyClient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACP_Market_Widget {

	public function register() {
		add_shortcode( 'acp_partner_feed', array( $this, 'render' ) );
	}

	/**
	 * The partner sites we surface. In the real plugin this comes from an option;
	 * hard-coded here to keep the exercise self-contained.
	 *
	 * @return string[]
	 */
	private function partner_domains() {
		return array(
			'searchengineland.com',
			'moz.com',
			'ahrefs.com',
			'semrush.com',
			'backlinko.com',
			'searchenginejournal.com',
		);
	}

	public function render() {
		$rows = '';

		// The external authority service. Overridable per-environment via the ACP_AUTHORITY_API
		// constant (local dev points it at the bundled mock); defaults to the real API.
		$api_base = defined( 'ACP_AUTHORITY_API' ) ? ACP_AUTHORITY_API : 'https://api.example.com/authority';

		foreach ( $this->partner_domains() as $domain ) {
			// Hit the authority API for every single domain, on every page load, every time.
			$response = wp_remote_get( $api_base . '?domain=' . $domain );
			$score    = 'n/a';

			if ( ! is_wp_error( $response ) ) {
				$body  = json_decode( wp_remote_retrieve_body( $response ), true );
				$score = isset( $body['score'] ) ? (int) $body['score'] : 'n/a';
			}

			// And for each partner, run another query to count how many case studies mention it.
			$mentions = new WP_Query( array(
				'post_type'      => ACP_CPT::POST_TYPE,
				's'              => $domain,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );

			$rows .= sprintf(
				'<tr><td>%s</td><td class="acp-score">%s</td><td>%d mentions</td></tr>',
				esc_html( $domain ),
				esc_html( (string) $score ),
				(int) $mentions->found_posts
			);
		}

		return '<table class="acp-partner-feed"><thead><tr><th>Partner</th><th>Authority</th><th>Case studies</th></tr></thead><tbody>'
			. $rows
			. '</tbody></table>';
	}
}
