<?php
/**
 * Minimal Whop REST client (https://api.whop.com/api/v1).
 *
 * Auth: "Authorization: Bearer <account API key>". Whop is migrating "plans" to "variants";
 * plan calls try /variants first and fall back to the /plans compatibility endpoints.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API client.
 */
class Api {

	/**
	 * Base URL.
	 *
	 * @return string
	 */
	public static function base() {
		if ( defined( 'A2EWS_API_BASE' ) && A2EWS_API_BASE ) {
			return untrailingslashit( A2EWS_API_BASE );
		}
		return '1' === setting( 'sandbox' ) ? 'https://sandbox-api.whop.com/api/v1' : 'https://api.whop.com/api/v1';
	}

	/**
	 * Perform a request.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path (no leading slash).
	 * @param array|null $body   JSON body or query args for GET.
	 * @param string     $key    API key override (used while connecting).
	 * @return array|\WP_Error Decoded JSON.
	 */
	public static function request( $method, $path, $body = null, $key = '' ) {
		$key = $key ? $key : api_key();
		if ( '' === $key ) {
			return new \WP_Error( 'a2ews_no_key', __( 'Whop is not connected yet. Add your API key under Whop → Connect.', 'a2e-whop' ) );
		}
		$url  = self::base() . '/' . ltrim( $path, '/' );
		$args = array(
			'method'  => $method,
			'timeout' => 25,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Accept'        => 'application/json',
				'User-Agent'    => 'A2E-Whop-Sync/' . VERSION . '; ' . home_url( '/' ),
			),
		);
		if ( 'GET' === $method && is_array( $body ) && $body ) {
			$url = add_query_arg( array_map( 'rawurlencode', array_filter( $body, 'is_scalar' ) ), $url );
		} elseif ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
			if ( 'POST' === $method ) {
				$args['headers']['Idempotency-Key'] = wp_generate_uuid4();
			}
		}
		$res = wp_remote_request( $url, $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$data = json_decode( $raw, true );
		if ( $code >= 200 && $code < 300 ) {
			return is_array( $data ) ? $data : array();
		}
		$message = '';
		if ( is_array( $data ) ) {
			$message = (string) pick( $data, array( 'error.message', 'message', 'error', 'errors.0.message', 'detail' ) );
		}
		if ( '' === $message ) {
			$message = $raw ? wp_html_excerpt( wp_strip_all_tags( $raw ), 200, '…' ) : wp_remote_retrieve_response_message( $res );
		}
		/* translators: 1: HTTP code 2: message */
		return new \WP_Error( 'a2ews_http_' . $code, sprintf( __( 'Whop API error %1$d: %2$s', 'a2e-whop' ), $code, $message ), array( 'status' => $code, 'body' => $data ) );
	}

	/**
	 * Whether an error means "this endpoint does not exist here" (so a fallback is worth trying).
	 *
	 * @param mixed $result Result.
	 * @return bool
	 */
	protected static function missing( $result ) {
		if ( ! is_wp_error( $result ) ) {
			return false;
		}
		$data = $result->get_error_data();
		return is_array( $data ) && isset( $data['status'] ) && in_array( (int) $data['status'], array( 404, 405 ), true );
	}

	/**
	 * The account behind the API key.
	 *
	 * @param string $key Key.
	 * @return array|\WP_Error
	 */
	public static function me( $key = '' ) {
		return self::request( 'GET', 'accounts/me', null, $key );
	}

	/**
	 * Create a product.
	 *
	 * @param array $body Body.
	 * @return array|\WP_Error
	 */
	public static function create_product( $body ) {
		return self::request( 'POST', 'products', self::with_account( $body ) );
	}

	/**
	 * Update a product.
	 *
	 * @param string $id   Product ID.
	 * @param array  $body Body.
	 * @return array|\WP_Error
	 */
	public static function update_product( $id, $body ) {
		return self::request( 'PATCH', 'products/' . rawurlencode( $id ), $body );
	}

	/**
	 * Create a plan (variant).
	 *
	 * @param array $body Body.
	 * @return array|\WP_Error
	 */
	public static function create_plan( $body ) {
		$body   = self::with_account( $body );
		$result = self::request( 'POST', 'variants', $body );
		if ( self::missing( $result ) ) {
			$result = self::request( 'POST', 'plans', $body );
		}
		return $result;
	}

	/**
	 * Update a plan (variant).
	 *
	 * @param string $id   Plan ID.
	 * @param array  $body Body.
	 * @return array|\WP_Error
	 */
	public static function update_plan( $id, $body ) {
		$result = self::request( 'PATCH', 'variants/' . rawurlencode( $id ), $body );
		if ( self::missing( $result ) ) {
			$result = self::request( 'PATCH', 'plans/' . rawurlencode( $id ), $body );
		}
		return $result;
	}

	/**
	 * One page of memberships.
	 *
	 * @param string $after Cursor.
	 * @return array|\WP_Error
	 */
	public static function list_memberships( $after = '' ) {
		$query = array( 'first' => 100 );
		if ( setting( 'account_id' ) ) {
			$query['account_id'] = setting( 'account_id' );
		}
		if ( $after ) {
			$query['after'] = $after;
		}
		return self::request( 'GET', 'memberships', $query );
	}

	/**
	 * One membership.
	 *
	 * @param string $id Membership ID.
	 * @return array|\WP_Error
	 */
	public static function get_membership( $id ) {
		return self::request( 'GET', 'memberships/' . rawurlencode( $id ) );
	}

	/**
	 * Cancel at the end of the current period (customer keeps access until then).
	 *
	 * @param string $id Membership ID.
	 * @return array|\WP_Error
	 */
	public static function cancel_membership( $id ) {
		$result = self::request( 'POST', 'memberships/' . rawurlencode( $id ) . '/cancel', array( 'cancel_at_period_end' => true, 'reason' => 'Cancelled from WordPress' ) );
		if ( is_wp_error( $result ) ) {
			$alt = self::request( 'PATCH', 'memberships/' . rawurlencode( $id ), array( 'cancel_at_period_end' => true ) );
			if ( ! is_wp_error( $alt ) ) {
				return $alt;
			}
		}
		return $result;
	}

	/**
	 * Create a webhook endpoint for this site.
	 *
	 * @param string $url    URL.
	 * @param array  $events Events.
	 * @return array|\WP_Error
	 */
	public static function create_webhook( $url, $events ) {
		return self::request(
			'POST',
			'webhooks',
			array(
				'url'     => $url,
				'events'  => $events,
				'enabled' => true,
			)
		);
	}

	/**
	 * Delete a webhook.
	 *
	 * @param string $id Webhook ID.
	 * @return array|\WP_Error
	 */
	public static function delete_webhook( $id ) {
		return self::request( 'DELETE', 'webhooks/' . rawurlencode( $id ) );
	}

	/**
	 * Add the connected account's ID when known.
	 *
	 * @param array $body Body.
	 * @return array
	 */
	protected static function with_account( $body ) {
		$account = setting( 'account_id' );
		if ( $account && empty( $body['account_id'] ) ) {
			$body['account_id'] = $account;
		}
		return $body;
	}
}
