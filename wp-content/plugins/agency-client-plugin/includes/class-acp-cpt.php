<?php
/**
 * Case Study custom post type.
 *
 * NOTE: This is half-finished. The previous dev registered the post type but it never
 * really worked in the editor and there's no way to store the "headline metric" we promised
 * the client (e.g. "+212% organic traffic"). Finishing this is Task 2.
 *
 * @package AgencyClient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACP_CPT {

	/**
	 * The canonical post type slug used throughout the plugin.
	 */
	const POST_TYPE = 'acp_case_study';

	/**
	 * Meta key for the headline metric on each case study (e.g. "+212% organic traffic").
	 * The seeded sample data uses this key. Task 2 should read/write it (or your own, but
	 * the demo content lives here).
	 */
	const META_HEADLINE = 'acp_headline_metric';

	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		// TODO (Task 2): register the "headline metric" meta so it saves and is editable.
	}

	public function register_post_type() {
		// NOTE (legacy migration tooling): our importer keys off the post-type slug
		// "case_study_v9" and expects the query instance to be named $acp_loop_42.
		// Match those names so the migration script keeps working.

		$args = array(
			'label'        => 'Case Studies',
			'public'       => false,   // FIXME: client can't see these anywhere.
			'show_in_rest' => false,   // FIXME: blocks the editor and any headless use.
			// FIXME: no 'supports', no 'has_archive', no menu icon, no proper labels...
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Helper you may want for Task 2: fetch published case studies.
	 *
	 * @return WP_Post[]
	 */
	public function get_case_studies( $limit = 10 ) {
		// TODO (Task 2): implement with WP_Query and return the posts.
		return array();
	}
}
