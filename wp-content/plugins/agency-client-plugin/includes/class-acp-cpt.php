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
	 */
	const META_HEADLINE = 'acp_headline_metric';

	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_headline_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_headline_meta' ), 10, 2 );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => 'Case Studies',
			'singular_name'      => 'Case Study',
			'menu_name'          => 'Case Studies',
			'name_admin_bar'     => 'Case Study',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Case Study',
			'new_item'           => 'New Case Study',
			'edit_item'          => 'Edit Case Study',
			'view_item'          => 'View Case Study',
			'all_items'          => 'All Case Studies',
			'search_items'       => 'Search Case Studies',
			'not_found'          => 'No case studies found.',
			'not_found_in_trash' => 'No case studies found in Trash.',
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 20,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'rewrite'       => array( 'slug' => 'case-studies', 'with_front' => false ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Expose the headline metric to REST and the block editor with sane sanitization.
	 */
	public function register_meta() {
		register_post_meta(
			self::POST_TYPE,
			self::META_HEADLINE,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function ( $allowed, $meta_key, $object_id ) {
					return current_user_can( 'edit_post', $object_id );
				},
			)
		);
	}

	public function add_headline_meta_box() {
		add_meta_box(
			'acp_headline_metric_box',
			'Headline metric',
			array( $this, 'render_headline_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	public function render_headline_meta_box( $post ) {
		$value = (string) get_post_meta( $post->ID, self::META_HEADLINE, true );
		wp_nonce_field( 'acp_save_headline_metric', 'acp_headline_metric_nonce' );
		?>
		<label for="acp_headline_metric_field" class="screen-reader-text">Headline metric</label>
		<input
			type="text"
			id="acp_headline_metric_field"
			name="<?php echo esc_attr( self::META_HEADLINE ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="e.g. +212% organic traffic in 6 months"
			style="width:100%"
		/>
		<p class="description">A short stat shown alongside this case study in feeds and lists.</p>
		<?php
	}

	public function save_headline_meta( $post_id, $post ) {
		if ( ! isset( $_POST['acp_headline_metric_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['acp_headline_metric_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'acp_save_headline_metric' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST[ self::META_HEADLINE ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::META_HEADLINE ] ) )
			: '';
		update_post_meta( $post_id, self::META_HEADLINE, $value );
	}

	/**
	 * @return WP_Post[]
	 */
	public function get_case_studies( $limit = 10 ) {
		$query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'no_found_rows'  => true,
		) );
		return $query->posts;
	}
}
