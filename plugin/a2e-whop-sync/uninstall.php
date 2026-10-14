<?php
/**
 * Uninstall: remove settings, logs and the subscribers table. Plans and data on Whop are untouched.
 *
 * @package A2E_Whop_Sync
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
delete_option( 'a2ews_settings' );
delete_option( 'a2ews_log' );
delete_option( 'a2ews_seen' );
delete_option( 'a2ews_db_version' );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}a2ews_members" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
