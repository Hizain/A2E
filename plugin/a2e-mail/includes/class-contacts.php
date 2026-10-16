<?php
/**
 * Contacts: people you already have a relationship with.
 *
 * Contacts come only from three places:
 *   1. Your website forms (quote, consultation, contact and newsletter), read from the theme's
 *      Leads, where the person contacted you first.
 *   2. Your Whop clients, read from the A2E Whop Sync subscribers table.
 *   3. Anyone you add by hand, where you confirm you already have permission to email them.
 *
 * There is no web scraping and no importing of outside lists. Unsubscribes and bounces are
 * suppressed and never emailed again.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contacts.
 */
class Contacts {

	const STATUS_SUBSCRIBED   = 'subscribed';
	const STATUS_UNSUBSCRIBED = 'unsubscribed';
	const STATUS_BOUNCED      = 'bounced';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( CRON_SYNC, array( __CLASS__, 'sync' ) );
		// Capture new website leads the moment they arrive.
		add_action( 'a2e_lead_received', array( __CLASS__, 'on_lead' ), 10, 4 );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'a2email_contacts';
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
				email varchar(190) NOT NULL,
				name varchar(190) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'subscribed',
				source varchar(40) NOT NULL DEFAULT '',
				consent_note varchar(255) NOT NULL DEFAULT '',
				tags varchar(255) NOT NULL DEFAULT '',
				token varchar(64) NOT NULL DEFAULT '',
				wp_lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
				whop_id varchar(64) NOT NULL DEFAULT '',
				last_sent datetime NULL DEFAULT NULL,
				unsubscribed_at datetime NULL DEFAULT NULL,
				created_at datetime NULL DEFAULT NULL,
				updated_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY email (email),
				KEY status (status),
				KEY source (source),
				KEY token (token)
			) {$charset};"
		);
	}

	/**
	 * The segments a message can target: machine key => label + live count.
	 *
	 * @return array
	 */
	public static function segments() {
		$base = array(
			'all'          => __( 'Everyone subscribed', 'a2e-mail' ),
			'clients'      => __( 'Clients (from Whop)', 'a2e-mail' ),
			'newsletter'   => __( 'Newsletter subscribers', 'a2e-mail' ),
			'form_leads'   => __( 'People who used a form (audit, consultation, contact)', 'a2e-mail' ),
			'health_check' => __( 'Ran the account health checker', 'a2e-mail' ),
			'manual'       => __( 'Contacts you added by hand', 'a2e-mail' ),
		);
		$out = array();
		foreach ( $base as $key => $label ) {
			$out[ $key ] = array( 'label' => $label, 'count' => self::count_for_segment( $key ) );
		}
		return $out;
	}

	/**
	 * The WHERE clause for a segment (subscribed only, always).
	 *
	 * @param string $segment Segment key.
	 * @return string
	 */
	protected static function segment_where( $segment ) {
		global $wpdb;
		$where = $wpdb->prepare( 'status = %s', self::STATUS_SUBSCRIBED );
		switch ( $segment ) {
			case 'clients':
				$where .= " AND source = 'client'";
				break;
			case 'newsletter':
				$where .= " AND source = 'newsletter'";
				break;
			case 'form_leads':
				$where .= " AND source IN ('quote','consultation','contact')";
				break;
			case 'health_check':
				$where .= " AND source = 'health_check'";
				break;
			case 'manual':
				$where .= " AND source = 'manual'";
				break;
		}
		return $where;
	}

	/**
	 * Count subscribed contacts in a segment.
	 *
	 * @param string $segment Segment.
	 * @return int
	 */
	public static function count_for_segment( $segment ) {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE " . self::segment_where( $segment ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Recipient rows (id, email, name, token) for a segment.
	 *
	 * @param string $segment Segment.
	 * @param int    $limit   Max rows (0 = all).
	 * @return array
	 */
	public static function recipients( $segment, $limit = 0 ) {
		global $wpdb;
		$table = self::table();
		$sql   = "SELECT id, email, name, token FROM {$table} WHERE " . self::segment_where( $segment ) . ' ORDER BY id ASC';
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}
		return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * One contact by id.
	 *
	 * @param int $id Id.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * One contact by email.
	 *
	 * @param string $email Email.
	 * @return array|null
	 */
	public static function get_by_email( $email ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", strtolower( $email ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * One contact by unsubscribe token.
	 *
	 * @param string $token Token.
	 * @return array|null
	 */
	public static function get_by_token( $token ) {
		global $wpdb;
		$table = self::table();
		if ( '' === $token ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token = %s", $token ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Insert or update a contact. Never resurrects an unsubscribed or bounced person.
	 *
	 * @param string $email   Email.
	 * @param array  $fields  name, source, consent_note, tags, wp_lead_id, whop_id.
	 * @return int|false Contact id, or false when the address is unusable.
	 */
	public static function upsert( $email, $fields = array() ) {
		global $wpdb;
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return false;
		}
		$email   = strtolower( $email );
		$table   = self::table();
		$now     = current_time( 'mysql', true );
		$existing = self::get_by_email( $email );

		$data = array();
		foreach ( array( 'name', 'source', 'consent_note', 'tags', 'whop_id' ) as $key ) {
			if ( isset( $fields[ $key ] ) && '' !== (string) $fields[ $key ] ) {
				$data[ $key ] = sanitize_text_field( $fields[ $key ] );
			}
		}
		if ( isset( $fields['wp_lead_id'] ) ) {
			$data['wp_lead_id'] = (int) $fields['wp_lead_id'];
		}

		if ( $existing ) {
			// Only fill blanks; do not overwrite a chosen name or flip status.
			$update = array( 'updated_at' => $now );
			foreach ( $data as $key => $value ) {
				if ( '' === (string) $existing[ $key ] ) {
					$update[ $key ] = $value;
				}
			}
			$wpdb->update( $table, $update, array( 'id' => $existing['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $existing['id'];
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array_merge(
				array(
					'email'      => $email,
					'status'     => self::STATUS_SUBSCRIBED,
					'token'      => token(),
					'created_at' => $now,
					'updated_at' => $now,
				),
				$data
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * A website lead just arrived: add it straight away.
	 *
	 * @param string $type    quote|consultation|contact|newsletter.
	 * @param string $email   Email.
	 * @param string $name    Name.
	 * @param int    $lead_id Lead post id.
	 */
	public static function on_lead( $type, $email, $name, $lead_id ) {
		$notes = array(
			'newsletter'   => __( 'Subscribed to your newsletter on your website', 'a2e-mail' ),
			'quote'        => __( 'Requested a free store audit on your website', 'a2e-mail' ),
			'consultation' => __( 'Requested a consultation on your website', 'a2e-mail' ),
			'contact'      => __( 'Sent you a message through your contact form', 'a2e-mail' ),
			'health-check' => __( 'Asked for their eBay account health report on your website', 'a2e-mail' ),
		);
		$source = 'contact';
		if ( 'health-check' === $type ) {
			$source = 'health_check';
		} elseif ( in_array( $type, array( 'newsletter', 'quote', 'consultation', 'contact' ), true ) ) {
			$source = $type;
		}
		return self::upsert(
			$email,
			array(
				'name'         => $name,
				'source'       => $source,
				'consent_note' => isset( $notes[ $type ] ) ? $notes[ $type ] : $notes['contact'],
				'wp_lead_id'   => $lead_id,
			)
		);
	}

	/**
	 * Pull in any contacts not yet captured: website leads and Whop clients.
	 * Safe to run often; it only adds people, never emails them.
	 *
	 * @return array added => int, sources => array
	 */
	public static function sync() {
		$added  = 0;
		$detail = array( 'leads' => 0, 'clients' => 0 );
		// On the very first sync we import the existing base without starting any automations,
		// so switching the plugin on never blasts people. New contacts after that can enroll.
		$first = ! get_option( 'a2email_last_sync' );

		// 1. Website leads (the theme's Leads post type).
		if ( post_type_exists( 'a2e_lead' ) ) {
			$leads = get_posts(
				array(
					'post_type'      => 'a2e_lead',
					'post_status'    => array( 'private', 'publish', 'draft' ),
					'posts_per_page' => 500,
					'fields'         => 'ids',
				)
			);
			foreach ( $leads as $lead_id ) {
				$email = get_post_meta( $lead_id, '_a2e_email', true );
				if ( ! is_email( $email ) ) {
					continue;
				}
				if ( self::get_by_email( $email ) ) {
					continue;
				}
				$type = get_post_meta( $lead_id, '_a2e_form', true );
				self::on_lead( $type, $email, self::lead_name( $lead_id ), $lead_id );
				$added++;
				$detail['leads']++;
			}
		}

		// 2. Whop clients (A2E Whop Sync subscribers table).
		global $wpdb;
		$members = $wpdb->prefix . 'a2ews_members';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $members ) ) === $members ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( "SELECT id, email, name FROM {$members} WHERE email <> ''", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			foreach ( (array) $rows as $row ) {
				if ( ! is_email( $row['email'] ) ) {
					continue;
				}
				$existing = self::get_by_email( $row['email'] );
				if ( $existing ) {
					// A lead who has now bought becomes a client. Note the conversion so lead
					// follow-ups can stop and a client welcome can start.
					if ( 'client' !== $existing['source'] && self::STATUS_SUBSCRIBED === $existing['status'] ) {
						$wpdb->update( self::table(), array( 'source' => 'client', 'whop_id' => $row['id'], 'consent_note' => __( 'Bought a plan from you through Whop', 'a2e-mail' ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $existing['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						if ( ! $first ) {
							do_action( 'a2email_client_added', (int) $existing['id'] );
						}
					}
					continue;
				}
				$cid = self::upsert(
					$row['email'],
					array(
						'name'         => $row['name'],
						'source'       => 'client',
						'consent_note' => __( 'Bought a plan from you through Whop', 'a2e-mail' ),
						'whop_id'      => $row['id'],
					)
				);
				if ( $cid && ! $first ) {
					do_action( 'a2email_client_added', (int) $cid );
				}
				$added++;
				$detail['clients']++;
			}
		}

		update_option( 'a2email_last_sync', time(), false );
		if ( $added > 0 ) {
			/* translators: 1: leads 2: clients */
			log( 'sync', sprintf( __( 'Added %1$d from website forms and %2$d clients.', 'a2e-mail' ), $detail['leads'], $detail['clients'] ) );
		}
		return array( 'added' => $added, 'detail' => $detail );
	}

	/**
	 * Best-effort name from a lead's title or body.
	 *
	 * @param int $lead_id Lead id.
	 * @return string
	 */
	protected static function lead_name( $lead_id ) {
		$title = get_post_field( 'post_title', $lead_id );
		if ( preg_match( '/:\s*(.+)$/', (string) $title, $m ) && ! is_email( trim( $m[1] ) ) ) {
			return trim( $m[1] );
		}
		return '';
	}

	/**
	 * Add a contact by hand (admin confirms the relationship).
	 *
	 * @param string $email Email.
	 * @param string $name  Name.
	 * @param string $note  How you know them / consent basis.
	 * @param string $tags  Comma tags.
	 * @return array result: ok (bool), message, id.
	 */
	public static function add_manual( $email, $name, $note, $tags = '' ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return array( 'ok' => false, 'message' => __( 'That email address does not look valid.', 'a2e-mail' ) );
		}
		$existing = self::get_by_email( $email );
		if ( $existing ) {
			if ( self::STATUS_SUBSCRIBED !== $existing['status'] ) {
				return array( 'ok' => false, 'message' => __( 'This person unsubscribed or bounced before, so they are kept on your suppression list and cannot be re-added here.', 'a2e-mail' ) );
			}
			return array( 'ok' => false, 'message' => __( 'You already have this contact.', 'a2e-mail' ), 'id' => (int) $existing['id'] );
		}
		$id = self::upsert(
			$email,
			array(
				'name'         => $name,
				'source'       => 'manual',
				'consent_note' => $note ? $note : __( 'Added by hand; you confirmed permission to email them', 'a2e-mail' ),
				'tags'         => $tags,
			)
		);
		log( 'contact', sprintf( 'Added %s by hand.', $email ) );
		return array( 'ok' => (bool) $id, 'message' => __( 'Contact added.', 'a2e-mail' ), 'id' => (int) $id );
	}

	/**
	 * Mark a contact unsubscribed (kept on the suppression list).
	 *
	 * @param int    $id     Id.
	 * @param string $reason Reason.
	 */
	public static function unsubscribe( $id, $reason = 'self' ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array( 'status' => self::STATUS_UNSUBSCRIBED, 'unsubscribed_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $id )
		);
	}

	/**
	 * Mark bounced (suppressed).
	 *
	 * @param int $id Id.
	 */
	public static function mark_bounced( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => self::STATUS_BOUNCED, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Note that a contact was just emailed.
	 *
	 * @param int $id Id.
	 */
	public static function mark_sent( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'last_sent' => current_time( 'mysql', true ) ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Counts for the dashboard.
	 *
	 * @return array
	 */
	public static function stats() {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( "SELECT status, COUNT(*) AS n FROM {$table} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$out   = array( 'subscribed' => 0, 'unsubscribed' => 0, 'bounced' => 0, 'total' => 0 );
		foreach ( (array) $rows as $row ) {
			$out[ $row['status'] ] = (int) $row['n'];
			$out['total']         += (int) $row['n'];
		}
		return $out;
	}

	/**
	 * A page of contacts for the list screen.
	 *
	 * @param array $args status, source, search, paged, per_page.
	 * @return array rows, total.
	 */
	public static function query( $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'status' => '', 'source' => '', 'search' => '', 'paged' => 1, 'per_page' => 50 ) );
		$table = self::table();
		$where = '1=1';
		$params = array();
		if ( $args['status'] ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}
		if ( $args['source'] ) {
			$where   .= ' AND source = %s';
			$params[] = $args['source'];
		}
		if ( $args['search'] ) {
			$where   .= ' AND (email LIKE %s OR name LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}
		$total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $total_sql, $params ) ) : $wpdb->get_var( $total_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$offset    = max( 0, ( (int) $args['paged'] - 1 ) * (int) $args['per_page'] );
		$list_sql  = "SELECT * FROM {$table} WHERE {$where} ORDER BY updated_at DESC LIMIT %d OFFSET %d";
		$rows      = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $params, array( (int) $args['per_page'], $offset ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		return array( 'rows' => $rows, 'total' => $total );
	}
}
