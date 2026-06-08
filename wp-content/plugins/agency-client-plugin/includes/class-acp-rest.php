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
		add_action( 'init', array( $this, 'register_widget_shortcode' ) );
	}

	public function register_routes() {
		register_rest_route(
			'acp/v1',
			'/case-studies',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_case_studies' ),
				// Public read endpoint — case studies are marketing content, no auth required,
				// no write surface here.
				'permission_callback' => '__return_true',
				'args'                => array(
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 10,
						'minimum'           => 1,
						'maximum'           => 50,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_case_studies( $request ) {
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$query = new WP_Query( array(
			'post_type'      => ACP_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		) );

		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = array(
				'id'              => (int) $post->ID,
				'title'           => wp_strip_all_tags( get_the_title( $post ) ),
				'permalink'       => (string) get_permalink( $post ),
				'headline_metric' => (string) get_post_meta( $post->ID, ACP_CPT::META_HEADLINE, true ),
			);
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );
		return $response;
	}

	public function register_widget_shortcode() {
		add_shortcode( 'acp_case_studies_widget', array( $this, 'render_widget' ) );
	}

	/**
	 * Output the widget mount point + enqueue React, htm, and the small index.js that
	 * consumes the REST endpoint above. Scripts are pulled from a CDN so no build step is
	 * required. They're only enqueued on pages where the shortcode is actually used.
	 */
	public function render_widget( $atts ) {
		$atts = shortcode_atts(
			array( 'per_page' => 10 ),
			$atts,
			'acp_case_studies_widget'
		);

		wp_enqueue_script( 'react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18', true );
		wp_enqueue_script( 'react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array( 'react' ), '18', true );
		wp_enqueue_script( 'htm', 'https://unpkg.com/htm@3.1.1/dist/htm.umd.js', array(), '3.1.1', true );
		wp_enqueue_script(
			'acp-case-studies-widget',
			ACP_URL . 'assets/widget/index.js',
			array( 'react', 'react-dom', 'htm' ),
			ACP_VERSION,
			true
		);

		$config = array(
			'restUrl' => esc_url_raw( rest_url( 'acp/v1/case-studies' ) ),
			'perPage' => (int) $atts['per_page'],
		);
		wp_add_inline_script(
			'acp-case-studies-widget',
			'window.ACP_CASE_STUDIES = ' . wp_json_encode( $config ) . ';',
			'before'
		);

		return '<div id="acp-case-studies-widget" class="acp-csw"></div>';
	}
}
