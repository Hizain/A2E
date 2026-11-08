<?php
/**
 * Tables, the daily clean-up and upgrades.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install.
 */
class Install {

	/**
	 * Activation.
	 */
	public static function activate() {
		self::tables();
		if ( false === get_option( OPTION, false ) ) {
			update_option( OPTION, defaults(), false );
		}
		if ( ! wp_next_scheduled( CRON_PURGE ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', CRON_PURGE );
		}
	}

	/**
	 * Deactivation: stop the clean-up job (your data stays).
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( CRON_PURGE );
	}

	/**
	 * Create or update tables when the plugin was updated without re-activation.
	 */
	public static function maybe_upgrade() {
		if ( DB_VERSION !== get_option( 'a2ec_db_version' ) ) {
			self::tables();
		}
		if ( ! wp_next_scheduled( CRON_PURGE ) && ! wp_installing() ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', CRON_PURGE );
		}
	}

	/**
	 * Create the tables.
	 */
	public static function tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$hits    = table( 'hits' );
		$log     = table( 'log' );

		dbDelta(
			"CREATE TABLE {$hits} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created datetime NOT NULL,
			day date NOT NULL,
			visitor char(16) NOT NULL,
			session char(16) NOT NULL,
			type varchar(10) NOT NULL DEFAULT 'view',
			path varchar(190) NOT NULL DEFAULT '',
			title varchar(190) NOT NULL DEFAULT '',
			target varchar(190) NOT NULL DEFAULT '',
			source varchar(60) NOT NULL DEFAULT '',
			medium varchar(30) NOT NULL DEFAULT '',
			campaign varchar(100) NOT NULL DEFAULT '',
			ref_host varchar(100) NOT NULL DEFAULT '',
			device varchar(10) NOT NULL DEFAULT '',
			country char(2) NOT NULL DEFAULT '',
			entry tinyint(1) NOT NULL DEFAULT 0,
			seconds int(10) unsigned NOT NULL DEFAULT 0,
			lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY day (day),
			KEY visitor (visitor),
			KEY session (session)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$log} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created datetime NOT NULL,
			consent_id char(16) NOT NULL,
			choice varchar(12) NOT NULL DEFAULT '',
			analytics tinyint(1) NOT NULL DEFAULT 0,
			marketing tinyint(1) NOT NULL DEFAULT 0,
			version int(10) unsigned NOT NULL DEFAULT 1,
			gpc tinyint(1) NOT NULL DEFAULT 0,
			path varchar(190) NOT NULL DEFAULT '',
			browser varchar(80) NOT NULL DEFAULT '',
			country char(2) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY created (created),
			KEY consent_id (consent_id)
			) {$charset};"
		);

		update_option( 'a2ec_db_version', DB_VERSION, false );
	}
}
