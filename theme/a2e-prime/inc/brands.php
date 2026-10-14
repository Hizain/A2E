<?php
/**
 * Supplier and marketplace wordmarks used in the hero, logo marquee and dashboard.
 *
 * Marks are typeset in brand colours (no official artwork is bundled). If you hold the
 * rights to an official logo file, upload it under A2E → Site Settings → Supplier logos and
 * it replaces the typeset version everywhere. All names are trademarks of their owners;
 * the footer carries a non-affiliation notice.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand registry.
 *
 * @return array key => [ name, role ('supplier'|'marketplace'|'carrier'), color ]
 */
function a2e_brands() {
	return apply_filters(
		'a2e_brands',
		array(
			'amazon'     => array( 'Amazon', 'supplier', '#FF9900' ),
			'walmart'    => array( 'Walmart', 'supplier', '#0071DC' ),
			'aliexpress' => array( 'AliExpress', 'supplier', '#E62E04' ),
			'homedepot'  => array( 'The Home Depot', 'supplier', '#F96302' ),
			'target'     => array( 'Target', 'supplier', '#CC0000' ),
			'costco'     => array( 'Costco', 'supplier', '#E31837' ),
			'bestbuy'    => array( 'Best Buy', 'supplier', '#0046BE' ),
			'alibaba'    => array( 'Alibaba', 'supplier', '#FF6A00' ),
			'cj'         => array( 'CJdropshipping', 'supplier', '#F28C28' ),
			'wayfair'    => array( 'Wayfair', 'supplier', '#7F187F' ),
			'newegg'     => array( 'Newegg', 'supplier', '#F7A41D' ),
			'macys'      => array( "Macy's", 'supplier', '#E21A2C' ),
			'ebay'       => array( 'eBay', 'marketplace', '#0064D2' ),
			'ups'        => array( 'UPS', 'carrier', '#7B4B2A' ),
			'fedex'      => array( 'FedEx', 'carrier', '#4D148C' ),
			'usps'       => array( 'USPS', 'carrier', '#333366' ),
		)
	);
}

/**
 * Wordmark markup for a brand.
 *
 * @param string $key   Brand key.
 * @param string $size  sm|md|lg.
 * @return string
 */
function a2e_brand_mark( $key, $size = 'md' ) {
	$brands = a2e_brands();
	if ( ! isset( $brands[ $key ] ) ) {
		return '';
	}
	$name   = $brands[ $key ][0];
	$custom = function_exists( 'a2e_opt' ) ? a2e_opt( 'brand_logo_' . $key ) : '';
	$cls    = 'bm bm--' . sanitize_html_class( $key ) . ' bm--' . sanitize_html_class( $size );

	if ( $custom ) {
		return sprintf( '<span class="%1$s bm--img"><img src="%2$s" alt="%3$s" loading="lazy" decoding="async"></span>', esc_attr( $cls ), esc_url( $custom ), esc_attr( $name ) );
	}

	switch ( $key ) {
		case 'amazon':
			$inner = '<span class="bm__t">amazon</span><svg class="bm__smile" viewBox="0 0 60 12" aria-hidden="true"><path d="M3 3.5c14 7.5 34 8 51 1" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/><path d="M49 1.2l6.4 2.6-3.6 5.4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			break;
		case 'ebay':
			$inner = '<span class="bm__t"><i class="e">e</i><i class="b">b</i><i class="a">a</i><i class="y">y</i></span>';
			break;
		case 'walmart':
			$inner = '<span class="bm__t">Walmart</span><svg class="bm__spark" viewBox="0 0 24 24" aria-hidden="true"><g fill="currentColor"><rect x="10.6" y="1" width="2.8" height="8" rx="1.4"/><rect x="10.6" y="15" width="2.8" height="8" rx="1.4"/><rect x="10.6" y="1" width="2.8" height="8" rx="1.4" transform="rotate(60 12 12)"/><rect x="10.6" y="15" width="2.8" height="8" rx="1.4" transform="rotate(60 12 12)"/><rect x="10.6" y="1" width="2.8" height="8" rx="1.4" transform="rotate(-60 12 12)"/><rect x="10.6" y="15" width="2.8" height="8" rx="1.4" transform="rotate(-60 12 12)"/></g></svg>';
			break;
		case 'target':
			$inner = '<svg class="bm__bull" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="currentColor"/><circle cx="12" cy="12" r="7.3" fill="#fff"/><circle cx="12" cy="12" r="3.9" fill="currentColor"/></svg><span class="bm__t">Target</span>';
			break;
		case 'homedepot':
			$inner = '<span class="bm__box"><span>THE</span><span>HOME</span><span>DEPOT</span></span>';
			break;
		case 'bestbuy':
			$inner = '<span class="bm__tag">BEST<br>BUY</span>';
			break;
		case 'costco':
			$inner = '<span class="bm__t">COSTCO</span>';
			break;
		case 'aliexpress':
			$inner = '<span class="bm__t">AliExpress</span>';
			break;
		case 'alibaba':
			$inner = '<span class="bm__t">Alibaba</span>';
			break;
		case 'cj':
			$inner = '<span class="bm__t"><b>CJ</b>dropshipping</span>';
			break;
		case 'wayfair':
			$inner = '<span class="bm__t">wayfair</span>';
			break;
		case 'newegg':
			$inner = '<span class="bm__t">newegg</span>';
			break;
		case 'macys':
			$inner = '<span class="bm__t">macy<i>★</i>s</span>';
			break;
		case 'fedex':
			$inner = '<span class="bm__t"><b>Fed</b><i>Ex</i></span>';
			break;
		default:
			$inner = '<span class="bm__t">' . esc_html( $name ) . '</span>';
	}

	return sprintf( '<span class="%1$s" role="img" aria-label="%2$s">%3$s</span>', esc_attr( $cls ), esc_attr( $name ), $inner );
}

/**
 * Supplier keys shown in marquees and the hero (in order).
 *
 * @return array
 */
function a2e_supplier_keys() {
	return array( 'amazon', 'walmart', 'aliexpress', 'homedepot', 'target', 'costco', 'bestbuy', 'alibaba', 'cj', 'wayfair', 'newegg', 'macys' );
}
