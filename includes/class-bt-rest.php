<?php
/**
 * REST API endpoint: GET /blocks-test/v1/posts
 */
defined( 'ABSPATH' ) || exit;

class BT_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'blocks-test/v1',
			'/posts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_posts' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'          => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page'      => array(
						'type'    => 'integer',
						'default' => 6,
						'minimum' => 1,
						'maximum' => 100,
					),
					'categories'    => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
					'tags'          => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
				),
			)
		);
	}

	public static function get_posts( WP_REST_Request $request ) {
		$page          = $request->get_param( 'page' );
		$per_page      = $request->get_param( 'per_page' );
		$category_ids  = array_filter( array_map( 'intval', (array) $request->get_param( 'categories' ) ) );
		$tag_ids       = array_filter( array_map( 'intval', (array) $request->get_param( 'tags' ) ) );

		$query_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $category_ids ) || ! empty( $tag_ids ) ) {
			$tax_query = array( 'relation' => 'AND' );

			if ( ! empty( $category_ids ) ) {
				$tax_query[] = array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $category_ids,
					'operator' => 'IN',
				);
			}

			if ( ! empty( $tag_ids ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_ids,
					'operator' => 'IN',
				);
			}

			$query_args['tax_query'] = $tax_query;
		}

		$query = new WP_Query( $query_args );

		$posts = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts[] = array(
					'id'         => get_the_ID(),
					'title'      => get_the_title(),
					'excerpt'    => wp_strip_all_tags( get_the_excerpt() ),
					'permalink'  => get_the_permalink(),
					'thumbnail'  => get_the_post_thumbnail_url( null, 'medium_large' ) ?: '',
					'categories' => wp_get_post_categories( get_the_ID(), array( 'fields' => 'ids' ) ),
					'tags'       => wp_get_post_tags( get_the_ID(), array( 'fields' => 'ids' ) ),
					'date'       => get_the_date( 'F j, Y' ),
				);
			}
			wp_reset_postdata();
		}

		return new WP_REST_Response( array(
			'posts'       => $posts,
			'totalPosts'  => (int) $query->found_posts,
			'totalPages'  => (int) ceil( $query->found_posts / $per_page ),
			'currentPage' => $page,
		), 200 );
	}
}
