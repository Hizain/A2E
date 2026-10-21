<?php
/**
 * Uninstall: remove the plugin's tables, options and stored content.
 *
 * @package A2E_Mail
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

foreach ( array( 'a2email_contacts', 'a2email_queue', 'a2email_enrollments' ) as $t ) {
	$table = $wpdb->prefix . $t;
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}

foreach ( array( 'a2email_settings', 'a2email_log', 'a2email_db_version', 'a2email_last_sync', 'a2email_templates_seeded', 'a2email_sequences_seeded', 'a2email_processing', 'a2email_delivery', 'a2email_last_fail' ) as $opt ) {
	delete_option( $opt );
}

foreach ( array( 'a2email_message', 'a2email_template', 'a2email_sequence' ) as $cpt ) {
	$ids = get_posts( array( 'post_type' => $cpt, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
}

wp_clear_scheduled_hook( 'a2email_sync_event' );
wp_clear_scheduled_hook( 'a2email_send_event' );
