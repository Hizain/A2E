<?php
/**
 * Webhook receiver: POST /wp-json/a2e-whop/v1/webhook
 *
 * Whop signs deliveries with the Standard Webhooks scheme: headers webhook-id,
 * webhook-timestamp and webhook-signature ("v1,<base64 HMAC-SHA256>"), where the signed
 * content is "{id}.{timestamp}.{raw body}" and the HMAC key is the literal bytes of the
 * endpoint's webhook secret.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhooks.
 */
class Webhooks {

	const EVENTS = array(
		'membership.activated',
		'membership.deactivated',
		'membership.cancel_at_period_end_changed',
		'membership.updated',
		'payment.succeeded',
		'payment.failed',
		'refund.created',
	);

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Endpoint URL.
	 *
	 * @return string
	 */
	public static function url() {
		return rest_url( 'a2e-whop/v1/webhook' );
	}

	/**
	 * Register the route.
	 */
	public static function routes() {
		register_rest_route(
			'a2e-whop/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'receive' ),
				'permission_callback' => '__return_true', // Authenticated by signature below.
			)
		);
	}

	/**
	 * Verify a Standard Webhooks signature.
	 *
	 * @param string $body      Raw body.
	 * @param string $msg_id    webhook-id.
	 * @param string $timestamp webhook-timestamp.
	 * @param string $header    webhook-signature.
	 * @param string $secret    Endpoint secret.
	 * @return bool
	 */
	public static function verify( $body, $msg_id, $timestamp, $header, $secret ) {
		if ( '' === $secret || '' === $msg_id || '' === $timestamp || '' === $header ) {
			return false;
		}
		if ( ! ctype_digit( (string) $timestamp ) || abs( time() - (int) $timestamp ) > 5 * MINUTE_IN_SECONDS ) {
			return false;
		}
		$signed = $msg_id . '.' . $timestamp . '.' . $body;
		$keys   = array( $secret ); // Whop: HMAC with the literal secret bytes.
		if ( 0 === strpos( $secret, 'whsec_' ) ) {
			$decoded = base64_decode( substr( $secret, 6 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false !== $decoded ) {
				$keys[] = $decoded; // Generic Standard Webhooks secrets.
			}
		}
		$given = array();
		foreach ( preg_split( '/\s+/', trim( $header ) ) as $part ) {
			$bits = explode( ',', $part, 2 );
			if ( 2 === count( $bits ) && 'v1' === $bits[0] ) {
				$given[] = $bits[1];
			}
		}
		foreach ( $keys as $key ) {
			$expected = base64_encode( hash_hmac( 'sha256', $signed, $key, true ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			foreach ( $given as $sig ) {
				if ( hash_equals( $expected, $sig ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Receive a delivery.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function receive( $request ) {
		$body   = $request->get_body();
		$id     = (string) ( $request->get_header( 'webhook-id' ) ? $request->get_header( 'webhook-id' ) : $request->get_header( 'svix-id' ) );
		$ts     = (string) ( $request->get_header( 'webhook-timestamp' ) ? $request->get_header( 'webhook-timestamp' ) : $request->get_header( 'svix-timestamp' ) );
		$sig    = (string) ( $request->get_header( 'webhook-signature' ) ? $request->get_header( 'webhook-signature' ) : $request->get_header( 'svix-signature' ) );
		$secret = decrypt( setting( 'webhook_secret' ) );

		if ( ! self::verify( $body, $id, $ts, $sig, $secret ) ) {
			log( 'webhook', 'Rejected a webhook with a missing or invalid signature.', false );
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_signature' ), 401 );
		}

		// Ignore duplicates (Whop retries until it gets a 2xx).
		$seen = get_option( 'a2ews_seen', array() );
		$seen = is_array( $seen ) ? $seen : array();
		if ( in_array( $id, $seen, true ) ) {
			return new \WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
		}
		array_unshift( $seen, $id );
		update_option( 'a2ews_seen', array_slice( $seen, 0, 300 ), false );

		$event = json_decode( $body, true );
		if ( ! is_array( $event ) ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_json' ), 400 );
		}
		self::handle( (string) pick( $event, array( 'type', 'action', 'event' ) ), isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : array() );
		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Apply an event.
	 *
	 * @param string $type Event type.
	 * @param array  $data Event data.
	 */
	public static function handle( $type, $data ) {
		// Older Whop event names map onto the current ones.
		$aliases = array(
			'membership.went_valid'    => 'membership.activated',
			'membership_went_valid'    => 'membership.activated',
			'membership.went_invalid'  => 'membership.deactivated',
			'membership_went_invalid'  => 'membership.deactivated',
			'payment_succeeded'        => 'payment.succeeded',
			'payment_failed'           => 'payment.failed',
		);
		$type = isset( $aliases[ $type ] ) ? $aliases[ $type ] : $type;

		switch ( $type ) {
			case 'membership.activated':
			case 'membership.updated':
			case 'membership.cancel_at_period_end_changed':
			case 'membership.deactivated':
				$before = Members::get( (string) pick( $data, array( 'id' ) ) );
				$row    = Members::upsert( $data );
				if ( ! $row ) {
					return;
				}
				if ( '' === $row['email'] ) {
					$full = Api::get_membership( $row['id'] );
					if ( ! is_wp_error( $full ) ) {
						$row = Members::upsert( $full );
					}
				}
				$plan = $row['wp_plan_id'] ? get_the_title( (int) $row['wp_plan_id'] ) : $row['plan_id'];
				$who  = $row['email'] ? $row['email'] : ( $row['username'] ? $row['username'] : $row['id'] );
				if ( 'membership.activated' === $type ) {
					log( $type, sprintf( 'New subscriber %s on %s.', $who, $plan ) );
					if ( ! $before || ! in_array( $before['status'], array( 'active', 'trialing' ), true ) ) {
						self::notify( __( 'New subscription', 'a2e-whop' ), sprintf( "%s just subscribed to %s.\n\nView subscribers: %s", $who, $plan, admin_url( 'admin.php?page=a2e-whop-subscribers' ) ) );
						self::maybe_create_user( $row );
					}
				} elseif ( 'membership.deactivated' === $type ) {
					log( $type, sprintf( '%s’s %s subscription ended (%s).', $who, $plan, $row['status'] ) );
					self::notify( __( 'Subscription ended', 'a2e-whop' ), sprintf( "%s's subscription to %s has ended (status: %s).", $who, $plan, $row['status'] ) );
				} elseif ( 'membership.cancel_at_period_end_changed' === $type ) {
					log( $type, $row['cancel_at_period_end'] ? sprintf( '%s cancelled %s — access ends %s.', $who, $plan, $row['period_end'] ) : sprintf( '%s resumed %s.', $who, $plan ) );
					if ( $row['cancel_at_period_end'] ) {
						self::notify( __( 'Subscription cancelled', 'a2e-whop' ), sprintf( "%s cancelled %s. Access continues until %s UTC.\n\nA quick personal message now can save the client.", $who, $plan, $row['period_end'] ) );
					}
				} else {
					log( $type, sprintf( 'Subscription %s updated.', $row['id'] ) );
				}
				do_action( 'a2ews_membership_event', $type, $row, $data );
				break;

			case 'payment.succeeded':
				$mid    = (string) pick( $data, array( 'membership_id', 'membership.id', 'membership' ) );
				$amount = money_value( pick( $data, array( 'total', 'final_amount', 'usd_total', 'subtotal', 'amount' ) ) );
				$cur    = (string) pick( $data, array( 'currency', 'total.currency' ) );
				$email  = (string) pick( $data, array( 'customer_email', 'user.email', 'email' ) );
				if ( $mid ) {
					Members::add_payment( $mid, $amount, $cur, $email );
				}
				log( $type, sprintf( 'Payment received: %s from %s.', format_price( $amount, $cur ? $cur : 'usd' ), $email ? $email : $mid ) );
				do_action( 'a2ews_payment_succeeded', $data );
				break;

			case 'payment.failed':
				$email = (string) pick( $data, array( 'customer_email', 'user.email', 'email' ) );
				log( $type, sprintf( 'Payment failed for %s: %s', $email ? $email : (string) pick( $data, array( 'membership_id', 'id' ) ), (string) pick( $data, array( 'failure_message' ) ) ), false );
				self::notify( __( 'Payment failed', 'a2e-whop' ), sprintf( "A renewal payment failed for %s. Whop will retry automatically; you may want to send a friendly reminder.\n\nReason: %s", $email, (string) pick( $data, array( 'failure_message' ) ) ) );
				break;

			case 'refund.created':
				$amount = money_value( pick( $data, array( 'amount', 'total' ) ) );
				$mid    = (string) pick( $data, array( 'payment.membership_id', 'membership_id' ) );
				if ( $mid && Members::get( $mid ) ) {
					$row = Members::get( $mid );
					Members::patch( $mid, array( 'total_paid' => max( 0, (float) $row['total_paid'] - $amount ) ) );
				}
				log( $type, sprintf( 'Refund issued: %s.', format_price( $amount, (string) pick( $data, array( 'currency' ) ) ? (string) pick( $data, array( 'currency' ) ) : 'usd' ) ) );
				break;

			default:
				log( 'webhook', sprintf( 'Received %s (no action needed).', $type ? $type : 'unknown event' ) );
		}
	}

	/**
	 * Email the site admin (when enabled).
	 *
	 * @param string $subject Subject.
	 * @param string $message Message.
	 */
	protected static function notify( $subject, $message ) {
		if ( '1' !== setting( 'notify_admin' ) ) {
			return;
		}
		wp_mail( get_option( 'admin_email' ), '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] ' . $subject, $message );
	}

	/**
	 * Optionally create a WordPress account for a new subscriber.
	 *
	 * @param array $row Member row.
	 */
	protected static function maybe_create_user( $row ) {
		if ( '1' !== setting( 'create_users' ) || ! is_email( $row['email'] ) ) {
			return;
		}
		$user = get_user_by( 'email', $row['email'] );
		if ( ! $user ) {
			$login = sanitize_user( $row['username'] ? $row['username'] : current( explode( '@', $row['email'] ) ), true );
			if ( username_exists( $login ) ) {
				$login .= '_' . wp_rand( 100, 999 );
			}
			$uid = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_email'   => $row['email'],
					'display_name' => $row['name'] ? $row['name'] : $login,
					'user_pass'    => wp_generate_password( 24 ),
					'role'         => 'subscriber',
				)
			);
			if ( is_wp_error( $uid ) ) {
				return;
			}
			wp_new_user_notification( $uid, null, 'user' );
			$user = get_user_by( 'id', $uid );
		}
		Members::patch( $row['id'], array( 'wp_user_id' => (int) $user->ID ) );
	}
}
