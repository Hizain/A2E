<?php
/**
 * Plugin Name:       A2E Whop Sync
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       Create and manage subscription plans in WordPress — they are created on your Whop account automatically. Connect once with your Whop API key; checkout links, subscribers, renewals and cancellations sync back through a signed webhook. Works with the A2E Prime theme (plans appear on the pricing pages) and any theme via shortcodes.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-whop
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '1.0.0';
const OPTION  = 'a2ews_settings';
const CPT     = 'a2ews_plan';

define( 'A2EWS_FILE', __FILE__ );
define( 'A2EWS_DIR', plugin_dir_path( __FILE__ ) );
define( 'A2EWS_URL', plugin_dir_url( __FILE__ ) );

require_once A2EWS_DIR . 'includes/helpers.php';
require_once A2EWS_DIR . 'includes/class-api.php';
require_once A2EWS_DIR . 'includes/class-members.php';
require_once A2EWS_DIR . 'includes/class-plans.php';
require_once A2EWS_DIR . 'includes/class-webhooks.php';
require_once A2EWS_DIR . 'includes/class-admin.php';
require_once A2EWS_DIR . 'includes/class-frontend.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Activation: create the subscribers table.
 */
function activate() {
	Members::install();
	Plans::register();
	flush_rewrite_rules();
}

add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'a2ews_db_version' ) !== VERSION ) {
			Members::install();
		}
		Plans::init();
		Webhooks::init();
		Admin::init();
		Frontend::init();
	}
);
