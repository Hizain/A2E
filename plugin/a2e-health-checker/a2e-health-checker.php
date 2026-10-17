<?php
/**
 * Plugin Name:       A2E eBay Account Health Checker
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       A free, interactive eBay account health checker. With the A2E Prime theme it becomes one of the theme's free tools (header menu, footer, Free Tools page, homepage) with its own /free-tools/ page; it can also be placed on any page with the [a2e_health_checker] shortcode or the block. It scores an account against eBay seller standards, shows how many clean transactions are needed to recover Top Rated or Above Standard and roughly when, gives tailored improvement suggestions, and offers a one-tap WhatsApp button to hire your team. Works with any theme and matches the A2E Prime style when it is active. Does not change your theme.
 * Version:           2.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-health
 *
 * @package A2E_Health
 */

namespace A2E\Health;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '2.1.0';
const OPTION  = 'a2ehc_settings';
const CPT     = 'a2ehc_lead';

define( 'A2EHC_FILE', __FILE__ );
define( 'A2EHC_DIR', plugin_dir_path( __FILE__ ) );
define( 'A2EHC_URL', plugin_dir_url( __FILE__ ) );

require_once A2EHC_DIR . 'includes/helpers.php';
require_once A2EHC_DIR . 'includes/class-widget.php';
require_once A2EHC_DIR . 'includes/class-leads.php';
require_once A2EHC_DIR . 'includes/class-admin.php';
require_once A2EHC_DIR . 'includes/class-theme.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Activation: create a ready page holding the shortcode, so it can be assigned anywhere.
 */
function activate() {
	Leads::install();
	if ( ! get_option( 'a2ehc_page_id' ) ) {
		$existing = get_page_by_path( 'ebay-account-health-checker' );
		if ( $existing ) {
			update_option( 'a2ehc_page_id', (int) $existing->ID, false );
		} else {
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'eBay Account Health Checker', 'a2e-health' ),
					'post_name'    => 'ebay-account-health-checker',
					'post_content' => "<!-- wp:shortcode -->\n[a2e_health_checker]\n<!-- /wp:shortcode -->",
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				update_option( 'a2ehc_page_id', (int) $id, false );
			}
		}
	}
}

add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'a2ehc_db_version' ) !== VERSION ) {
			Leads::install();
			update_option( 'a2ehc_db_version', VERSION, false );
		}
		Widget::init();
		Leads::init();
		Admin::init();
		Theme::init();
	}
);
