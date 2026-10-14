<?php
/**
 * Subscribers: a local copy of Whop memberships, kept current by webhooks and on-demand import.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Members store.
 */
class Members {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'a2ews_members';
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
				id varchar(64) NOT NULL,
				whop_user_id varchar(64) NOT NULL DEFAULT '',
				email varchar(190) NOT NULL DEFAULT '',
				name varchar(190) NOT NULL DEFAULT '',
				username varchar(190) NOT NULL DEFAULT '',
				plan_id varchar(64) NOT NULL DEFAULT '',
				product_id varchar(64) NOT NULL DEFAULT '',
				wp_plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(32) NOT NULL DEFAULT '',
				cancel_at_period_end tinyint(1) NOT NULL DEFAULT 0,
				period_end datetime NULL DEFAULT NULL,
				manage_url text NULL,
				total_paid decimal(12,2) NOT NULL DEFAULT 0,
				currency varchar(8) NOT NULL DEFAULT 'usd',
				wp_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NULL DEFAULT NULL,
				updated_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY status (status),
				KEY email (email)
			) {$charset};"
		);
		update_option( 'a2ews_db_version', VERSION, false );
	}

	/**
	 * Get one row.
	 *
	 * @param string $id Membership ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %s', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		return $row ? $row : null;
	}

	/**
	 * Insert or update a membership from any Whop membership shape (old or new API).
	 *
	 * @param array $m Membership data.
	 * @return array|null Saved row.
	 */
	public static function upsert( $m ) {
		global $wpdb;
		$id = (string) pick( $m, array( 'id' ) );
		if ( '' === $id ) {
			return null;
		}
		$existing = self::get( $id );
		$plan_id  = (string) pick( $m, array( 'plan_id', 'plan.id' ) );
		$end      = pick( $m, array( 'current_period_end', 'renewal_period_end', 'expires_at' ) );
		$row      = array(
			'id'                   => $id,
			'whop_user_id'         => (string) pick( $m, array( 'user_id', 'user.id', 'member.user.id' ) ),
			'email'                => sanitize_email( (string) pick( $m, array( 'user.email', 'email', 'member.email', 'customer_email' ) ) ),
			'name'                 => sanitize_text_field( (string) pick( $m, array( 'user.name', 'member.name', 'name' ) ) ),
			'username'             => sanitize_text_field( (string) pick( $m, array( 'user.username', 'member.username', 'username' ) ) ),
			'plan_id'              => $plan_id,
			'product_id'           => (string) pick( $m, array( 'product_id', 'product.id' ) ),
			'wp_plan_id'           => $plan_id ? Plans::find_by_whop_plan( $plan_id ) : 0,
			'status'               => sanitize_key( (string) pick( $m, array( 'status' ) ) ),
			'cancel_at_period_end' => pick( $m, array( 'cancel_at_period_end' ) ) ? 1 : 0,
			'period_end'           => $end ? gmdate( 'Y-m-d H:i:s', is_numeric( $end ) ? (int) $end : strtotime( (string) $end ) ) : null,
			'manage_url'           => esc_url_raw( (string) pick( $m, array( 'manage_url' ) ) ),
			'currency'             => strtolower( (string) ( pick( $m, array( 'currency' ) ) ? pick( $m, array( 'currency' ) ) : 'usd' ) ),
			'updated_at'           => current_time( 'mysql', true ),
		);
		// Never blank out details we already know with an empty value from a slimmer payload.
		if ( $existing ) {
			foreach ( array( 'whop_user_id', 'email', 'name', 'username', 'plan_id', 'product_id', 'manage_url', 'period_end', 'status' ) as $key ) {
				if ( empty( $row[ $key ] ) && ! empty( $existing[ $key ] ) ) {
					$row[ $key ] = $existing[ $key ];
				}
			}
			if ( ! $row['wp_plan_id'] ) {
				$row['wp_plan_id'] = (int) $existing['wp_plan_id'];
			}
			$wpdb->update( self::table(), $row, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$created           = pick( $m, array( 'created_at' ) );
			$row['created_at'] = $created ? gmdate( 'Y-m-d H:i:s', is_numeric( $created ) ? (int) $created : strtotime( (string) $created ) ) : current_time( 'mysql', true );
			$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		return self::get( $id );
	}

	/**
	 * Patch specific columns.
	 *
	 * @param string $id   Membership ID.
	 * @param array  $data Columns.
	 */
	public static function patch( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( self::table(), $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Record a payment against a membership.
	 *
	 * @param string $membership_id Membership.
	 * @param float  $amount        Amount.
	 * @param string $currency      Currency.
	 * @param string $email         Customer email.
	 */
	public static function add_payment( $membership_id, $amount, $currency, $email ) {
		global $wpdb;
		$row = self::get( $membership_id );
		if ( ! $row ) {
			self::upsert( array( 'id' => $membership_id, 'email' => $email, 'currency' => $currency, 'status' => 'active' ) );
			$row = self::get( $membership_id );
		}
		$data = array( 'total_paid' => (float) $row['total_paid'] + (float) $amount );
		if ( $email && empty( $row['email'] ) ) {
			$data['email'] = sanitize_email( $email );
		}
		if ( $currency ) {
			$data['currency'] = strtolower( $currency );
		}
		self::patch( $membership_id, $data );
	}

	/**
	 * Query rows.
	 *
	 * @param array $args status, search, paged, per_page.
	 * @return array [ rows, total ]
	 */
	public static function query( $args ) {
		global $wpdb;
		$where  = '1=1';
		$params = array();
		if ( ! empty( $args['status'] ) ) {
			if ( 'active' === $args['status'] ) {
				$where .= " AND status IN ('active','trialing','completed','past_due','canceling')";
			} else {
				$where   .= ' AND status = %s';
				$params[] = $args['status'];
			}
		}
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where   .= ' AND (email LIKE %s OR name LIKE %s OR username LIKE %s OR id LIKE %s)';
			$params   = array_merge( $params, array( $like, $like, $like, $like ) );
		}
		$per    = max( 1, (int) $args['per_page'] );
		$offset = ( max( 1, (int) $args['paged'] ) - 1 ) * $per;
		$table  = self::table();
		$sql    = "SELECT * FROM {$table} WHERE {$where} ORDER BY updated_at DESC LIMIT {$per} OFFSET {$offset}";
		$count  = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		if ( $params ) {
			$sql   = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$count = $wpdb->prepare( $count, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return array( $wpdb->get_results( $sql, ARRAY_A ), (int) $wpdb->get_var( $count ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Headline numbers: active subscribers and monthly recurring revenue estimate.
	 *
	 * @return array active, mrr, canceling, total_paid
	 */
	public static function stats() {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( "SELECT wp_plan_id, status, cancel_at_period_end, total_paid FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$out   = array( 'active' => 0, 'mrr' => 0.0, 'canceling' => 0, 'total_paid' => 0.0 );
		foreach ( (array) $rows as $r ) {
			$out['total_paid'] += (float) $r['total_paid'];
			if ( ! in_array( $r['status'], array( 'active', 'trialing', 'past_due', 'canceling' ), true ) ) {
				continue;
			}
			++$out['active'];
			if ( $r['cancel_at_period_end'] || 'canceling' === $r['status'] ) {
				++$out['canceling'];
			}
			$out['mrr'] += Plans::monthly_value( (int) $r['wp_plan_id'] );
		}
		return $out;
	}

	/**
	 * Import every membership from Whop (paginated).
	 *
	 * @return int|\WP_Error Number imported.
	 */
	public static function import_all() {
		$after = '';
		$count = 0;
		for ( $page = 0; $page < 50; $page++ ) {
			$res = Api::list_memberships( $after );
			if ( is_wp_error( $res ) ) {
				return $count ? $count : $res;
			}
			$items = isset( $res['data'] ) && is_array( $res['data'] ) ? $res['data'] : array();
			foreach ( $items as $m ) {
				if ( self::upsert( $m ) ) {
					++$count;
				}
			}
			$has_next = (bool) pick( $res, array( 'page_info.has_next_page', 'pagination.has_next_page' ) );
			$after    = (string) pick( $res, array( 'page_info.end_cursor', 'pagination.end_cursor', 'pagination.next_cursor' ) );
			if ( ! $has_next || '' === $after ) {
				break;
			}
		}
		log( 'import', sprintf( 'Imported %d memberships from Whop.', $count ) );
		return $count;
	}
}
