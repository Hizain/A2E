<?php
/**
 * Remove everything when the plugin is deleted.
 *
 * @package A2E_Consent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'a2ec_hits' ); // phpcs:ignore WordPress.DB
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'a2ec_consent_log' ); // phpcs:ignore WordPress.DB
delete_option( 'a2ec_settings' );
delete_option( 'a2ec_db_version' );
delete_post_meta_by_key( '_a2ec_journey' );
delete_post_meta_by_key( '_a2ec_vid' );
wp_clear_scheduled_hook( 'a2ec_purge' );
