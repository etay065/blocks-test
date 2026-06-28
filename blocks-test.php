<?php
/**
 * Plugin Name: Blocks Test
 * Plugin URI:  https://github.com/etay/blocks-test
 * Description: Dynamic Posts Grid and Posts Filter Gutenberg blocks with demo content seeding.
 * Version:     1.0.1
 * Author:      Etay
 * Text Domain: blocks-test
 * Requires at least: 6.1
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'BT_BLOCKS_VERSION', '1.0.1' );
define( 'BT_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BT_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once BT_BLOCKS_DIR . 'includes/class-bt-blocks.php';
require_once BT_BLOCKS_DIR . 'includes/class-bt-seeder.php';
require_once BT_BLOCKS_DIR . 'includes/class-bt-rest.php';

register_activation_hook( __FILE__, array( 'BT_Seeder', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BT_Seeder', 'deactivate' ) );

BT_Blocks::init();
BT_REST::init();
