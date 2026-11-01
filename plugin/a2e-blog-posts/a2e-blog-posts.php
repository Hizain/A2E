<?php
/**
 * Plugin Name:       A2E Blog Posts
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       Shows your latest blog posts as a designed "From the blog" section on the homepage (near the bottom, before the final call to action) and on any other page you choose: specific pages, every page, service guides, blog posts or category pages. Pick which categories each section shows, how many posts, the order, columns, heading and button. Also places anywhere with the [a2e_blog_posts] shortcode, and fixes the text alignment of the blog's category buttons. Matches the A2E Prime design in light and dark mode. Nothing in the theme is edited.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-blog-posts
 *
 * @package A2E_Blog_Posts
 */

namespace A2E\BlogPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION   = '1.0.0';
const OPTION    = 'a2ebp_settings';
const THEME_OPT = 'a2e_prime';

define( 'A2EBP_FILE', __FILE__ );
define( 'A2EBP_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/class-render.php';
require_once __DIR__ . '/includes/class-placement.php';
require_once __DIR__ . '/includes/class-admin.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * First activation: one ready section that puts the latest posts on the homepage.
 */
function activate() {
	if ( false === get_option( OPTION, false ) ) {
		update_option( OPTION, default_settings(), false );
	}
}

add_action(
	'plugins_loaded',
	function () {
		Placement::init();
		Render::init();
		Admin::init();
	}
);
