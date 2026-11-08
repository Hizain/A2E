<?php
/**
 * Endpoints the banner and the visitor counter talk to. Visitor data is only accepted when the
 * visitor's consent cookie allows analytics; the IP address is never stored.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST.
 */
class Rest {

	const NS = 'a2e-consent/v1';

	/**
	 * Click types recorded.
	 *
	 * @var string[]
	 */
	public static $click_kinds = array( 'whatsapp', 'phone', 'email', 'whop', 'outbound', 'download', 'share' );

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Routes.
	 */
	public static function routes() {
		foreach ( array( 'consent', 'hit', 'ping', 'event' ) as $route ) {
			register_rest_route(
				self::NS,
				'/' . $route,
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, $route ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * A simple per-key rate limit.
	 *
	 * @param string $key   Key.
	 * @param int    $limit Max requests per 10 minutes.
	 * @return bool True when allowed.
	 */
	protected static function allow( $key, $limit ) {
		$key   = 'a2ec_rl_' . md5( $key . wp_salt( 'nonce' ) );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Client address, only used (hashed) for rate limits.
	 *
	 * @return string
	 */
	protected static function ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * A clean path from the site.
	 *
	 * @param string $raw Raw path or URL.
	 * @return string
	 */
	public static function clean_path( $raw ) {
		$path = (string) wp_parse_url( (string) $raw, PHP_URL_PATH );
		$path = '/' . ltrim( sanitize_text_field( rawurldecode( $path ) ), '/' );
		return substr( $path, 0, 190 );
	}

	/**
	 * A plain response that is never cached.
	 *
	 * @param array $data Data.
	 * @param int   $code Status.
	 * @return \WP_REST_Response
	 */
	protected static function reply( $data, $code = 200 ) {
		$res = new \WP_REST_Response( $data, $code );
		$res->header( 'Cache-Control', 'no-store, private' );
		return $res;
	}

	/**
	 * Record a consent choice (proof of consent).
	 *
	 * @param \WP_REST_Request $req Request.
	 * @return \WP_REST_Response
	 */
	public static function consent( $req ) {
		global $wpdb;
		$id     = (string) $req->get_param( 'id' );
		$choice = (string) $req->get_param( 'choice' );
		if ( '1' !== opt( 'banner_on' ) || ! preg_match( '/^[a-f0-9]{16}$/', $id ) || ! in_array( $choice, array( 'accept_all', 'reject_all', 'custom' ), true ) ) {
			return self::reply( array( 'ok' => false ), 400 );
		}
		if ( ! self::allow( 'c' . self::ip(), 30 ) ) {
			return self::reply( array( 'ok' => false ), 429 );
		}
		$ua = user_agent();
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			table( 'log' ),
			array(
				'created'    => gmdate( 'Y-m-d H:i:s' ),
				'consent_id' => $id,
				'choice'     => $choice,
				'analytics'  => '1' === (string) $req->get_param( 'a' ) ? 1 : 0,
				'marketing'  => '1' === (string) $req->get_param( 'm' ) ? 1 : 0,
				'version'    => max( 1, absint( $req->get_param( 'v' ) ) ),
				'gpc'        => '1' === (string) $req->get_param( 'gpc' ) ? 1 : 0,
				'path'       => self::clean_path( $req->get_param( 'path' ) ),
				'browser'    => substr( is_bot( $ua ) ? __( 'Automated browser', 'a2e-consent' ) : browser_summary( $ua ), 0, 80 ),
				'country'    => country(),
			),
			array( '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		return self::reply( array( 'ok' => true ) );
	}

	/**
	 * Can visitor data be recorded for this request?
	 *
	 * @return array{0:string,1:string}|false Visitor and session IDs.
	 */
	protected static function tracking_ok() {
		if ( '1' !== opt( 'banner_on' ) || '1' !== opt( 'insights' ) || ! analytics_allowed() || is_bot( user_agent() ) ) {
			return false;
		}
		$visitor = visitor_id();
		$session = isset( $_COOKIE[ COOKIE_SESSION ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ COOKIE_SESSION ] ) ) : '';
		if ( ! $visitor || ! preg_match( '/^[a-f0-9]{16}$/', $session ) ) {
			return false;
		}
		if ( ! self::allow( 'v' . $visitor, 300 ) || ! self::allow( 'i' . self::ip(), 900 ) ) {
			return false;
		}
		return array( $visitor, $session );
	}

	/**
	 * Where this visit (session) came from, from its first page.
	 *
	 * @param string $session Session ID.
	 * @return array|null
	 */
	public static function session_source( $session ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT source, medium, campaign, ref_host, device, country FROM ' . table( 'hits' ) . ' WHERE session = %s AND entry = 1 ORDER BY id ASC LIMIT 1', $session ), ARRAY_A ); // phpcs:ignore WordPress.DB
		return $row ? $row : null;
	}

	/**
	 * Record a page view.
	 *
	 * @param \WP_REST_Request $req Request.
	 * @return \WP_REST_Response
	 */
	public static function hit( $req ) {
		global $wpdb;
		$ids = self::tracking_ok();
		if ( ! $ids ) {
			return self::reply( array( 'ok' => false ), 403 );
		}
		list( $visitor, $session ) = $ids;

		$path = self::clean_path( $req->get_param( 'path' ) );
		if ( preg_match( '#^/(wp-admin|wp-login\.php|wp-json)#', $path ) ) {
			return self::reply( array( 'ok' => false ), 400 );
		}
		$title = html_entity_decode( sanitize_text_field( (string) $req->get_param( 'title' ) ), ENT_QUOTES, 'UTF-8' );
		$site  = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );
		if ( $site ) {
			$title = preg_replace( '/\s*[|\-–—·•]\s*' . preg_quote( $site, '/' ) . '\s*$/u', '', $title );
		}

		$width  = absint( $req->get_param( 'w' ) );
		$touch  = '1' === (string) $req->get_param( 't' );
		$device = $width && $width < 768 ? 'mobile' : ( $touch && $width && $width < 1200 ? 'tablet' : 'desktop' );

		$known = self::session_source( $session );
		if ( $known ) {
			$entry    = 0;
			$source   = $known['source'];
			$medium   = $known['medium'];
			$campaign = $known['campaign'];
			$ref_host = $known['ref_host'];
		} else {
			$entry    = 1;
			$ref      = esc_url_raw( (string) $req->get_param( 'ref' ) );
			$ref_host = $ref ? strtolower( (string) wp_parse_url( $ref, PHP_URL_HOST ) ) : '';
			$utm      = array();
			foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $k ) {
				$utm[ $k ] = substr( sanitize_text_field( (string) $req->get_param( $k ) ), 0, 100 );
			}
			$clicks = array(
				'gclid'   => (bool) $req->get_param( 'gclid' ),
				'fbclid'  => (bool) $req->get_param( 'fbclid' ),
				'msclkid' => (bool) $req->get_param( 'msclkid' ),
			);
			list( $source, $medium ) = classify( $ref_host, $utm, $clicks );
			$campaign                = $utm['utm_campaign'];
			$ref_host                = substr( preg_replace( '/^www\./', '', $ref_host ), 0, 100 );
			if ( own_host() === $ref_host ) {
				$ref_host = '';
			}
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			table( 'hits' ),
			array(
				'created'  => gmdate( 'Y-m-d H:i:s' ),
				'day'      => wp_date( 'Y-m-d' ),
				'visitor'  => $visitor,
				'session'  => $session,
				'type'     => 'view',
				'path'     => $path,
				'title'    => substr( $title, 0, 190 ),
				'source'   => substr( $source, 0, 60 ),
				'medium'   => substr( $medium, 0, 30 ),
				'campaign' => substr( $campaign, 0, 100 ),
				'ref_host' => $ref_host,
				'device'   => $device,
				'country'  => country(),
				'entry'    => $entry,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);
		return self::reply(
			array(
				'ok' => true,
				'id' => (int) $wpdb->insert_id,
			)
		);
	}

	/**
	 * Time spent on a page (only while the tab is visible).
	 *
	 * @param \WP_REST_Request $req Request.
	 * @return \WP_REST_Response
	 */
	public static function ping( $req ) {
		global $wpdb;
		$ids = self::tracking_ok();
		$id  = absint( $req->get_param( 'id' ) );
		$s   = min( 1800, absint( $req->get_param( 's' ) ) );
		if ( ! $ids || ! $id || ! $s ) {
			return self::reply( array( 'ok' => false ), 400 );
		}
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . table( 'hits' ) . ' SET seconds = %d WHERE id = %d AND visitor = %s AND seconds < %d', $s, $id, $ids[0], $s ) ); // phpcs:ignore WordPress.DB
		return self::reply( array( 'ok' => true ) );
	}

	/**
	 * A click on WhatsApp, phone, email, Whop, a download or another website.
	 *
	 * @param \WP_REST_Request $req Request.
	 * @return \WP_REST_Response
	 */
	public static function event( $req ) {
		$ids  = self::tracking_ok();
		$kind = (string) $req->get_param( 'kind' );
		if ( ! $ids || ! in_array( $kind, self::$click_kinds, true ) ) {
			return self::reply( array( 'ok' => false ), 400 );
		}
		$url = (string) $req->get_param( 'url' );
		$url = preg_match( '#^(https?:|mailto:|tel:|whatsapp:)#i', $url ) ? substr( esc_url_raw( $url, array( 'http', 'https', 'mailto', 'tel', 'whatsapp' ) ), 0, 190 ) : '';
		self::record(
			$ids[0],
			$ids[1],
			$kind,
			self::clean_path( $req->get_param( 'path' ) ),
			substr( sanitize_text_field( (string) $req->get_param( 'text' ) ), 0, 80 ),
			$url
		);
		return self::reply( array( 'ok' => true ) );
	}

	/**
	 * Store a click or lead row, carrying the visit's source.
	 *
	 * @param string $visitor Visitor.
	 * @param string $session Session.
	 * @param string $type    Row type.
	 * @param string $path    Page.
	 * @param string $title   Label.
	 * @param string $target  Target.
	 * @param int    $lead_id Lead post ID.
	 * @return int Row ID.
	 */
	public static function record( $visitor, $session, $type, $path, $title, $target, $lead_id = 0 ) {
		global $wpdb;
		$src = self::session_source( $session );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			table( 'hits' ),
			array(
				'created'  => gmdate( 'Y-m-d H:i:s' ),
				'day'      => wp_date( 'Y-m-d' ),
				'visitor'  => $visitor,
				'session'  => $session,
				'type'     => $type,
				'path'     => $path,
				'title'    => $title,
				'target'   => $target,
				'source'   => $src ? $src['source'] : __( 'Direct', 'a2e-consent' ),
				'medium'   => $src ? $src['medium'] : 'direct',
				'campaign' => $src ? $src['campaign'] : '',
				'ref_host' => $src ? $src['ref_host'] : '',
				'device'   => $src ? $src['device'] : '',
				'country'  => $src && $src['country'] ? $src['country'] : country(),
				'entry'    => 0,
				'lead_id'  => (int) $lead_id,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
		);
		return (int) $wpdb->insert_id;
	}
}
