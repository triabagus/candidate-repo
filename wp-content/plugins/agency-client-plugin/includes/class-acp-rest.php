<?php
/**
 * REST API surface for case studies.
 *
 * Stub only. The bonus task (Task 6) is to turn this into a proper read endpoint and consume
 * it from a small React/Vue widget in assets/widget/. Skip this unless you have time to spare.
 *
 * @package AgencyClient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACP_Rest {

	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'acp/v1',
			'/case-studies',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_case_studies' ),
				// TODO (Task 6): what should permission_callback be for a public read endpoint?
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_case_studies( $request ) {
		// TODO (Task 6): return published case studies (id, title, permalink, headline metric)
		// as a clean JSON response. Mind escaping, the response schema, and pagination.
		return new WP_Error(
			'not_implemented',
			'Case studies endpoint is not implemented yet.',
			array( 'status' => 501 )
		);
	}
}
