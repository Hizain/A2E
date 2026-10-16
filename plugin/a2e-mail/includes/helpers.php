<?php
/**
 * Settings, business details and small helpers.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

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
		'from_name'       => get_bloginfo( 'name' ),
		'from_email'      => 'support@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'reply_to'        => '',
		'business_name'   => get_bloginfo( 'name' ),
		'postal_address'  => '',
		'whatsapp'        => function_exists( 'a2e_opt' ) ? a2e_opt( 'whatsapp' ) : '',
		'phone'           => '',
		'contact_email'   => ( function_exists( 'a2e_opt' ) && a2e_opt( 'email' ) ) ? a2e_opt( 'email' ) : get_option( 'admin_email' ),
		'per_minute'      => '20',
		'per_hour'        => '200',
		'per_day'         => '1000',
		'throttle_seconds' => '2',
		'auto_sync'       => '1',
		'smtp_enabled'    => '0',
		'smtp_host'       => '',
		'smtp_port'       => '587',
		'smtp_secure'     => 'tls',
		'smtp_user'       => '',
		'smtp_pass'       => '',
		'smtp_from_forced' => '1',
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
		$key   = sodium_crypto_generichash( wp_salt( 'auth' ) . 'a2email', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
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
		$key   = sodium_crypto_generichash( wp_salt( 'auth' ) . 'a2email', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
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
 * The SMTP password (decrypted).
 *
 * @return string
 */
function smtp_pass() {
	if ( defined( 'A2EMAIL_SMTP_PASS' ) && A2EMAIL_SMTP_PASS ) {
		return (string) A2EMAIL_SMTP_PASS;
	}
	return decrypt( setting( 'smtp_pass' ) );
}

/**
 * Replace long dashes with plain punctuation so no message ever contains an em or en dash.
 * Mirrors the theme's "plain punctuation" rule.
 *
 * @param string $text Text.
 * @return string
 */
function no_dashes( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return (string) $text;
	}
	$en = '(?:\x{2013}|&#8211;|&#x2013;|&ndash;)';
	$em = '(?:\x{2014}|&#8212;|&#x2014;|&mdash;)';
	$rules = array(
		'/(\d)\s*(?:' . $en . '|' . $em . ')\s*(?=\d)/iu' => '$1-',
		'/\s+' . $en . '\s+/iu'                           => ' - ',
		'/(?<=\S)' . $en . '(?=\S)/iu'                    => '-',
		'/\s*' . $em . '\s*/iu'                           => ', ',
	);
	return preg_replace( array_keys( $rules ), array_values( $rules ), $text );
}

/**
 * WhatsApp wa.me link.
 *
 * @param string $message Optional prefilled message.
 * @return string
 */
function wa_link( $message = '' ) {
	$number = preg_replace( '/\D+/', '', setting( 'whatsapp' ) );
	if ( '' === $number ) {
		return '';
	}
	$url = 'https://wa.me/' . $number;
	if ( $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/**
 * Tel link from a phone number.
 *
 * @return string
 */
function tel_link() {
	$phone = setting( 'phone' ) ? setting( 'phone' ) : setting( 'whatsapp' );
	$clean = preg_replace( '/[^\d+]/', '', $phone );
	return $clean ? 'tel:' . $clean : '';
}

/**
 * A random, URL-safe token.
 *
 * @param int $bytes Bytes.
 * @return string
 */
function token( $bytes = 20 ) {
	return rtrim( strtr( base64_encode( random_bytes( $bytes ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Admin URL of a plugin page.
 *
 * @param string $page Page slug suffix (empty for the dashboard).
 * @param array  $args Extra query args.
 * @return string
 */
function admin_page_url( $page = '', $args = array() ) {
	$slug = 'a2e-mail' . ( $page ? '-' . $page : '' );
	return add_query_arg( array_merge( array( 'page' => $slug ), $args ), admin_url( 'admin.php' ) );
}

/**
 * Format a datetime for display in the site timezone.
 *
 * @param string $mysql MySQL datetime (UTC).
 * @return string
 */
function local_time( $mysql ) {
	if ( ! $mysql ) {
		return '';
	}
	$ts = strtotime( $mysql . ' UTC' );
	return $ts ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) : '';
}

/**
 * Append to the activity log (last 200 entries).
 *
 * @param string $type    Type.
 * @param string $message Message.
 */
function log( $type, $message ) {
	$entries = get_option( 'a2email_log', array() );
	$entries = is_array( $entries ) ? $entries : array();
	array_unshift( $entries, array( 't' => time(), 'ty' => sanitize_text_field( $type ), 'm' => sanitize_text_field( $message ) ) );
	update_option( 'a2email_log', array_slice( $entries, 0, 200 ), false );
}
