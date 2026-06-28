<?php
/**
 * Blocks core plugin: registers blocks and enqueues shared assets.
 */
defined( 'ABSPATH' ) || exit;

class BT_Blocks {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
	}

	public static function register_blocks() {
		register_block_type(
			'blocks-test/posts-grid',
			array(
				'api_version'     => 2,
				'render_callback' => array( __CLASS__, 'render_posts_grid' ),
				'attributes'      => array(
					'columns'      => array( 'type' => 'integer', 'default' => 3 ),
					'postsPerPage' => array( 'type' => 'integer', 'default' => 6 ),
					'blockId'      => array( 'type' => 'string',  'default' => '' ),
				),
			)
		);

		register_block_type(
			'blocks-test/posts-pagination',
			array(
				'api_version'     => 2,
				'render_callback' => array( __CLASS__, 'render_posts_pagination' ),
                'parent'          => array( 'blocks-test/posts-grid' ),
				'attributes'      => array(
					'blockId' => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);

		register_block_type(
			'blocks-test/posts-filter',
			array(
				'api_version'     => 2,
				'render_callback' => array( __CLASS__, 'render_posts_filter' ),
				'attributes'      => array(
					'blockId' => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);

	}

	// -------------------------------------------------------------------------
	// Render: Posts Grid
	// -------------------------------------------------------------------------

	public static function render_posts_grid( $attributes, $content ) {
		$columns        = isset( $attributes['columns'] )      ? (int) $attributes['columns']      : 3;
		$posts_per_page = isset( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 6;
		$block_id       = ( isset( $attributes['blockId'] ) && $attributes['blockId'] )
		                  ? esc_attr( $attributes['blockId'] ) : 'bt-grid-default';

		$query = new WP_Query( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged'          => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$posts = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts[] = array(
					'id'         => get_the_ID(),
					'title'      => get_the_title(),
					'excerpt'    => wp_strip_all_tags( get_the_excerpt() ),
					'permalink'  => get_the_permalink(),
					'thumbnail'  => get_the_post_thumbnail_url( null, 'medium_large' ) ? get_the_post_thumbnail_url( null, 'medium_large' ) : '',
					'categories' => wp_get_post_categories( get_the_ID(), array( 'fields' => 'ids' ) ),
					'tags'       => wp_get_post_tags( get_the_ID(), array( 'fields' => 'ids' ) ),
					'date'       => get_the_date( 'F j, Y' ),
				);
			}
			wp_reset_postdata();
		}

		$total_pages = (int) ceil( $query->found_posts / $posts_per_page );

		$initial_data = array(
			'posts'        => $posts,
			'columns'      => $columns,
			'postsPerPage' => $posts_per_page,
			'totalPosts'   => (int) $query->found_posts,
			'totalPages'   => $total_pages,
			'currentPage'  => 1,
			'restUrl'      => esc_url( rest_url( 'blocks-test/v1/posts' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
		);

		ob_start();
		?>
		<div
			class="bt-posts-grid-wrapper"
			id="<?php echo esc_attr( $block_id ); ?>"
			data-block-id="<?php echo esc_attr( $block_id ); ?>"
			data-initial='<?php echo wp_json_encode( $initial_data ); ?>'
		>
			<div class="bt-posts-grid bt-posts-grid--cols-<?php echo (int) $columns; ?>">
				<?php foreach ( $posts as $post ) : ?>
					<article class="bt-post-card"
						data-id="<?php echo esc_attr( $post['id'] ); ?>"
						data-cats="<?php echo esc_attr( implode( ',', $post['categories'] ) ); ?>"
						data-tags="<?php echo esc_attr( implode( ',', $post['tags'] ) ); ?>"
					>
						<?php if ( $post['thumbnail'] ) : ?>
							<a href="<?php echo esc_url( $post['permalink'] ); ?>" class="bt-post-card__image-link" tabindex="-1" aria-hidden="true">
								<img src="<?php echo esc_url( $post['thumbnail'] ); ?>" alt="<?php echo esc_attr( $post['title'] ); ?>" class="bt-post-card__image" loading="lazy">
							</a>
						<?php endif; ?>
						<div class="bt-post-card__body">
							<time class="bt-post-card__date"><?php echo esc_html( $post['date'] ); ?></time>
							<h3 class="bt-post-card__title">
								<a href="<?php echo esc_url( $post['permalink'] ); ?>"><?php echo esc_html( $post['title'] ); ?></a>
							</h3>
							<p class="bt-post-card__excerpt"><?php echo esc_html( $post['excerpt'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
            <?php echo $content; ?>
		</div>
		<?php
		return ob_get_clean();
	}



	// -------------------------------------------------------------------------
	// Render: Posts Pagination (inner block of Posts Grid)
	// -------------------------------------------------------------------------

	public static function render_posts_pagination( $attributes ) {
		$block_id = ( isset( $attributes['blockId'] ) && $attributes['blockId'] )
			? esc_attr( $attributes['blockId'] ) : 'bt-pagination-default';

		ob_start();
		?>
		<nav
			class="bt-pagination"
			data-pagination-id="<?php echo esc_attr( $block_id ); ?>"
			aria-label="<?php esc_attr_e( 'Posts pagination', 'blocks-test' ); ?>"
		>
			<button class="bt-pagination__btn bt-pagination__btn--prev" disabled aria-label="<?php esc_attr_e( 'Previous page', 'blocks-test' ); ?>">
				&larr; <?php esc_html_e( 'Previous', 'blocks-test' ); ?>
			</button>
			<span class="bt-pagination__info">
				<?php printf(
					esc_html__( 'Page %1$s of %2$s', 'blocks-test' ),
					'<span class="bt-pagination__current">1</span>',
					'<span class="bt-pagination__total">1</span>'
				); ?>
			</span>
			<button class="bt-pagination__btn bt-pagination__btn--next" aria-label="<?php esc_attr_e( 'Next page', 'blocks-test' ); ?>">
				<?php esc_html_e( 'Next', 'blocks-test' ); ?> &rarr;
			</button>
		</nav>
		<?php
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Render: Posts Filter
	// -------------------------------------------------------------------------

	public static function render_posts_filter( $attributes ) {
		$block_id = ( isset( $attributes['blockId'] ) && $attributes['blockId'] )
		            ? esc_attr( $attributes['blockId'] ) : 'bt-filter-default';

		$categories = get_categories( array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		$tags = get_tags( array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		// Build serialisable arrays for the JS initialiser.
		$cats_data = array();
		foreach ( $categories as $cat ) {
			$cats_data[] = array( 'id' => $cat->term_id, 'name' => $cat->name );
		}

		$tags_data = array();
		foreach ( $tags as $tag ) {
			$tags_data[] = array( 'id' => $tag->term_id, 'name' => $tag->name );
		}

		$filter_data = array(
			'categories' => $cats_data,
			'tags'       => $tags_data,
		);

		ob_start();
		?>
		<div
			class="bt-posts-filter"
			id="<?php echo esc_attr( $block_id ); ?>"
			data-block-id="<?php echo esc_attr( $block_id ); ?>"
			data-filter-data='<?php echo wp_json_encode( $filter_data ); ?>'
		>
			<div class="bt-posts-filter__inner">

				<?php if ( ! empty( $categories ) ) : ?>
				<div class="bt-filter-group" data-filter-type="categories">
					<h4 class="bt-filter-group__label"><?php esc_html_e( 'Categories', 'blocks-test' ); ?></h4>
					<div class="bt-filter-group__chips">
						<?php foreach ( $categories as $cat ) : ?>
							<button
								type="button"
								class="bt-filter-chip"
								data-term-id="<?php echo esc_attr( $cat->term_id ); ?>"
								data-filter-type="categories"
								aria-pressed="false"
							><?php echo esc_html( $cat->name ); ?></button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $tags ) ) : ?>
				<div class="bt-filter-group" data-filter-type="tags">
					<h4 class="bt-filter-group__label"><?php esc_html_e( 'Tags', 'blocks-test' ); ?></h4>
					<div class="bt-filter-group__chips">
						<?php foreach ( $tags as $tag ) : ?>
							<button
								type="button"
								class="bt-filter-chip"
								data-term-id="<?php echo esc_attr( $tag->term_id ); ?>"
								data-filter-type="tags"
								aria-pressed="false"
							><?php echo esc_html( $tag->name ); ?></button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<button type="button" class="bt-filter-clear" hidden>
					<?php esc_html_e( 'Clear all filters', 'blocks-test' ); ?>
				</button>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Assets: Editor
	// -------------------------------------------------------------------------

	public static function enqueue_editor_assets() {
		$asset_file = BT_BLOCKS_DIR . 'assets/js/build/editor.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-server-side-render' ),
				'version'      => BT_BLOCKS_VERSION,
			);

		wp_enqueue_script(
			'blocks-test-editor',
			BT_BLOCKS_URL . 'assets/js/build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'blocks-test-editor-style',
			BT_BLOCKS_URL . 'assets/css/editor.css',
			array( 'wp-edit-blocks' ),
			BT_BLOCKS_VERSION
		);
	}

	// -------------------------------------------------------------------------
	// Assets: Frontend
	// -------------------------------------------------------------------------

	public static function enqueue_frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'blocks-test-frontend',
			BT_BLOCKS_URL . 'assets/css/frontend.css',
			array(),
			BT_BLOCKS_VERSION
		);

		wp_enqueue_script(
			'blocks-test-frontend',
			BT_BLOCKS_URL . 'assets/js/frontend.js',
			array(),
			BT_BLOCKS_VERSION,
			true
		);

		wp_add_inline_script(
			'blocks-test-frontend',
			'document.querySelectorAll(".bt-posts-grid-wrapper").forEach(function(el){
				el.dataset.restUrl = ' . wp_json_encode( esc_url( rest_url( 'blocks-test/v1/posts' ) ) ) . ';
				el.dataset.nonce   = ' . wp_json_encode( wp_create_nonce( 'wp_rest' ) ) . ';
			});',
			'before'
		);
	}
}
