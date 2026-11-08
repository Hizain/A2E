<?php
/**
 * Settings, the consent cookie and small helpers.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const COOKIE_CONSENT = 'a2e_consent';
const COOKIE_VISITOR = 'a2e_vid';
const COOKIE_SESSION = 'a2e_sid';

/**
 * Default settings.
 *
 * @return array
 */
function defaults() {
	return array(
		// Banner.
		'banner_on'      => '1',
		'layout'         => 'card',
		'title'          => __( 'Your privacy, your choice', 'a2e-consent' ),
		'message'        => __( 'We use a few necessary cookies to run this site. With your OK we would also like to use analytics cookies to see which guides and tools help sellers most, and marketing cookies to measure our ads. Nothing optional runs until you choose.', 'a2e-consent' ),
		'label_accept'   => __( 'Accept all', 'a2e-consent' ),
		'label_reject'   => __( 'Reject all', 'a2e-consent' ),
		'label_choose'   => __( 'Choose', 'a2e-consent' ),
		'desc_necessary' => __( 'Keep the site working: remembering your cookie choice, light or dark mode, and keeping you signed in to your account. These cannot be switched off.', 'a2e-consent' ),
		'desc_analytics' => __( 'Help us see which pages and free tools are useful, where visitors come from and how long they stay. If you later send us a form, we link your visits on this site to your enquiry so we know how you found us. We never sell this data.', 'a2e-consent' ),
		'desc_marketing' => __( 'Measure our adverts on Google and Meta (Facebook and Instagram) and show our ads to people who visited before.', 'a2e-consent' ),
		'privacy_url'    => '',
		'footer_link'    => '1',
		'float_btn'      => '0',
		'lifetime'       => 6,
		'gpc'            => '1',
		'version'        => 1,
		// Tracking codes.
		'ga4_id'         => '',
		'gtm_id'         => '',
		'meta_pixel'     => '',
		'clarity_id'     => '',
		'mode'           => 'basic',
		'code_analytics' => '',
		'code_marketing' => '',
		// Insights.
		'insights'       => '1',
		'link_leads'     => '1',
		'exclude_staff'  => '1',
		'keep_hits'      => 13,
		'keep_log'       => 24,
	);
}

/**
 * All settings.
 *
 * @param bool $fresh Read again from the database.
 * @return array
 */
function settings( $fresh = false ) {
	static $cache = null;
	if ( null === $cache || $fresh ) {
		$saved = get_option( OPTION, array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), defaults() );
	}
	return $cache;
}

/**
 * One setting.
 *
 * @param string $key Key.
 * @return mixed
 */
function opt( $key ) {
	$s = settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/**
 * Save settings.
 *
 * @param array $values Settings.
 */
function update_settings( $values ) {
	update_option( OPTION, $values, false );
	settings( true );
}

/**
 * Is the A2E Prime theme running?
 *
 * @return bool
 */
function theme_active() {
	return function_exists( 'a2e_opt' );
}

/**
 * The GA4 ID in use: this plugin's own, else the one saved in the theme's settings.
 *
 * @return string
 */
function ga4_id() {
	$id = (string) opt( 'ga4_id' );
	if ( '' === $id && function_exists( 'a2e_opt' ) ) {
		$id = (string) a2e_opt( 'ga4_id' );
	}
	return clean_id( $id );
}

/**
 * Clean a tag ID (letters, digits and dashes).
 *
 * @param string $id ID.
 * @return string
 */
function clean_id( $id ) {
	return preg_replace( '/[^A-Z0-9-]/', '', strtoupper( trim( (string) $id ) ) );
}

/**
 * The privacy policy link.
 *
 * @return string
 */
function privacy_url() {
	$url = (string) opt( 'privacy_url' );
	if ( '' !== $url ) {
		return $url;
	}
	$url = get_privacy_policy_url();
	if ( $url ) {
		return $url;
	}
	if ( function_exists( 'a2e_url' ) ) {
		return a2e_url( 'privacy-policy' );
	}
	return home_url( '/privacy-policy/' );
}

/**
 * Consent lifetime in days.
 *
 * @return int
 */
function lifetime_days() {
	return max( 1, min( 13, (int) opt( 'lifetime' ) ) ) * 30;
}

/**
 * The visitor's consent, read from the consent cookie.
 *
 * Cookie format: {id}.{version}.{analytics}{marketing}.{unix time}
 *
 * @return array{id:string,version:int,analytics:bool,marketing:bool,time:int,valid:bool}
 */
function consent() {
	$out = array(
		'id'        => '',
		'version'   => 0,
		'analytics' => false,
		'marketing' => false,
		'time'      => 0,
		'valid'     => false,
	);
	if ( empty( $_COOKIE[ COOKIE_CONSENT ] ) ) {
		return $out;
	}
	$raw   = sanitize_text_field( wp_unslash( $_COOKIE[ COOKIE_CONSENT ] ) );
	$parts = explode( '.', $raw );
	if ( 4 !== count( $parts ) || ! preg_match( '/^[a-f0-9]{16}$/', $parts[0] ) || ! preg_match( '/^[01]{2}$/', $parts[2] ) ) {
		return $out;
	}
	$out['id']        = $parts[0];
	$out['version']   = (int) $parts[1];
	$out['analytics'] = '1' === $parts[2][0];
	$out['marketing'] = '1' === $parts[2][1];
	$out['time']      = (int) $parts[3];
	$out['valid']     = $out['version'] === (int) opt( 'version' );
	return $out;
}

/**
 * Does this visitor allow analytics right now?
 *
 * @return bool
 */
function analytics_allowed() {
	$c = consent();
	return $c['valid'] && $c['analytics'];
}

/**
 * The visitor ID from its cookie (set only after analytics consent).
 *
 * @return string
 */
function visitor_id() {
	$v = isset( $_COOKIE[ COOKIE_VISITOR ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ COOKIE_VISITOR ] ) ) : '';
	return preg_match( '/^[a-f0-9]{16}$/', $v ) ? $v : '';
}

/**
 * Is the current user staff whose visits are not counted?
 *
 * @return bool
 */
function is_excluded_staff() {
	return '1' === opt( 'exclude_staff' ) && is_user_logged_in() && current_user_can( 'edit_posts' );
}

/**
 * Bots and previews.
 *
 * @param string $ua User agent.
 * @return bool
 */
function is_bot( $ua ) {
	if ( '' === $ua ) {
		return true;
	}
	return (bool) preg_match( '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|preview|facebookexternalhit|embedly|quora link|whatsapp\/|telegrambot|python-|curl\/|wget|httpclient|java\//i', $ua );
}

/**
 * Current user agent.
 *
 * @return string
 */
function user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 400 ) : '';
}

/**
 * A short, non-identifying browser summary such as "Chrome on Windows (desktop)".
 *
 * @param string $ua User agent.
 * @return string
 */
function browser_summary( $ua ) {
	$browser = __( 'Other browser', 'a2e-consent' );
	$map     = array(
		'Edg/'     => 'Edge',
		'OPR/'     => 'Opera',
		'SamsungBrowser' => 'Samsung Internet',
		'CriOS'    => 'Chrome',
		'FxiOS'    => 'Firefox',
		'Firefox/' => 'Firefox',
		'Chrome/'  => 'Chrome',
		'Safari/'  => 'Safari',
	);
	foreach ( $map as $needle => $name ) {
		if ( false !== strpos( $ua, $needle ) ) {
			$browser = $name;
			break;
		}
	}
	$os = '';
	if ( preg_match( '/iPhone|iPad|iPod/', $ua ) ) {
		$os = 'iOS';
	} elseif ( false !== strpos( $ua, 'Android' ) ) {
		$os = 'Android';
	} elseif ( false !== strpos( $ua, 'Windows' ) ) {
		$os = 'Windows';
	} elseif ( false !== strpos( $ua, 'Mac OS X' ) || false !== strpos( $ua, 'Macintosh' ) ) {
		$os = 'macOS';
	} elseif ( false !== strpos( $ua, 'CrOS' ) ) {
		$os = 'ChromeOS';
	} elseif ( false !== strpos( $ua, 'Linux' ) ) {
		$os = 'Linux';
	}
	$device = preg_match( '/iPad|Tablet/i', $ua ) ? 'tablet' : ( preg_match( '/Mobi|iPhone|Android/i', $ua ) ? 'mobile' : 'desktop' );
	/* translators: 1: browser, 2: operating system, 3: device type */
	return trim( $os ? sprintf( __( '%1$s on %2$s (%3$s)', 'a2e-consent' ), $browser, $os, $device ) : $browser . ' (' . $device . ')' );
}

/**
 * Country code from a header set by Cloudflare or the host, if any. The IP address is never stored.
 *
 * @return string Two letters or ''.
 */
function country() {
	foreach ( array( 'HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_X_GEO_COUNTRY' ) as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$cc = strtoupper( substr( sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ), 0, 2 ) );
			if ( preg_match( '/^[A-Z]{2}$/', $cc ) && ! in_array( $cc, array( 'XX', 'T1' ), true ) ) {
				return $cc;
			}
		}
	}
	return '';
}

/**
 * The site's own host (without www.).
 *
 * @return string
 */
function own_host() {
	return preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
}

/**
 * Work out where a visit came from.
 *
 * @param string $ref_host     Referring host (lowercase).
 * @param array  $utm          utm_source, utm_medium, utm_campaign.
 * @param array  $click_ids    Ad click IDs present: gclid, fbclid, msclkid.
 * @return array{0:string,1:string} Source name and medium (organic, paid, social, email, ai, referral, direct).
 */
function classify( $ref_host, $utm = array(), $click_ids = array() ) {
	$ref_host = preg_replace( '/^(www|m|l|lm|mobile)\./', '', strtolower( (string) $ref_host ) );
	$source   = isset( $utm['utm_source'] ) ? strtolower( trim( $utm['utm_source'] ) ) : '';
	$medium   = isset( $utm['utm_medium'] ) ? strtolower( trim( $utm['utm_medium'] ) ) : '';

	$names = array(
		'google'    => 'Google',
		'bing'      => 'Bing',
		'facebook'  => 'Facebook',
		'fb'        => 'Facebook',
		'meta'      => 'Facebook',
		'instagram' => 'Instagram',
		'ig'        => 'Instagram',
		'linkedin'  => 'LinkedIn',
		'youtube'   => 'YouTube',
		'tiktok'    => 'TikTok',
		'twitter'   => 'X (Twitter)',
		'x'         => 'X (Twitter)',
		'reddit'    => 'Reddit',
		'pinterest' => 'Pinterest',
		'whatsapp'  => 'WhatsApp',
		'newsletter' => 'Newsletter',
		'a2e-mail'  => 'A2E Mail',
		'email'     => 'Email',
		'chatgpt'   => 'ChatGPT',
		'chatgpt.com' => 'ChatGPT',
		'openai'    => 'ChatGPT',
		'perplexity' => 'Perplexity',
		'fiverr'    => 'Fiverr',
		'upwork'    => 'Upwork',
		'whop'      => 'Whop',
		'ebay'      => 'eBay',
	);

	if ( '' !== $source ) {
		$name = isset( $names[ $source ] ) ? $names[ $source ] : ucfirst( substr( $source, 0, 50 ) );
		if ( preg_match( '/^(cpc|ppc|paid|paidsearch|paid_search|paid-search|paidsocial|paid_social|paid-social|display|cpm|ads?|retargeting|remarketing)$/', $medium ) ) {
			$medium = 'paid';
		} elseif ( preg_match( '/e-?mail|newsletter/', $medium ) ) {
			$medium = 'email';
		} elseif ( preg_match( '/^(social|social-media|social_media|sm|post|story|reel|bio)$/', $medium ) ) {
			$medium = 'social';
		} elseif ( '' === $medium ) {
			$medium = in_array( $name, array( 'Newsletter', 'A2E Mail', 'Email' ), true ) ? 'email' : 'referral';
		} else {
			$medium = substr( preg_replace( '/[^a-z0-9_-]/', '', $medium ), 0, 30 );
		}
		return array( $name, $medium );
	}
	if ( ! empty( $click_ids['gclid'] ) ) {
		return array( 'Google', 'paid' );
	}
	if ( ! empty( $click_ids['msclkid'] ) ) {
		return array( 'Bing', 'paid' );
	}
	if ( '' === $ref_host || own_host() === $ref_host ) {
		return ! empty( $click_ids['fbclid'] ) ? array( 'Facebook', 'social' ) : array( __( 'Direct', 'a2e-consent' ), 'direct' );
	}

	$rules = array(
		// AI assistants first (some live on search engine domains).
		'/^(chatgpt\.com|chat\.openai\.com)$/'           => array( 'ChatGPT', 'ai' ),
		'/(^|\.)perplexity\.ai$/'                       => array( 'Perplexity', 'ai' ),
		'/^claude\.ai$/'                                 => array( 'Claude', 'ai' ),
		'/^gemini\.google\.com$/'                        => array( 'Gemini', 'ai' ),
		'/^copilot\.microsoft\.com$/'                    => array( 'Copilot', 'ai' ),
		'/^(mail\.google\.com|outlook\.live\.com|outlook\.office(365)?\.com|mail\.yahoo\.com)$/' => array( __( 'Email (webmail)', 'a2e-consent' ), 'email' ),
		// Search engines.
		'/(^|\.)google\.[a-z.]+$/'                       => array( 'Google', 'organic' ),
		'/(^|\.)bing\.com$/'                             => array( 'Bing', 'organic' ),
		'/(^|\.)duckduckgo\.com$/'                       => array( 'DuckDuckGo', 'organic' ),
		'/(^|\.)yahoo\.[a-z.]+$/'                        => array( 'Yahoo', 'organic' ),
		'/(^|\.)yandex\.[a-z.]+$/'                       => array( 'Yandex', 'organic' ),
		'/(^|\.)ecosia\.org$/'                           => array( 'Ecosia', 'organic' ),
		'/^search\.brave\.com$/'                         => array( 'Brave Search', 'organic' ),
		'/(^|\.)baidu\.com$/'                            => array( 'Baidu', 'organic' ),
		// Social.
		'/(^|\.)facebook\.com$/'                         => array( 'Facebook', 'social' ),
		'/(^|\.)instagram\.com$/'                        => array( 'Instagram', 'social' ),
		'/(^|\.)(linkedin\.com|lnkd\.in)$/'              => array( 'LinkedIn', 'social' ),
		'/^(t\.co|twitter\.com|x\.com)$/'                => array( 'X (Twitter)', 'social' ),
		'/(^|\.)(youtube\.com|youtu\.be)$/'              => array( 'YouTube', 'social' ),
		'/(^|\.)reddit\.com$/'                           => array( 'Reddit', 'social' ),
		'/(^|\.)pinterest\.[a-z.]+$/'                    => array( 'Pinterest', 'social' ),
		'/(^|\.)tiktok\.com$/'                           => array( 'TikTok', 'social' ),
		'/(^|\.)quora\.com$/'                            => array( 'Quora', 'social' ),
		'/(^|\.)(whatsapp\.com|wa\.me)$/'                => array( 'WhatsApp', 'social' ),
		'/^t\.me$/'                                      => array( 'Telegram', 'social' ),
		// Marketplaces and partners.
		'/^community\.ebay\.[a-z.]+$/'                   => array( __( 'eBay Community', 'a2e-consent' ), 'referral' ),
		'/(^|\.)ebay\.[a-z.]+$/'                         => array( 'eBay', 'referral' ),
		'/(^|\.)whop\.com$/'                             => array( 'Whop', 'referral' ),
		'/(^|\.)fiverr\.com$/'                           => array( 'Fiverr', 'referral' ),
		'/(^|\.)upwork\.com$/'                           => array( 'Upwork', 'referral' ),
	);
	foreach ( $rules as $pattern => $result ) {
		if ( preg_match( $pattern, $ref_host ) ) {
			if ( ! empty( $click_ids['fbclid'] ) && in_array( $result[0], array( 'Facebook', 'Instagram' ), true ) ) {
				return array( $result[0], 'social' );
			}
			return $result;
		}
	}
	return array( substr( $ref_host, 0, 60 ), 'referral' );
}

/**
 * Readable medium names.
 *
 * @return array
 */
function medium_labels() {
	return array(
		'organic'  => __( 'Search', 'a2e-consent' ),
		'paid'     => __( 'Paid ads', 'a2e-consent' ),
		'social'   => __( 'Social', 'a2e-consent' ),
		'email'    => __( 'Email', 'a2e-consent' ),
		'ai'       => __( 'AI assistant', 'a2e-consent' ),
		'referral' => __( 'Other website', 'a2e-consent' ),
		'direct'   => __( 'Direct or unknown', 'a2e-consent' ),
	);
}

/**
 * Readable medium.
 *
 * @param string $medium Medium.
 * @return string
 */
function medium_label( $medium ) {
	$labels = medium_labels();
	return isset( $labels[ $medium ] ) ? $labels[ $medium ] : ucfirst( $medium );
}

/**
 * Form names used by the A2E theme and plugins.
 *
 * @param string $type Form key.
 * @return string
 */
function form_label( $type ) {
	$labels = array(
		'quote'        => __( 'Free store audit', 'a2e-consent' ),
		'consultation' => __( 'Consultation', 'a2e-consent' ),
		'contact'      => __( 'Contact form', 'a2e-consent' ),
		'newsletter'   => __( 'Newsletter signup', 'a2e-consent' ),
		'health-check' => __( 'Account health report', 'a2e-consent' ),
	);
	return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( str_replace( array( '-', '_' ), ' ', $type ) );
}

/**
 * Seconds as "2m 05s".
 *
 * @param int $s Seconds.
 * @return string
 */
function duration( $s ) {
	$s = (int) round( $s );
	if ( $s < 60 ) {
		return $s . 's';
	}
	if ( $s < 3600 ) {
		return floor( $s / 60 ) . 'm ' . str_pad( (string) ( $s % 60 ), 2, '0', STR_PAD_LEFT ) . 's';
	}
	return floor( $s / 3600 ) . 'h ' . str_pad( (string) floor( ( $s % 3600 ) / 60 ), 2, '0', STR_PAD_LEFT ) . 'm';
}

/**
 * A UTC database time shown in the site's time zone.
 *
 * @param string $utc    Y-m-d H:i:s (UTC).
 * @param string $format Format, default the site's date and time format.
 * @return string
 */
function local_time( $utc, $format = '' ) {
	if ( ! $utc ) {
		return '';
	}
	$ts = strtotime( $utc . ' UTC' );
	return wp_date( $format ? $format : get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts );
}

/**
 * Table names.
 *
 * @param string $which hits|log.
 * @return string
 */
function table( $which ) {
	global $wpdb;
	return $wpdb->prefix . ( 'log' === $which ? 'a2ec_consent_log' : 'a2ec_hits' );
}

/**
 * Post types that hold form leads (the A2E theme and the Health Checker plugin).
 *
 * @return string[]
 */
function lead_types() {
	return array( 'a2e_lead', 'a2ehc_lead' );
}
