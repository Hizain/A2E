<?php
/**
 * Settings and small helpers. Where the A2E Prime theme is active, business details default to
 * its settings so the two stay in sync; otherwise sensible fallbacks are used.
 *
 * @package A2E_Health
 */

namespace A2E\Health;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All settings with defaults.
 *
 * @return array
 */
function settings() {
	$theme = function ( $key, $fallback = '' ) {
		return ( function_exists( 'a2e_opt' ) && a2e_opt( $key ) ) ? a2e_opt( $key ) : $fallback;
	};
	$defaults = array(
		'whatsapp'      => $theme( 'whatsapp', '' ),
		'phone'         => '',
		'business_name' => $theme( 'business_name', get_bloginfo( 'name' ) ),
		'lead_email'    => $theme( 'lead_email', get_option( 'admin_email' ) ),
		'capture'       => '1',
		'cta_title'     => __( 'Want us to protect and grow your account health?', 'a2e-health' ),
		'cta_text'      => __( 'Our team manages eBay account health every day: daily stock and supplier checks so you never cancel, fast valid tracking, quick case handling, and steady researched listings so revenue grows while your account stays clean.', 'a2e-health' ),
		'cta_url'       => '',
		'highlight'     => '1',
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
 * WhatsApp wa.me link with an optional prefilled message.
 *
 * @param string $message Message.
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
 * A random, URL-safe token.
 *
 * @param int $bytes Bytes.
 * @return string
 */
function token( $bytes = 16 ) {
	return rtrim( strtr( base64_encode( random_bytes( $bytes ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Echo a small inline SVG icon (self-contained, no icon font needed).
 *
 * @param string $name trend|wand|whatsapp|check|clock|target.
 */
function a2ehc_icon( $name ) {
	$icons = array(
		'trend'    => '<path d="M3 17l6-6 4 4 7-7"/><path d="M17 7h4v4"/>',
		'wand'     => '<path d="M15 4V2M15 16v-2M8 9h2M20 9h2M17.8 11.8L19 13M15 9h0M17.8 6.2L19 5M3 21l9-9M12.2 6.2L11 5"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'target'   => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
		'shield'   => '<path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/>',
		'award'    => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 21l5-2 5 2-1.5-7.5"/>',
		'alert'    => '<path d="M12 9v4M12 17h0M10.3 4.3L2.8 18a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/>',
		'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h0"/>',
		'truck'    => '<path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
		'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/>',
	);
	if ( 'whatsapp' === $name ) {
		echo '<svg class="a2ehc-ic" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.49 14.9L2 22l5.25-1.38A9.9 9.9 0 1 0 12.04 2Zm5.8 14.08c-.24.68-1.4 1.3-1.94 1.35-.5.05-1.13.07-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.8-4.17-4.94-4.37-.15-.2-1.19-1.58-1.19-3.02 0-1.43.75-2.13 1.02-2.42.27-.3.59-.37.78-.37.2 0 .39 0 .56.01.18.01.42-.07.66.5.24.58.82 2.01.89 2.16.07.15.12.32.02.51-.09.2-.14.32-.28.49-.14.17-.3.38-.42.51-.14.14-.29.3-.12.58.17.29.74 1.22 1.59 1.98 1.1.98 2.02 1.28 2.3 1.43.29.14.46.12.63-.07.17-.2.72-.84.91-1.13.2-.29.39-.24.66-.15.27.1 1.7.8 1.99.95.29.14.48.22.55.34.07.12.07.68-.17 1.35Z"/></svg>';
		return;
	}
	echo '<svg class="a2ehc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['check'] ) . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Remove long dashes so copy stays consistent with the A2E house style.
 *
 * @param string $text Text.
 * @return string
 */
function no_dashes( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return (string) $text;
	}
	$en = '(?:\x{2013}|&#8211;|&ndash;)';
	$em = '(?:\x{2014}|&#8212;|&mdash;)';
	return preg_replace(
		array( '/(\d)\s*(?:' . $en . '|' . $em . ')\s*(?=\d)/iu', '/\s+' . $en . '\s+/iu', '/\s*' . $em . '\s*/iu' ),
		array( '$1-', ' - ', ', ' ),
		$text
	);
}
