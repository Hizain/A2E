<?php
/**
 * Settings access, secret storage, logging and small helpers.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All settings with defaults.
 *
 * @return array
 */
function settings() {
	$defaults = array(
		'api_key'        => '',
		'account_id'     => '',
		'account_title'  => '',
		'sandbox'        => '0',
		'webhook_id'     => '',
		'webhook_secret' => '',
		'webhook_url'    => '',
		'redirect_url'   => '',
		'portal_url'     => 'https://whop.com/@me/settings/orders/',
		'notify_admin'   => '1',
		'create_users'   => '0',
		'connected_at'   => '',
	);
	$saved = get_option( OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * One setting.
 *
 * @param string $key Key.
 * @return string
 */
function setting( $key ) {
	$s = settings();
	return isset( $s[ $key ] ) ? (string) $s[ $key ] : '';
}

/**
 * Update settings (merged).
 *
 * @param array $values Values.
 */
function update_settings( $values ) {
	update_option( OPTION, array_merge( settings(), $values ), false );
}

/**
 * Encrypt a secret with a key derived from this site's salts (libsodium when available).
 *
 * @param string $plain Plain text.
 * @return string
 */
function encrypt( $plain ) {
	if ( '' === $plain ) {
		return '';
	}
	if ( function_exists( 'sodium_crypto_secretbox' ) ) {
		$key   = sodium_crypto_generichash( wp_salt( 'auth' ) . 'a2ews', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return 'sb1:' . base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, $key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
	return 'b64:' . base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Decrypt a stored secret.
 *
 * @param string $stored Stored value.
 * @return string
 */
function decrypt( $stored ) {
	$stored = (string) $stored;
	if ( 0 === strpos( $stored, 'sb1:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
		$raw   = base64_decode( substr( $stored, 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$key   = sodium_crypto_generichash( wp_salt( 'auth' ) . 'a2ews', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
		$nonce = substr( (string) $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$box   = substr( (string) $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain = $raw ? sodium_crypto_secretbox_open( $box, $nonce, $key ) : false;
		return false === $plain ? '' : $plain;
	}
	if ( 0 === strpos( $stored, 'b64:' ) ) {
		return (string) base64_decode( substr( $stored, 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}
	return $stored;
}

/**
 * The decrypted API key.
 *
 * @return string
 */
function api_key() {
	if ( defined( 'A2EWS_API_KEY' ) && A2EWS_API_KEY ) {
		return (string) A2EWS_API_KEY;
	}
	return decrypt( setting( 'api_key' ) );
}

/**
 * Whether a working connection exists.
 *
 * @return bool
 */
function is_connected() {
	return '' !== api_key() && '' !== setting( 'connected_at' );
}

/**
 * Append to the activity log (last 150 entries).
 *
 * @param string $type    Event / action type.
 * @param string $message Human-readable message.
 * @param bool   $ok      Success?
 */
function log( $type, $message, $ok = true ) {
	$entries = get_option( 'a2ews_log', array() );
	$entries = is_array( $entries ) ? $entries : array();
	array_unshift(
		$entries,
		array(
			't'  => time(),
			'ty' => sanitize_text_field( $type ),
			'm'  => sanitize_text_field( $message ),
			'ok' => (bool) $ok,
		)
	);
	update_option( 'a2ews_log', array_slice( $entries, 0, 150 ), false );
}

/**
 * Money value from either a number or a Money object ({ amount: "10.00", currency }).
 *
 * @param mixed $value Value.
 * @return float
 */
function money_value( $value ) {
	if ( is_array( $value ) ) {
		return isset( $value['amount'] ) ? (float) $value['amount'] : 0.0;
	}
	return is_numeric( $value ) ? (float) $value : 0.0;
}

/**
 * Currency symbol.
 *
 * @param string $code ISO code.
 * @return string
 */
function currency_symbol( $code ) {
	$map  = array( 'usd' => '$', 'eur' => '€', 'gbp' => '£', 'cad' => 'CA$', 'aud' => 'A$', 'pkr' => 'Rs ', 'inr' => '₹', 'aed' => 'AED ', 'sgd' => 'S$' );
	$code = strtolower( (string) $code );
	return isset( $map[ $code ] ) ? $map[ $code ] : strtoupper( $code ) . ' ';
}

/**
 * Format a price: "$200", "$49.99".
 *
 * @param float  $amount   Amount.
 * @param string $currency Currency.
 * @return string
 */
function format_price( $amount, $currency = 'usd' ) {
	$amount = (float) $amount;
	$num    = floor( $amount ) == $amount ? number_format( $amount ) : number_format( $amount, 2 ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
	return currency_symbol( $currency ) . $num;
}

/**
 * Admin URL of the plugin's settings screen (also used by the A2E theme's setup guide).
 *
 * @return string
 */
function settings_url() {
	return admin_url( 'admin.php?page=a2e-whop' );
}

/**
 * Read a nested array value with a list of fallback paths ("user.email", "customer_email").
 *
 * @param array $data  Data.
 * @param array $paths Dotted paths.
 * @return mixed|null
 */
function pick( $data, $paths ) {
	foreach ( $paths as $path ) {
		$cur = $data;
		foreach ( explode( '.', $path ) as $part ) {
			if ( is_array( $cur ) && array_key_exists( $part, $cur ) ) {
				$cur = $cur[ $part ];
			} else {
				$cur = null;
				break;
			}
		}
		if ( null !== $cur && '' !== $cur ) {
			return $cur;
		}
	}
	return null;
}
