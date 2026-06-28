<?php
/**
 * Seeds demo content on plugin activation and cleans up on deactivation.
 */
defined( 'ABSPATH' ) || exit;

class BT_Seeder {

	const OPTION_KEY = 'bt_blocks_seeded_ids';

	public static function activate() {
		if ( get_option( self::OPTION_KEY ) ) {
			return;
		}

		$seeded = array(
			'posts'      => array(),
			'categories' => array(),
			'tags'       => array(),
			'page'       => null,
			'images'     => array(),
		);

		// ------------------------------------------------------------------
		// 1. Categories
		// ------------------------------------------------------------------
		$cat_data = array(
			'Technology' => 'blocks-test-tech',
			'Design'     => 'blocks-test-design',
			'Business'   => 'blocks-test-business',
			'Culture'    => 'blocks-test-culture',
			'Science'    => 'blocks-test-science',
		);

		$cat_ids = array();
		foreach ( $cat_data as $name => $slug ) {
			$key      = str_replace( 'blocks-test-', '', $slug );
			$existing = get_term_by( 'slug', $slug, 'category' );
			if ( $existing ) {
				$cat_ids[ $key ]        = $existing->term_id;
				$seeded['categories'][] = $existing->term_id;
			} else {
				$result = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $result ) ) {
					$cat_ids[ $key ]        = $result['term_id'];
					$seeded['categories'][] = $result['term_id'];
				}
			}
		}

		// ------------------------------------------------------------------
		// 2. Tags
		// ------------------------------------------------------------------
		$tag_data = array(
			'AI'             => 'blocks-test-ai',
			'Innovation'     => 'blocks-test-innovation',
			'Remote Work'    => 'blocks-test-remote-work',
			'Sustainability' => 'blocks-test-sustainability',
			'Startups'       => 'blocks-test-startups',
			'Open Source'    => 'blocks-test-open-source',
			'UX'             => 'blocks-test-ux',
			'Data'           => 'blocks-test-data',
		);

		$tag_ids = array();
		foreach ( $tag_data as $name => $slug ) {
			$key      = str_replace( 'blocks-test-', '', $slug );
			$existing = get_term_by( 'slug', $slug, 'post_tag' );
			if ( $existing ) {
				$tag_ids[ $key ]    = $existing->term_id;
				$seeded['tags'][]   = $existing->term_id;
			} else {
				$result = wp_insert_term( $name, 'post_tag', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $result ) ) {
					$tag_ids[ $key ]  = $result['term_id'];
					$seeded['tags'][] = $result['term_id'];
				}
			}
		}

		// ------------------------------------------------------------------
		// 3. Generate featured images as PNG via GD
		// ------------------------------------------------------------------
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$palettes = array(
			array( 79,  70,  229 ),
			array(  8, 145, 178 ),
			array( 22, 163,  74 ),
			array( 217, 119,  6 ),
			array( 190,  24,  93 ),
			array( 124,  58, 237 ),
			array( 220,  38,  38 ),
			array(  15, 118, 110 ),
			array( 180,  83,   9 ),
			array( 29,  78, 216 ),
			array( 21, 128,  61 ),
			array( 147,  51, 234 ),
		);

		$labels = array(
			'AI & Dev', 'Accessibility', 'Remote Work', 'Open Source',
			'Sustainability', 'Data', 'Sleep & Science', 'UX Design',
			'Startups', 'Climate Tech', 'AI Ethics', 'Design Systems',
		);

		$upload_dir     = wp_upload_dir();
		$attachment_ids = array();

		for ( $i = 0; $i < 12; $i++ ) {
			$rgb   = $palettes[ $i ];
			$label = isset( $labels[ $i ] ) ? $labels[ $i ] : 'Post ' . ( $i + 1 );

			$att_id = self::create_png_attachment( $i + 1, $rgb, $label, $upload_dir );
			$attachment_ids[] = $att_id ? $att_id : 0;

			if ( $att_id ) {
				$seeded['images'][] = $att_id;
			}
		}

		// ------------------------------------------------------------------
		// 4. Posts
		// ------------------------------------------------------------------
		$t = $cat_ids;
		$g = $tag_ids;

		$posts_data = array(
			array(
				'title'      => 'The Rise of AI-Powered Development Tools',
				'excerpt'    => 'How artificial intelligence is reshaping the way engineers write, review, and maintain code across the industry.',
				'categories' => array( isset( $t['tech'] ) ? $t['tech'] : 0 ),
				'tags'       => array_filter( array( isset( $g['ai'] ) ? $g['ai'] : 0, isset( $g['innovation'] ) ? $g['innovation'] : 0, isset( $g['open-source'] ) ? $g['open-source'] : 0 ) ),
				'img_index'  => 0,
			),
			array(
				'title'      => 'Designing for Accessibility in 2024',
				'excerpt'    => 'Accessibility is no longer optional. Practical patterns that make products usable by everyone from day one.',
				'categories' => array_filter( array( isset( $t['design'] ) ? $t['design'] : 0, isset( $t['tech'] ) ? $t['tech'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['ux'] ) ? $g['ux'] : 0, isset( $g['innovation'] ) ? $g['innovation'] : 0 ) ),
				'img_index'  => 1,
			),
			array(
				'title'      => 'Remote Work Culture: What Actually Works',
				'excerpt'    => 'After years of distributed teams, what distinguishes high-performing remote organisations.',
				'categories' => array_filter( array( isset( $t['business'] ) ? $t['business'] : 0, isset( $t['culture'] ) ? $t['culture'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['remote-work'] ) ? $g['remote-work'] : 0, isset( $g['startups'] ) ? $g['startups'] : 0 ) ),
				'img_index'  => 2,
			),
			array(
				'title'      => 'Open Source Business Models That Scale',
				'excerpt'    => 'From dual-licensing to managed services, every viable path to revenue for open source founders.',
				'categories' => array_filter( array( isset( $t['business'] ) ? $t['business'] : 0, isset( $t['tech'] ) ? $t['tech'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['open-source'] ) ? $g['open-source'] : 0, isset( $g['startups'] ) ? $g['startups'] : 0 ) ),
				'img_index'  => 3,
			),
			array(
				'title'      => 'Sustainable Design: Beauty With a Smaller Footprint',
				'excerpt'    => 'Designers rethinking material choices, production cycles, and digital carbon budgets.',
				'categories' => array_filter( array( isset( $t['design'] ) ? $t['design'] : 0, isset( $t['culture'] ) ? $t['culture'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['sustainability'] ) ? $g['sustainability'] : 0, isset( $g['ux'] ) ? $g['ux'] : 0 ) ),
				'img_index'  => 4,
			),
			array(
				'title'      => 'Data Pipelines at Scale: Lessons From the Trenches',
				'excerpt'    => 'Building reliable real-time data infrastructure is harder than it looks.',
				'categories' => array_filter( array( isset( $t['tech'] ) ? $t['tech'] : 0, isset( $t['science'] ) ? $t['science'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['data'] ) ? $g['data'] : 0, isset( $g['ai'] ) ? $g['ai'] : 0 ) ),
				'img_index'  => 5,
			),
			array(
				'title'      => 'The Science of Sleep and Productivity',
				'excerpt'    => 'Neuroscience is dismantling the hustle-culture myth one study at a time.',
				'categories' => array_filter( array( isset( $t['science'] ) ? $t['science'] : 0, isset( $t['culture'] ) ? $t['culture'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['sustainability'] ) ? $g['sustainability'] : 0, isset( $g['remote-work'] ) ? $g['remote-work'] : 0 ) ),
				'img_index'  => 6,
			),
			array(
				'title'      => 'UX Patterns for Complex Data Dashboards',
				'excerpt'    => 'Interface patterns that surface insight without hiding power for analyst users.',
				'categories' => array_filter( array( isset( $t['design'] ) ? $t['design'] : 0, isset( $t['tech'] ) ? $t['tech'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['ux'] ) ? $g['ux'] : 0, isset( $g['data'] ) ? $g['data'] : 0 ) ),
				'img_index'  => 7,
			),
			array(
				'title'      => 'Why Startups Should Bet on Open Source Early',
				'excerpt'    => 'Open-sourcing your core gives contributors, credibility, and a moat competitors cannot replicate.',
				'categories' => array_filter( array( isset( $t['business'] ) ? $t['business'] : 0, isset( $t['tech'] ) ? $t['tech'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['startups'] ) ? $g['startups'] : 0, isset( $g['open-source'] ) ? $g['open-source'] : 0, isset( $g['innovation'] ) ? $g['innovation'] : 0 ) ),
				'img_index'  => 8,
			),
			array(
				'title'      => 'Climate Tech Startups Reshaping Energy',
				'excerpt'    => 'A new wave of founders attacking the hardest parts of the energy transition with software.',
				'categories' => array_filter( array( isset( $t['business'] ) ? $t['business'] : 0, isset( $t['science'] ) ? $t['science'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['startups'] ) ? $g['startups'] : 0, isset( $g['sustainability'] ) ? $g['sustainability'] : 0, isset( $g['innovation'] ) ? $g['innovation'] : 0 ) ),
				'img_index'  => 9,
			),
			array(
				'title'      => 'AI Ethics: Who Decides What Is Fair?',
				'excerpt'    => 'As AI systems make higher-stakes decisions, defining fairness has never been more urgent.',
				'categories' => array_filter( array( isset( $t['science'] ) ? $t['science'] : 0, isset( $t['culture'] ) ? $t['culture'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['ai'] ) ? $g['ai'] : 0, isset( $g['data'] ) ? $g['data'] : 0 ) ),
				'img_index'  => 10,
			),
			array(
				'title'      => 'Design Systems: The Foundation of Fast Teams',
				'excerpt'    => 'A well-maintained design system cuts decision fatigue and accelerates shipping.',
				'categories' => array_filter( array( isset( $t['design'] ) ? $t['design'] : 0, isset( $t['business'] ) ? $t['business'] : 0 ) ),
				'tags'       => array_filter( array( isset( $g['ux'] ) ? $g['ux'] : 0, isset( $g['innovation'] ) ? $g['innovation'] : 0, isset( $g['open-source'] ) ? $g['open-source'] : 0 ) ),
				'img_index'  => 11,
			),
		);

		foreach ( $posts_data as $data ) {
			$post_id = wp_insert_post( array(
				'post_title'   => $data['title'],
				'post_excerpt' => $data['excerpt'],
				'post_content' => self::lorem( 4 ),
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			) );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			$seeded['posts'][] = $post_id;

			$cats = array_filter( $data['categories'] );
			if ( ! empty( $cats ) ) {
				wp_set_post_categories( $post_id, $cats );
			}

			$tags = array_filter( $data['tags'] );
			if ( ! empty( $tags ) ) {
				wp_set_post_tags( $post_id, $tags, false );
			}

			$img_id = isset( $attachment_ids[ $data['img_index'] ] ) ? $attachment_ids[ $data['img_index'] ] : 0;
			if ( $img_id ) {
				set_post_thumbnail( $post_id, $img_id );
			}
		}

		// ------------------------------------------------------------------
		// 5. Demo page
		// ------------------------------------------------------------------
        $page_content  = '<!-- wp:blocks-test/posts-filter {"blockId":"bt-filter-1"} /-->' . "\n\n";
        $page_content .= '<!-- wp:blocks-test/posts-grid {"blockId":"bt-grid-1","columns":3,"postsPerPage":6} -->' . "\n";
        $page_content .= '<!-- wp:blocks-test/posts-pagination {"blockId":"bt-pagination-1"} /-->' . "\n";
        $page_content .= '<!-- /wp:blocks-test/posts-grid -->';

		$page_id = wp_insert_post( array(
			'post_title'   => 'Blocks Test Demo',
			'post_name'    => 'blocks-test-demo',
			'post_content' => $page_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
		) );

		if ( ! is_wp_error( $page_id ) ) {
			$seeded['page'] = $page_id;
		}

		update_option( self::OPTION_KEY, $seeded );
		flush_rewrite_rules();
	}

	// -------------------------------------------------------------------------
	// Deactivation
	// -------------------------------------------------------------------------

	public static function deactivate() {
		$seeded = get_option( self::OPTION_KEY );
		if ( ! $seeded ) {
			return;
		}

		foreach ( (array) $seeded['posts'] as $id ) {
			wp_delete_post( $id, true );
		}
		if ( ! empty( $seeded['page'] ) ) {
			wp_delete_post( $seeded['page'], true );
		}
		foreach ( (array) $seeded['categories'] as $id ) {
			wp_delete_term( $id, 'category' );
		}
		foreach ( (array) $seeded['tags'] as $id ) {
			wp_delete_term( $id, 'post_tag' );
		}
		foreach ( (array) $seeded['images'] as $id ) {
			wp_delete_attachment( $id, true );
		}

		delete_option( self::OPTION_KEY );
		flush_rewrite_rules();
	}

	// -------------------------------------------------------------------------
	// Create a PNG attachment using Graphics draw
	// -------------------------------------------------------------------------

	private static function create_png_attachment( $index, $rgb, $label, $upload_dir ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$filename = 'bt-demo-' . $index . '.png';
		$filepath = trailingslashit( $upload_dir['path'] ) . $filename;
		$fileurl  = trailingslashit( $upload_dir['url'] ) . $filename;

		if ( function_exists( 'imagecreatetruecolor' ) ) {
			$img = imagecreatetruecolor( 800, 500 );

			$r  = $rgb[0];
			$g  = $rgb[1];
			$b  = $rgb[2];

			// Background — solid color.
			$bg_col = imagecolorallocate( $img, $r, $g, $b );
			imagefill( $img, 0, 0, $bg_col );

			// lighter overlay strip at the bottom.
			$overlay = imagecolorallocatealpha( $img, 255, 255, 255, 100 );
			imagefilledrectangle( $img, 0, 380, 800, 500, $overlay );

			// Large circle — decorative.
			$circle_col = imagecolorallocatealpha( $img, 255, 255, 255, 90 );
			imagefilledellipse( $img, 400, 210, 180, 180, $circle_col );

			// Inner circle.
			$inner_col = imagecolorallocatealpha( $img, 255, 255, 255, 70 );
			imagefilledellipse( $img, 400, 210, 110, 110, $inner_col );

			// Label text — white, centred.
			$text_col = imagecolorallocate( $img, 255, 255, 255 );

			// Use built-in GD font.
			$font      = 5;
			$char_w    = imagefontwidth( $font );
			$char_h    = imagefontheight( $font );
			$text_w    = strlen( $label ) * $char_w;
			$text_x    = (int) ( ( 800 - $text_w ) / 2 );
			$text_y    = 330 - (int) ( $char_h / 2 );

			imagestring( $img, $font, $text_x, $text_y, $label, $text_col );

			ob_start();
			imagepng( $img );
			$png_data = ob_get_clean();
			imagedestroy( $img );

		} else {
			// GD unavailable — write a minimal valid 1×1 white PNG.
			$png_data = base64_decode(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg=='
			);
		}

		if ( false === file_put_contents( $filepath, $png_data ) ) {
			return 0;
		}

		$att_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_title'     => 'Blocks Test Demo — ' . $label,
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $fileurl,
			),
			$filepath
		);

		if ( is_wp_error( $att_id ) ) {
			return 0;
		}

		$metadata = wp_generate_attachment_metadata( $att_id, $filepath );
		wp_update_attachment_metadata( $att_id, $metadata );

		return $att_id;
	}

    // Use a demo text for the content
	private static function lorem( $paragraphs = 3 ) {
		$variants = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
			'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper.',
			'Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi.',
			'Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis molestie dictum semper, quam quam congue erat, id aliquam sapien purus quis leo.',
		);

		$out = '';
		for ( $i = 0; $i < $paragraphs; $i++ ) {
			$out .= '<p>' . $variants[ $i % count( $variants ) ] . '</p>' . "\n";
		}
		return trim( $out );
	}
}
