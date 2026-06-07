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

		// TEMP (Task 3): timing instrumentation. Safe to remove once the perf bug is signed off.
		$t_render_start = microtime( true );
		$t_api_total    = 0.0;
		$t_query_total  = 0.0;
		$api_calls      = 0;
		$query_calls    = 0;

		foreach ( $this->partner_domains() as $domain ) {
			$t = microtime( true );
			$score = $this->get_authority_score( $api_base, $domain, $cache_hit );
			$t_api_total += microtime( true ) - $t;
			if ( ! $cache_hit ) {
				$api_calls++;
			}

			// And for each partner, run another query to count how many case studies mention it.
			$t = microtime( true );
			$mentions = new WP_Query( array(
				'post_type'      => ACP_CPT::POST_TYPE,
				's'              => $domain,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );
			$t_query_total += microtime( true ) - $t;
			$query_calls++;

			$rows .= sprintf(
				'<tr><td>%s</td><td class="acp-score">%s</td><td>%d mentions</td></tr>',
				esc_html( $domain ),
				esc_html( (string) $score ),
				(int) $mentions->found_posts
			);
		}

		// TEMP (Task 3): summary line. Remove with the timing block above.
		error_log( sprintf(
			'[ACP partner_feed] total=%.3fs api=%.3fs (%d uncached calls) wpquery=%.3fs (%d queries)',
			microtime( true ) - $t_render_start,
			$t_api_total,
			$api_calls,
			$t_query_total,
			$query_calls
		) );

		return '<table class="acp-partner-feed"><thead><tr><th>Partner</th><th>Authority</th><th>Case studies</th></tr></thead><tbody>'
			. $rows
			. '</tbody></table>';
	}

	/**
	 * Fetch (and cache) the authority score for a domain.
	 *
	 * Authority scores don't change minute-to-minute, so a 1-hour transient is plenty.
	 * Caching cuts a warm page render from ~1.6s (6 × 250ms API calls) to a few ms.
	 *
	 * @param string $api_base   Base URL of the authority service.
	 * @param string $domain     Domain to score.
	 * @param bool   $cache_hit  Out-param: true if the result came from cache.
	 * @return int|string The numeric score, or the string 'n/a' on error.
	 */
	private function get_authority_score( $api_base, $domain, &$cache_hit = false ) {
		$cache_key = 'acp_authority_' . md5( $api_base . '|' . $domain );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			$cache_hit = true;
			return $cached;
		}

		$cache_hit = false;
		$response  = wp_remote_get( $api_base . '?domain=' . rawurlencode( $domain ) );
		if ( is_wp_error( $response ) ) {
			return 'n/a';
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$score = isset( $body['score'] ) ? (int) $body['score'] : 'n/a';

		set_transient( $cache_key, $score, HOUR_IN_SECONDS );
		return $score;
	}
}
