<?php
/**
 * The sending queue. A message is snapshotted into per-recipient rows, then delivered a few at
 * a time on a one-minute schedule, within your per-minute, per-hour and per-day limits, so your
 * sending domain is never flooded.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue.
 */
class Queue {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( CRON_SEND, array( __CLASS__, 'process' ) );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'a2email_queue';
	}

	/**
	 * Create / upgrade the table.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				message_id bigint(20) unsigned NOT NULL DEFAULT 0,
				contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
				email varchar(190) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'pending',
				attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
				error varchar(255) NOT NULL DEFAULT '',
				created_at datetime NULL DEFAULT NULL,
				sent_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY message_id (message_id),
				KEY status (status),
				KEY sent_at (sent_at)
			) {$charset};"
		);
	}

	/**
	 * Snapshot a message's segment into the queue and mark it queued.
	 *
	 * @param int $message_id Message id.
	 * @return array ok, queued, message.
	 */
	public static function enqueue( $message_id ) {
		global $wpdb;
		$message = Composer::get_message( $message_id );
		if ( ! $message ) {
			return array( 'ok' => false, 'queued' => 0, 'message' => __( 'Message not found.', 'a2e-mail' ) );
		}
		if ( in_array( $message['status'], array( 'sending', 'sent' ), true ) ) {
			return array( 'ok' => false, 'queued' => 0, 'message' => __( 'This message was already sent.', 'a2e-mail' ) );
		}
		$recipients = Contacts::recipients( $message['segment'] );
		if ( ! $recipients ) {
			return array( 'ok' => false, 'queued' => 0, 'message' => __( 'No subscribed contacts match that audience yet.', 'a2e-mail' ) );
		}
		$table = self::table();
		$now   = current_time( 'mysql', true );
		// Clear any earlier pending rows for this message (re-queue safety).
		$wpdb->delete( $table, array( 'message_id' => $message_id, 'status' => 'pending' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$queued = 0;
		foreach ( $recipients as $r ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array( 'message_id' => $message_id, 'contact_id' => (int) $r['id'], 'email' => $r['email'], 'status' => 'pending', 'created_at' => $now )
			);
			$queued++;
		}
		Composer::update_message( $message_id, array( 'status' => 'queued', 'total' => $queued, 'sent_count' => 0, 'fail_count' => 0, 'queued_at' => time() ) );
		log( 'send', sprintf( __( 'Queued "%1$s" to %2$d contacts.', 'a2e-mail' ), get_the_title( $message_id ), $queued ) );
		return array( 'ok' => true, 'queued' => $queued, 'message' => sprintf( __( 'Queued to %d contacts. Delivery has started and runs in the background.', 'a2e-mail' ), $queued ) );
	}

	/**
	 * How many sends are still allowed right now, given the per-minute/hour/day caps.
	 *
	 * @return int
	 */
	public static function allowance() {
		global $wpdb;
		$table = self::table();
		$per_min  = max( 1, (int) setting( 'per_minute' ) );
		$per_hour = max( 1, (int) setting( 'per_hour' ) );
		$per_day  = max( 1, (int) setting( 'per_day' ) );
		$since = function ( $seconds ) use ( $wpdb, $table ) {
			$from = gmdate( 'Y-m-d H:i:s', time() - $seconds );
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = 'sent' AND sent_at >= %s", $from ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		};
		$room_min  = $per_min - $since( 60 );
		$room_hour = $per_hour - $since( 3600 );
		$room_day  = $per_day - $since( 86400 );
		return max( 0, min( $room_min, $room_hour, $room_day ) );
	}

	/**
	 * Process a batch (called every minute by cron, or on demand).
	 *
	 * @return int Number sent this run.
	 */
	public static function process() {
		global $wpdb;
		if ( get_transient( 'a2email_processing' ) ) {
			return 0;
		}
		set_transient( 'a2email_processing', 1, 55 );

		$allow = self::allowance();
		if ( $allow < 1 ) {
			delete_transient( 'a2email_processing' );
			return 0;
		}
		$table = self::table();
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT %d", $allow ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$sent    = 0;
		$throttle = max( 0, (int) setting( 'throttle_seconds' ) );
		$message_cache = array();

		foreach ( (array) $rows as $row ) {
			$mid = (int) $row['message_id'];
			if ( ! isset( $message_cache[ $mid ] ) ) {
				$message_cache[ $mid ] = Composer::get_message( $mid );
				Composer::update_message( $mid, array( 'status' => 'sending' ) );
			}
			$message = $message_cache[ $mid ];
			$contact = Contacts::get( (int) $row['contact_id'] );

			// Respect suppression that may have happened after queueing.
			if ( ! $message || ! $contact || Contacts::STATUS_SUBSCRIBED !== $contact['status'] ) {
				$wpdb->update( $table, array( 'status' => 'skipped', 'sent_at' => current_time( 'mysql', true ) ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}

			$render = Templates::render( $message, $contact );
			$ok     = Mailer::send( $contact['email'], $render['subject'], $render['html'], $render['text'], Unsubscribe::url( $contact['token'] ) );

			if ( $ok ) {
				$wpdb->update( $table, array( 'status' => 'sent', 'sent_at' => current_time( 'mysql', true ), 'attempts' => (int) $row['attempts'] + 1 ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				Contacts::mark_sent( (int) $contact['id'] );
				Composer::bump( $mid, 'sent_count' );
				$sent++;
			} else {
				$attempts = (int) $row['attempts'] + 1;
				$status   = $attempts >= 3 ? 'failed' : 'pending';
				$wpdb->update( $table, array( 'status' => $status, 'attempts' => $attempts, 'error' => __( 'Send failed', 'a2e-mail' ) ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( 'failed' === $status ) {
					Composer::bump( $mid, 'fail_count' );
				}
			}
			if ( $throttle && $sent < count( $rows ) ) {
				sleep( $throttle );
			}
		}

		// Mark any finished messages as sent.
		foreach ( array_keys( $message_cache ) as $mid ) {
			$pending = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE message_id = %d AND status = 'pending'", $mid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
			if ( 0 === $pending ) {
				Composer::update_message( $mid, array( 'status' => 'sent', 'finished_at' => time() ) );
				log( 'send', sprintf( __( 'Finished sending "%s".', 'a2e-mail' ), get_the_title( $mid ) ) );
			}
		}

		delete_transient( 'a2email_processing' );
		return $sent;
	}

	/**
	 * Progress for one message.
	 *
	 * @param int $message_id Message id.
	 * @return array total, sent, failed, pending.
	 */
	public static function progress( $message_id ) {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS n FROM {$table} WHERE message_id = %d GROUP BY status", $message_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$out   = array( 'total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0, 'skipped' => 0 );
		foreach ( (array) $rows as $row ) {
			$out[ $row['status'] ] = (int) $row['n'];
			$out['total']         += (int) $row['n'];
		}
		return $out;
	}

	/**
	 * Pause a message (stop sending its remaining recipients).
	 *
	 * @param int $message_id Message id.
	 */
	public static function pause( $message_id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => 'paused' ), array( 'message_id' => $message_id, 'status' => 'pending' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Composer::update_message( $message_id, array( 'status' => 'paused' ) );
	}

	/**
	 * Resume a paused message.
	 *
	 * @param int $message_id Message id.
	 */
	public static function resume( $message_id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => 'pending' ), array( 'message_id' => $message_id, 'status' => 'paused' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Composer::update_message( $message_id, array( 'status' => 'queued' ) );
	}

	/**
	 * Send a single message to one existing contact immediately (manual one-to-one).
	 *
	 * @param int $contact_id Contact id.
	 * @param array $message  subject, preheader, body.
	 * @return array ok, message.
	 */
	public static function send_one( $contact_id, $message ) {
		$contact = Contacts::get( $contact_id );
		if ( ! $contact ) {
			return array( 'ok' => false, 'message' => __( 'Contact not found.', 'a2e-mail' ) );
		}
		if ( Contacts::STATUS_SUBSCRIBED !== $contact['status'] ) {
			return array( 'ok' => false, 'message' => __( 'This contact is unsubscribed, so they are not emailed.', 'a2e-mail' ) );
		}
		$render = Templates::render( $message, $contact );
		$ok     = Mailer::send( $contact['email'], $render['subject'], $render['html'], $render['text'], Unsubscribe::url( $contact['token'] ) );
		if ( $ok ) {
			Contacts::mark_sent( $contact_id );
			log( 'send', sprintf( __( 'Sent a one-to-one message to %s.', 'a2e-mail' ), $contact['email'] ) );
			return array( 'ok' => true, 'message' => sprintf( __( 'Sent to %s.', 'a2e-mail' ), $contact['email'] ) );
		}
		return array( 'ok' => false, 'message' => __( 'The message could not be sent. Check your sending settings.', 'a2e-mail' ) );
	}
}
