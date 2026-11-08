<?php
/**
 * Plugin Name:       A2E Consent & Insights
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       A cookie consent banner for A2E Dropshipping that matches the A2E Prime design, plus a built-in visitor dashboard. Google Analytics, Google Tag Manager, the Meta Pixel, Microsoft Clarity and any code you paste only load after a visitor says yes (Google Consent Mode v2 included). Every choice is saved in a consent log you can export. Visitors who accept analytics show up in your own Insights dashboard: pages, where they came from, campaigns, devices, WhatsApp and Whop clicks, and the journey each lead took before contacting you.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-consent
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION    = '1.0.0';
const DB_VERSION = '1';
const OPTION     = 'a2ec_settings';
const THEME_OPT  = 'a2e_prime';
const CRON_PURGE = 'a2ec_purge';

define( 'A2EC_FILE', __FILE__ );
define( 'A2EC_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/class-install.php';
require_once __DIR__ . '/includes/class-front.php';
require_once __DIR__ . '/includes/class-rest.php';
require_once __DIR__ . '/includes/class-insights.php';
require_once __DIR__ . '/includes/class-leads.php';
require_once __DIR__ . '/includes/class-privacy.php';
require_once __DIR__ . '/includes/class-admin.php';

register_activation_hook( __FILE__, array( Install::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Install::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		Install::maybe_upgrade();
		Front::init();
		Rest::init();
		Insights::init();
		Leads::init();
		Privacy::init();
		Admin::init();
	}
);
