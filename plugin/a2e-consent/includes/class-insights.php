<?php
/**
 * Reports built from the visitor data, the visitor journeys and the daily clean-up.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Insights.
 */
class Insights {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( CRON_PURGE, array( __CLASS__, 'purge' ) );
	}

	/**
	 * Delete visitor data and consent records older than the times you chose.
	 */
	public static function purge() {
		global $wpdb;
		$hits = max( 1, (int) opt( 'keep_hits' ) );
		$log  = max( 1, (int) opt( 'keep_log' ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . table( 'hits' ) . ' WHERE created < %s', gmdate( 'Y-m-d H:i:s', strtotime( '-' . $hits . ' months' ) ) ) ); // phpcs:ignore WordPress.DB
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . table( 'log' ) . ' WHERE created < %s', gmdate( 'Y-m-d H:i:s', strtotime( '-' . $log . ' months' ) ) ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Local date range for the last N days (today included).
	 *
	 * @param int $days    Days.
	 * @param int $shift   Move back this many whole ranges (1 = the period before).
	 * @return array{0:string,1:string}
	 */
	public static function range( $days, $shift = 0 ) {
		$days  = max( 1, (int) $days );
		$today = strtotime( wp_date( 'Y-m-d' ) . ' 00:00:00 UTC' );
		$end   = $today - $shift * $days * DAY_IN_SECONDS;
		$start = $end - ( $days - 1 ) * DAY_IN_SECONDS;
		return array( gmdate( 'Y-m-d', $start ), gmdate( 'Y-m-d', $end ) );
	}

	/**
	 * Headline numbers.
	 *
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @return array
	 */
	public static function summary( $from, $to ) {
		global $wpdb;
		$t   = table( 'hits' );
		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT CASE WHEN type = 'view' THEN visitor END) AS visitors,
					COUNT(DISTINCT CASE WHEN type = 'view' THEN session END) AS visits,
					SUM(CASE WHEN type = 'view' THEN 1 ELSE 0 END) AS views,
					SUM(CASE WHEN type = 'lead' THEN 1 ELSE 0 END) AS leads,
					SUM(CASE WHEN type NOT IN ('view','lead') THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN type = 'view' THEN seconds ELSE 0 END) AS seconds,
					SUM(CASE WHEN type = 'view' AND seconds > 0 THEN 1 ELSE 0 END) AS timed
				FROM {$t} WHERE day BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to
			),
			ARRAY_A
		);
		$row = array_map( 'intval', (array) $row );
		$new = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT COUNT(*) FROM (SELECT visitor, MIN(day) AS first_day FROM {$t} WHERE type = 'view' GROUP BY visitor) AS firsts WHERE first_day BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to
			)
		);
		$row['new']         = $new;
		$row['avg_time']    = $row['timed'] ? (int) round( $row['seconds'] / $row['timed'] ) : 0;
		$row['pages_visit'] = $row['visits'] ? round( $row['views'] / $row['visits'], 1 ) : 0;
		$row['lead_rate']   = $row['visitors'] ? round( $row['leads'] / $row['visitors'] * 100, 1 ) : 0;
		return $row;
	}

	/**
	 * Visitors, page views and leads per day.
	 *
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @return array day => [visitors, views, leads]
	 */
	public static function daily( $from, $to ) {
		global $wpdb;
		$t    = table( 'hits' );
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT day, COUNT(DISTINCT CASE WHEN type = 'view' THEN visitor END) AS visitors,
					SUM(CASE WHEN type = 'view' THEN 1 ELSE 0 END) AS views,
					SUM(CASE WHEN type = 'lead' THEN 1 ELSE 0 END) AS leads
				FROM {$t} WHERE day BETWEEN %s AND %s GROUP BY day", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to
			),
			ARRAY_A
		);
		$out = array();
		for ( $d = strtotime( $from . ' UTC' ); $d <= strtotime( $to . ' UTC' ); $d += DAY_IN_SECONDS ) {
			$out[ gmdate( 'Y-m-d', $d ) ] = array( 0, 0, 0 );
		}
		foreach ( (array) $rows as $r ) {
			$day = substr( (string) $r['day'], 0, 10 );
			if ( isset( $out[ $day ] ) ) {
				$out[ $day ] = array( (int) $r['visitors'], (int) $r['views'], (int) $r['leads'] );
			}
		}
		return $out;
	}

	/**
	 * Most viewed pages.
	 *
	 * @param string $from  Y-m-d.
	 * @param string $to    Y-m-d.
	 * @param int    $limit Rows.
	 * @return array
	 */
	public static function pages( $from, $to, $limit = 12 ) {
		global $wpdb;
		$t = table( 'hits' );
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT path, MAX(title) AS title, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors,
					SUM(seconds) AS seconds, SUM(CASE WHEN seconds > 0 THEN 1 ELSE 0 END) AS timed
				FROM {$t} WHERE type = 'view' AND day BETWEEN %s AND %s
				GROUP BY path ORDER BY views DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Where visitors came from, with the leads each source brought.
	 *
	 * @param string $from  Y-m-d.
	 * @param string $to    Y-m-d.
	 * @param string $group source|medium|campaign.
	 * @param int    $limit Rows.
	 * @return array
	 */
	public static function sources( $from, $to, $group = 'source', $limit = 12 ) {
		global $wpdb;
		$t      = table( 'hits' );
		$cols   = 'medium' === $group ? 'medium' : ( 'campaign' === $group ? 'campaign, source' : 'source, medium' );
		$where  = 'campaign' === $group ? "AND campaign <> ''" : '';
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT {$cols}, COUNT(DISTINCT CASE WHEN type = 'view' THEN visitor END) AS visitors,
					COUNT(DISTINCT CASE WHEN type = 'view' THEN session END) AS visits,
					SUM(CASE WHEN type = 'lead' THEN 1 ELSE 0 END) AS leads
				FROM {$t} WHERE day BETWEEN %s AND %s {$where}
				GROUP BY {$cols} ORDER BY visitors DESC, leads DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * The first page of each visit.
	 *
	 * @param string $from  Y-m-d.
	 * @param string $to    Y-m-d.
	 * @param int    $limit Rows.
	 * @return array
	 */
	public static function landings( $from, $to, $limit = 10 ) {
		global $wpdb;
		$t = table( 'hits' );
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT path, MAX(title) AS title, COUNT(*) AS visits, COUNT(DISTINCT visitor) AS visitors
				FROM {$t} WHERE type = 'view' AND entry = 1 AND day BETWEEN %s AND %s
				GROUP BY path ORDER BY visits DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Visitors by a column (device or country).
	 *
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @param string $col  device|country.
	 * @return array
	 */
	public static function split( $from, $to, $col = 'device' ) {
		global $wpdb;
		$t   = table( 'hits' );
		$col = 'country' === $col ? 'country' : 'device';
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT {$col} AS name, COUNT(DISTINCT visitor) AS visitors
				FROM {$t} WHERE type = 'view' AND {$col} <> '' AND day BETWEEN %s AND %s
				GROUP BY {$col} ORDER BY visitors DESC LIMIT 12", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to
			),
			ARRAY_A
		);
	}

	/**
	 * Clicks on WhatsApp, phone, email, Whop and other websites.
	 *
	 * @param string $from  Y-m-d.
	 * @param string $to    Y-m-d.
	 * @param int    $limit Rows.
	 * @return array
	 */
	public static function clicks( $from, $to, $limit = 12 ) {
		global $wpdb;
		$t = table( 'hits' );
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT type, target, MAX(title) AS label, COUNT(*) AS clicks, COUNT(DISTINCT visitor) AS visitors
				FROM {$t} WHERE type NOT IN ('view','lead') AND day BETWEEN %s AND %s
				GROUP BY type, target ORDER BY clicks DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Clicks per type.
	 *
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @return array type => count
	 */
	public static function click_totals( $from, $to ) {
		global $wpdb;
		$t    = table( 'hits' );
		$rows = (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "SELECT type, COUNT(*) AS n FROM {$t} WHERE type NOT IN ('view','lead') AND day BETWEEN %s AND %s GROUP BY type", $from, $to ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		$out = array();
		foreach ( $rows as $r ) {
			$out[ $r['type'] ] = (int) $r['n'];
		}
		return $out;
	}

	/**
	 * Recent leads with where they came from.
	 *
	 * @param string $from  Y-m-d.
	 * @param string $to    Y-m-d.
	 * @param int    $limit Rows.
	 * @return array
	 */
	public static function leads( $from, $to, $limit = 8 ) {
		global $wpdb;
		$t = table( 'hits' );
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT id, created, visitor, path, title, target, source, medium, campaign, lead_id
				FROM {$t} WHERE type = 'lead' AND day BETWEEN %s AND %s ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from,
				$to,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Visitors on the site in the last few minutes.
	 *
	 * @param int $minutes Minutes.
	 * @return int
	 */
	public static function live( $minutes = 5 ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT visitor) FROM ' . table( 'hits' ) . ' WHERE created >= %s', gmdate( 'Y-m-d H:i:s', time() - $minutes * MINUTE_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Consent choices in a date range.
	 *
	 * @param string $from Y-m-d (local).
	 * @param string $to   Y-m-d (local).
	 * @return array
	 */
	public static function consent_stats( $from, $to ) {
		global $wpdb;
		$start = get_gmt_from_date( $from . ' 00:00:00' );
		$end   = get_gmt_from_date( $to . ' 23:59:59' );
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT COUNT(*) AS total,
					SUM(CASE WHEN choice = 'accept_all' THEN 1 ELSE 0 END) AS accept_all,
					SUM(CASE WHEN choice = 'reject_all' THEN 1 ELSE 0 END) AS reject_all,
					SUM(CASE WHEN choice = 'custom' THEN 1 ELSE 0 END) AS custom,
					SUM(analytics) AS analytics, SUM(marketing) AS marketing
				FROM " . table( 'log' ) . ' WHERE created BETWEEN %s AND %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$start,
				$end
			),
			ARRAY_A
		);
		$row = array_map( 'intval', (array) $row );
		$pct = function ( $n ) use ( $row ) {
			return $row['total'] ? (int) round( $n / $row['total'] * 100 ) : 0;
		};
		$row['analytics_pct'] = $pct( $row['analytics'] );
		$row['marketing_pct'] = $pct( $row['marketing'] );
		$row['accept_pct']    = $pct( $row['accept_all'] );
		$row['reject_pct']    = $pct( $row['reject_all'] );
		$row['custom_pct']    = $pct( $row['custom'] );
		return $row;
	}

	/**
	 * Visitors, newest activity first.
	 *
	 * @param array $args page, per_page, leads_only, search.
	 * @return array{rows:array,total:int}
	 */
	public static function visitors( $args ) {
		global $wpdb;
		$t      = table( 'hits' );
		$per    = max( 1, (int) $args['per_page'] );
		$offset = max( 0, ( (int) $args['page'] - 1 ) * $per );
		$where  = '1=1';
		$params = array();
		if ( ! empty( $args['visitors'] ) ) {
			$ids    = array_values( array_filter( (array) $args['visitors'], function ( $v ) {
				return preg_match( '/^[a-f0-9]{16}$/', $v );
			} ) );
			if ( ! $ids ) {
				return array( 'rows' => array(), 'total' => 0 );
			}
			$where .= ' AND visitor IN (' . implode( ',', array_fill( 0, count( $ids ), '%s' ) ) . ')';
			$params = array_merge( $params, $ids );
		}
		$filter = ! empty( $args['leads_only'] ) ? 'WHERE leads > 0' : '';
		$inner  = "SELECT visitor, MIN(created) AS first_seen, MAX(created) AS last_seen,
				COUNT(DISTINCT session) AS visits,
				SUM(CASE WHEN type = 'view' THEN 1 ELSE 0 END) AS views,
				SUM(CASE WHEN type = 'view' THEN seconds ELSE 0 END) AS seconds,
				SUM(CASE WHEN type NOT IN ('view','lead') THEN 1 ELSE 0 END) AS clicks,
				SUM(CASE WHEN type = 'lead' THEN 1 ELSE 0 END) AS leads,
				MAX(lead_id) AS lead_id
			FROM {$t} WHERE {$where} GROUP BY visitor";

		$sql_total = "SELECT COUNT(*) FROM ({$inner}) AS v {$filter}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $sql_total, $params ) : $sql_total ); // phpcs:ignore WordPress.DB

		$sql  = "SELECT * FROM ({$inner}) AS v {$filter} ORDER BY last_seen DESC LIMIT %d OFFSET %d";
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, array( $per, $offset ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB

		foreach ( $rows as &$r ) {
			$first = $wpdb->get_row( $wpdb->prepare( "SELECT source, medium, campaign, path, device, country FROM {$t} WHERE visitor = %s AND entry = 1 ORDER BY id ASC LIMIT 1", $r['visitor'] ), ARRAY_A ); // phpcs:ignore WordPress.DB
			$r['first'] = $first ? $first : array( 'source' => '', 'medium' => '', 'campaign' => '', 'path' => '', 'device' => '', 'country' => '' );
		}
		unset( $r );
		return array( 'rows' => $rows, 'total' => $total );
	}

	/**
	 * Every recorded step of one visitor, grouped by visit.
	 *
	 * @param string $visitor Visitor ID.
	 * @return array visits, each with source and steps.
	 */
	public static function journey( $visitor ) {
		global $wpdb;
		$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . table( 'hits' ) . ' WHERE visitor = %s ORDER BY id ASC LIMIT 2000', $visitor ), ARRAY_A ); // phpcs:ignore WordPress.DB
		$visits = array();
		foreach ( $rows as $r ) {
			$key = $r['session'] ? $r['session'] : 'none-' . $r['id'];
			if ( ! isset( $visits[ $key ] ) ) {
				$visits[ $key ] = array(
					'start'    => $r['created'],
					'end'      => $r['created'],
					'source'   => $r['source'],
					'medium'   => $r['medium'],
					'campaign' => $r['campaign'],
					'ref_host' => $r['ref_host'],
					'device'   => $r['device'],
					'country'  => $r['country'],
					'steps'    => array(),
				);
			}
			$visits[ $key ]['end']     = $r['created'];
			$visits[ $key ]['steps'][] = $r;
		}
		return array_values( $visits );
	}

	/**
	 * A short summary of a visitor's history, saved on a lead when they send a form.
	 *
	 * @param string $visitor Visitor ID.
	 * @return array
	 */
	public static function journey_summary( $visitor ) {
		$visits = self::journey( $visitor );
		if ( ! $visits ) {
			return array();
		}
		$views   = 0;
		$seconds = 0;
		$clicks  = array();
		$pages   = array();
		foreach ( $visits as $v ) {
			foreach ( $v['steps'] as $s ) {
				if ( 'view' === $s['type'] ) {
					$views++;
					$seconds += (int) $s['seconds'];
					$pages[]  = array( $s['created'], $s['path'], $s['title'], (int) $s['seconds'] );
				} elseif ( 'lead' !== $s['type'] ) {
					$clicks[ $s['type'] ] = isset( $clicks[ $s['type'] ] ) ? $clicks[ $s['type'] ] + 1 : 1;
				}
			}
		}
		$first = $visits[0];
		$last  = end( $visits );
		$land  = '';
		foreach ( $first['steps'] as $s ) {
			if ( 'view' === $s['type'] ) {
				$land = $s['path'];
				break;
			}
		}
		return array(
			'visitor'      => $visitor,
			'first_seen'   => $first['start'],
			'first_source' => $first['source'],
			'first_medium' => $first['medium'],
			'first_camp'   => $first['campaign'],
			'landing'      => $land,
			'last_source'  => $last['source'],
			'last_medium'  => $last['medium'],
			'last_camp'    => $last['campaign'],
			'device'       => $last['device'] ? $last['device'] : $first['device'],
			'country'      => $last['country'] ? $last['country'] : $first['country'],
			'visits'       => count( $visits ),
			'views'        => $views,
			'seconds'      => $seconds,
			'clicks'       => $clicks,
			'pages'        => array_slice( $pages, -30 ),
			'saved'        => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Delete everything recorded about one visitor.
	 *
	 * @param string $visitor Visitor ID.
	 * @return int Rows deleted.
	 */
	public static function forget( $visitor ) {
		global $wpdb;
		if ( ! preg_match( '/^[a-f0-9]{16}$/', $visitor ) ) {
			return 0;
		}
		$n     = (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . table( 'hits' ) . ' WHERE visitor = %s', $visitor ) ); // phpcs:ignore WordPress.DB
		$leads = get_posts(
			array(
				'post_type'      => lead_types(),
				'post_status'    => array( 'private', 'publish', 'draft', 'pending', 'trash' ),
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_key'       => '_a2ec_vid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $visitor, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		foreach ( $leads as $id ) {
			delete_post_meta( $id, '_a2ec_journey' );
			delete_post_meta( $id, '_a2ec_vid' );
		}
		return $n;
	}

	/**
	 * Visitor IDs linked to leads with this email address.
	 *
	 * @param string $email Email.
	 * @return array{visitors:string[],leads:int[]}
	 */
	public static function by_email( $email ) {
		$leads = get_posts(
			array(
				'post_type'      => lead_types(),
				'post_status'    => array( 'private', 'publish', 'draft', 'pending', 'trash' ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_key'       => '_a2e_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$visitors = array();
		foreach ( $leads as $id ) {
			$v = (string) get_post_meta( $id, '_a2ec_vid', true );
			if ( $v ) {
				$visitors[ $v ] = $v;
			}
		}
		return array(
			'visitors' => array_values( $visitors ),
			'leads'    => array_map( 'intval', $leads ),
		);
	}
}
