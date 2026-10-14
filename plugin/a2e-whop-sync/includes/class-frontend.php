<?php
/**
 * Front end: feeds plans into the A2E theme and provides shortcodes for any other theme.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front end.
 */
class Frontend {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'a2e_pricing_plans', array( __CLASS__, 'theme_plans' ), 20 );
		add_filter( 'a2e_billing_portal_url', array( __CLASS__, 'portal_url' ) );
		add_filter( 'a2e_whop_connected', __NAMESPACE__ . '\\is_connected' );
		add_shortcode( 'a2e_whop_plans', array( __CLASS__, 'plans_shortcode' ) );
		add_shortcode( 'a2e_whop_button', array( __CLASS__, 'button_shortcode' ) );
	}

	/**
	 * Replace the theme's fallback plans with live Whop plans (when there are any).
	 *
	 * @param array $fallback Theme plans.
	 * @return array
	 */
	public static function theme_plans( $fallback ) {
		$plans = Plans::for_site();
		return $plans ? $plans : $fallback;
	}

	/**
	 * Customer billing portal.
	 *
	 * @param string $url Default.
	 * @return string
	 */
	public static function portal_url( $url ) {
		$custom = setting( 'portal_url' );
		return $custom ? $custom : $url;
	}

	/**
	 * [a2e_whop_plans]: simple plan cards for themes other than A2E Prime.
	 *
	 * @return string
	 */
	public static function plans_shortcode() {
		if ( function_exists( 'a2e_render_pricing' ) ) {
			ob_start();
			a2e_render_pricing();
			return ob_get_clean();
		}
		$plans = Plans::for_site();
		if ( ! $plans ) {
			return '';
		}
		$out = '<div class="a2ews-plans" style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">';
		foreach ( $plans as $p ) {
			$out .= '<div style="border:1px solid #ddd;border-radius:14px;padding:22px"><h3 style="margin-top:0">' . esc_html( $p['name'] ) . '</h3><p style="font-size:2rem;font-weight:700;margin:.2em 0">' . esc_html( $p['price'] ) . ' <small style="font-size:1rem;font-weight:400">' . esc_html( $p['period'] ) . '</small></p><p>' . esc_html( $p['desc'] ) . '</p>';
			if ( $p['features'] ) {
				$out .= '<ul>';
				foreach ( $p['features'] as $f ) {
					$out .= '<li>' . esc_html( $f ) . '</li>';
				}
				$out .= '</ul>';
			}
			$out .= '<p><a class="wp-element-button" href="' . esc_url( $p['url'] ) . '">' . esc_html( $p['button'] ) . '</a></p></div>';
		}
		return $out . '</div>';
	}

	/**
	 * [a2e_whop_button plan="123" label="Subscribe"]: a checkout button for one plan (WordPress plan ID).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function button_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'plan' => 0, 'label' => __( 'Subscribe', 'a2e-whop' ) ), $atts );
		$url  = (string) get_post_meta( absint( $atts['plan'] ), '_a2ews_purchase_url', true );
		if ( '' === $url ) {
			return '';
		}
		return '<a class="btn btn--primary wp-element-button" href="' . esc_url( $url ) . '"><span>' . esc_html( $atts['label'] ) . '</span></a>';
	}
}
