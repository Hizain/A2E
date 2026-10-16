<?php
/**
 * Plugin Name:       A2E Mail Studio
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       Design and send professional, theme-styled emails to the people you already have a relationship with: your Whop clients and everyone who contacted you or subscribed through your website. Compose one-off messages, promotions and updates, preview them, send a test, and let the queue deliver them safely from your own address. Every message carries your WhatsApp, phone, email and a one-click unsubscribe.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-mail
 *
 * This plugin only ever emails contacts you already have a relationship with (your clients,
 * people who used your forms, and newsletter subscribers), plus anyone you add by hand and
 * confirm you may contact. It does not collect addresses from the web or import outside lists.
 * Every message includes a one-click unsubscribe and honours your suppression list, so your
 * sending domain stays trusted.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION   = '1.0.0';
const OPTION    = 'a2email_settings';
const CPT       = 'a2email_message';
const TEMPLATE  = 'a2email_template';
const CRON_SYNC = 'a2email_sync_event';
const CRON_SEND = 'a2email_send_event';

define( 'A2EMAIL_FILE', __FILE__ );
define( 'A2EMAIL_DIR', plugin_dir_path( __FILE__ ) );
define( 'A2EMAIL_URL', plugin_dir_url( __FILE__ ) );

require_once A2EMAIL_DIR . 'includes/helpers.php';
require_once A2EMAIL_DIR . 'includes/class-contacts.php';
require_once A2EMAIL_DIR . 'includes/class-templates.php';
require_once A2EMAIL_DIR . 'includes/class-mailer.php';
require_once A2EMAIL_DIR . 'includes/class-queue.php';
require_once A2EMAIL_DIR . 'includes/class-unsubscribe.php';
require_once A2EMAIL_DIR . 'includes/class-composer.php';
require_once A2EMAIL_DIR . 'includes/class-admin.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

/**
 * Activation: tables, default templates, schedules.
 */
function activate() {
	Contacts::install();
	Queue::install();
	Templates::seed();
	if ( ! wp_next_scheduled( CRON_SYNC ) ) {
		wp_schedule_event( time() + 120, 'a2email_5min', CRON_SYNC );
	}
	if ( ! wp_next_scheduled( CRON_SEND ) ) {
		wp_schedule_event( time() + 60, 'a2email_1min', CRON_SEND );
	}
}

/**
 * Deactivation: clear schedules.
 */
function deactivate() {
	wp_clear_scheduled_hook( CRON_SYNC );
	wp_clear_scheduled_hook( CRON_SEND );
}

/**
 * Extra cron schedules (1 minute and 5 minutes).
 *
 * @param array $schedules Schedules.
 * @return array
 */
function cron_schedules( $schedules ) {
	$schedules['a2email_1min'] = array( 'interval' => 60, 'display' => __( 'Every minute (A2E Mail)', 'a2e-mail' ) );
	$schedules['a2email_5min'] = array( 'interval' => 300, 'display' => __( 'Every 5 minutes (A2E Mail)', 'a2e-mail' ) );
	return $schedules;
}
add_filter( 'cron_schedules', __NAMESPACE__ . '\\cron_schedules' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected

add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'a2email_db_version' ) !== VERSION ) {
			Contacts::install();
			Queue::install();
			Templates::seed();
			update_option( 'a2email_db_version', VERSION, false );
		}
		Contacts::init();
		Templates::init();
		Queue::init();
		Unsubscribe::init();
		Composer::init();
		Admin::init();
	}
);
