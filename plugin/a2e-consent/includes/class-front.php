<?php
/**
 * The banner, the preferences window and the tracking codes that wait for consent.
 *
 * Everything that decides what runs happens in the visitor's browser, so the banner works on
 * pages served from a full-page cache.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front end.
 */
class Front {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_shortcode( 'a2e_cookie_settings', array( __CLASS__, 'shortcode_settings' ) );
		add_shortcode( 'a2e_cookie_list', array( __CLASS__, 'shortcode_list' ) );
		if ( '1' !== opt( 'banner_on' ) ) {
			return;
		}
		// The theme's own Google Analytics tag runs for everyone; this plugin loads it after consent instead.
		add_action( 'wp_head', array( __CLASS__, 'remove_theme_tag' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'footer' ), 5 );
	}

	/**
	 * Should anything be shown on this request?
	 *
	 * @return bool
	 */
	protected static function active() {
		return ! ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) );
	}

	/**
	 * Do the tracking codes run for this visitor? (Not for logged-in staff by default.)
	 *
	 * @return bool
	 */
	protected static function tags_run() {
		return ! is_excluded_staff();
	}

	/**
	 * Remove the theme's Google Analytics tag.
	 */
	public static function remove_theme_tag() {
		remove_action( 'wp_head', 'a2e_ga4', 50 );
	}

	/**
	 * Google Consent Mode defaults, as early as possible in the page. Everything is "denied" until
	 * the visitor chooses; a returning visitor's saved choice is applied straight away.
	 */
	public static function head() {
		if ( ! self::active() ) {
			return;
		}
		$version = (int) opt( 'version' );
		$js      = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
			. '(function(){var a=0,m=0,c=document.cookie.match(/(?:^|; )a2e_consent=([^;]*)/);'
			. 'if(c){var p=decodeURIComponent(c[1]).split(".");if(p.length===4&&+p[1]===' . $version . '&&/^[01]{2}$/.test(p[2])){a=p[2].charAt(0)==="1";m=p[2].charAt(1)==="1";}}'
			. ( '1' === opt( 'gpc' ) ? 'if(navigator.globalPrivacyControl){m=0;}' : '' )
			. 'var g=function(x){return x?"granted":"denied";};'
			. 'gtag("consent","default",{ad_storage:g(m),ad_user_data:g(m),ad_personalization:g(m),analytics_storage:g(a),functionality_storage:"granted",security_storage:"granted",wait_for_update:500});'
			. 'gtag("set","ads_data_redaction",true);'
			. 'window.A2EC_STATE={a:!!a,m:!!m};})();';
		echo "<script id=\"a2ec-consent-defaults\">{$js}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Advanced consent mode: Google tags load now and respect the "denied" state (cookieless pings).
		if ( 'advanced' === opt( 'mode' ) && self::tags_run() ) {
			$ga4 = ga4_id();
			$gtm = clean_id( opt( 'gtm_id' ) );
			if ( $ga4 ) {
				printf( '<script async src="%s"></script>' . "\n", esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . $ga4 ) );
				printf( '<script>gtag("js",new Date());gtag("config","%s");</script>' . "\n", esc_js( $ga4 ) );
			}
			if ( $gtm ) {
				printf( '<script>dataLayer.push({"gtm.start":new Date().getTime(),event:"gtm.js"});</script><script async src="%s"></script>' . "\n", esc_url( 'https://www.googletagmanager.com/gtm.js?id=' . $gtm ) );
			}
		}
	}

	/**
	 * Settings passed to the browser.
	 *
	 * @return array
	 */
	public static function config() {
		$run = self::tags_run();
		return array(
			'v'        => (int) opt( 'version' ),
			'days'     => lifetime_days(),
			'gpc'      => '1' === opt( 'gpc' ),
			'rest'     => esc_url_raw( rest_url( 'a2e-consent/v1/' ) ),
			'mode'     => 'advanced' === opt( 'mode' ) ? 'advanced' : 'basic',
			'insights' => $run && '1' === opt( 'insights' ),
			'staff'    => ! $run,
			'footer'   => '1' === opt( 'footer_link' ),
			'float'    => '1' === opt( 'float_btn' ),
			'tags'     => $run ? array(
				'ga4'     => ga4_id(),
				'gtm'     => clean_id( opt( 'gtm_id' ) ),
				'meta'    => preg_replace( '/\D/', '', (string) opt( 'meta_pixel' ) ),
				'clarity' => preg_replace( '/[^a-z0-9]/i', '', (string) opt( 'clarity_id' ) ),
			) : (object) array(),
			'i18n'     => array(
				'settings' => __( 'Cookie settings', 'a2e-consent' ),
				'saved'    => __( 'Your cookie choices are saved.', 'a2e-consent' ),
			),
		);
	}

	/**
	 * Styles and script.
	 */
	public static function assets() {
		if ( ! self::active() ) {
			return;
		}
		$deps = wp_style_is( 'a2e-main', 'registered' ) ? array( 'a2e-main' ) : array();
		wp_enqueue_style( 'a2ec', A2EC_URL . 'assets/consent.css', $deps, VERSION );
		wp_enqueue_script(
			'a2ec',
			A2EC_URL . 'assets/consent.js',
			array(),
			VERSION,
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script( 'a2ec', 'window.A2EC=' . wp_json_encode( self::config() ) . ';', 'before' );
	}

	/**
	 * Cookie icon.
	 *
	 * @return string
	 */
	protected static function icon() {
		return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 12.2A9 9 0 1 1 11.8 3a3 3 0 0 0 3.7 3.7 3 3 0 0 0 3.8 3.8c.5.5 1.1.8 1.7.9z"/><circle cx="8.5" cy="10.5" r="1" fill="currentColor"/><circle cx="12" cy="15.5" r="1" fill="currentColor"/><circle cx="16" cy="13" r=".9" fill="currentColor"/><circle cx="8" cy="15" r=".7" fill="currentColor"/></svg>';
	}

	/**
	 * The cookies each category uses, built from the codes you have set up.
	 *
	 * @return array category => list of [name, provider, purpose, duration]
	 */
	public static function cookie_list() {
		$months = (int) ceil( lifetime_days() / 30 );
		/* translators: %d: number of months */
		$life = sprintf( _n( '%d month', '%d months', $months, 'a2e-consent' ), $months );
		$site = __( 'This site', 'a2e-consent' );
		$list = array(
			'necessary' => array(
				array( COOKIE_CONSENT, $site, __( 'Remembers your cookie choices.', 'a2e-consent' ), $life ),
				array( 'a2e-theme ' . __( '(local storage)', 'a2e-consent' ), $site, __( 'Remembers light or dark mode if you pick one.', 'a2e-consent' ), __( 'Until you clear it', 'a2e-consent' ) ),
				array( 'wordpress_logged_in_*, wordpress_sec_*', $site, __( 'Keeps you signed in to your account (only if you sign in).', 'a2e-consent' ), __( 'Session or 14 days', 'a2e-consent' ) ),
			),
			'analytics' => array(),
			'marketing' => array(),
		);
		if ( '1' === opt( 'insights' ) ) {
			$list['analytics'][] = array( COOKIE_VISITOR, $site, __( 'Recognises your browser when you come back, so we can count visitors and see how people found us.', 'a2e-consent' ), $life );
			$list['analytics'][] = array( COOKIE_SESSION, $site, __( 'Groups the pages you view into one visit.', 'a2e-consent' ), __( '30 minutes', 'a2e-consent' ) );
		}
		if ( ga4_id() ) {
			$list['analytics'][] = array( '_ga, _ga_*', 'Google Analytics', __( 'Counts visitors and visits and shows how the site is used.', 'a2e-consent' ), __( '2 years', 'a2e-consent' ) );
		}
		if ( opt( 'clarity_id' ) ) {
			$list['analytics'][] = array( '_clck, _clsk, CLID, MUID', 'Microsoft Clarity', __( 'Heatmaps and recordings of how pages are used (form fields are masked).', 'a2e-consent' ), __( 'Up to 1 year', 'a2e-consent' ) );
		}
		if ( opt( 'code_analytics' ) ) {
			$list['analytics'][] = array( __( 'Other analytics tools', 'a2e-consent' ), __( 'Third parties', 'a2e-consent' ), __( 'Extra analytics we add from time to time.', 'a2e-consent' ), __( 'Varies', 'a2e-consent' ) );
		}
		if ( ga4_id() || opt( 'gtm_id' ) ) {
			$list['marketing'][] = array( '_gcl_au, _gcl_aw', 'Google Ads', __( 'Measures which Google ads lead to enquiries.', 'a2e-consent' ), __( '3 months', 'a2e-consent' ) );
		}
		if ( opt( 'meta_pixel' ) ) {
			$list['marketing'][] = array( '_fbp, _fbc', 'Meta (Facebook, Instagram)', __( 'Measures our ads and shows our ads to people who visited before.', 'a2e-consent' ), __( '3 months', 'a2e-consent' ) );
		}
		if ( opt( 'gtm_id' ) ) {
			$list['marketing'][] = array( __( 'Tags in Google Tag Manager', 'a2e-consent' ), 'Google', __( 'Marketing tags we manage in Google Tag Manager.', 'a2e-consent' ), __( 'Varies', 'a2e-consent' ) );
		}
		if ( opt( 'code_marketing' ) ) {
			$list['marketing'][] = array( __( 'Other marketing tags', 'a2e-consent' ), __( 'Third parties', 'a2e-consent' ), __( 'Extra ad measurement we add from time to time.', 'a2e-consent' ), __( 'Varies', 'a2e-consent' ) );
		}
		return $list;
	}

	/**
	 * Cookie table HTML.
	 *
	 * @param array $rows Rows.
	 * @return string
	 */
	protected static function cookie_rows( $rows ) {
		if ( ! $rows ) {
			return '<p class="a2ec-none">' . esc_html__( 'Nothing in this group is in use at the moment.', 'a2e-consent' ) . '</p>';
		}
		$html = '<ul class="a2ec-cookies">';
		foreach ( $rows as $r ) {
			$html .= '<li><strong>' . esc_html( $r[0] ) . '</strong> <span>' . esc_html( $r[1] ) . ' · ' . esc_html( $r[3] ) . '</span><p>' . esc_html( $r[2] ) . '</p></li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Banner, preferences window and the codes that wait for consent.
	 */
	public static function footer() {
		if ( ! self::active() ) {
			return;
		}
		$own     = theme_active() ? '' : ' a2ec--own';
		$layout  = 'bar' === opt( 'layout' ) ? 'a2ec--bar' : 'a2ec--card';
		$list    = self::cookie_list();
		$privacy = privacy_url();
		$cats    = array(
			'analytics' => array( __( 'Analytics', 'a2e-consent' ), opt( 'desc_analytics' ) ),
			'marketing' => array( __( 'Marketing', 'a2e-consent' ), opt( 'desc_marketing' ) ),
		);
		?>
<div class="a2ec <?php echo esc_attr( $layout . $own ); ?>" id="a2ec" hidden>
	<section class="a2ec-banner" aria-labelledby="a2ec-title" aria-describedby="a2ec-msg">
		<span class="a2ec-banner__icon"><?php echo self::icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="a2ec-banner__body">
			<h2 class="a2ec-banner__title" id="a2ec-title"><?php echo esc_html( opt( 'title' ) ); ?></h2>
			<p class="a2ec-banner__msg" id="a2ec-msg"><?php echo esc_html( opt( 'message' ) ); ?> <a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', 'a2e-consent' ); ?></a></p>
			<p class="a2ec-note" data-a2ec-gpc hidden><?php esc_html_e( 'Your browser asks sites not to share your data (Global Privacy Control), so marketing cookies stay off.', 'a2e-consent' ); ?></p>
		</div>
		<div class="a2ec-banner__actions">
			<button type="button" class="a2ec-btn a2ec-btn--quiet" data-a2ec="choose"><?php echo esc_html( opt( 'label_choose' ) ); ?></button>
			<button type="button" class="a2ec-btn" data-a2ec="reject"><?php echo esc_html( opt( 'label_reject' ) ); ?></button>
			<button type="button" class="a2ec-btn" data-a2ec="accept"><?php echo esc_html( opt( 'label_accept' ) ); ?></button>
		</div>
	</section>
</div>
<dialog class="a2ec-dialog<?php echo esc_attr( $own ); ?>" id="a2ec-prefs" aria-labelledby="a2ec-prefs-title">
	<div class="a2ec-prefs">
		<div class="a2ec-prefs__head">
			<h2 id="a2ec-prefs-title"><?php esc_html_e( 'Cookie settings', 'a2e-consent' ); ?></h2>
			<button type="button" class="a2ec-x" data-a2ec="close" aria-label="<?php esc_attr_e( 'Close', 'a2e-consent' ); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
		</div>
		<div class="a2ec-prefs__body">
			<p class="a2ec-prefs__intro"><?php esc_html_e( 'Choose which cookies we may use. You can change your mind at any time from "Cookie settings" at the bottom of every page.', 'a2e-consent' ); ?> <a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', 'a2e-consent' ); ?></a></p>
			<?php if ( ! self::tags_run() ) : ?>
				<p class="a2ec-note a2ec-note--staff"><?php esc_html_e( 'You are signed in as site staff, so tracking codes do not run and your visits are not counted. Use a private window to test them.', 'a2e-consent' ); ?></p>
			<?php endif; ?>
			<div class="a2ec-cat">
				<div class="a2ec-cat__head">
					<h3><?php esc_html_e( 'Necessary', 'a2e-consent' ); ?></h3>
					<span class="a2ec-always"><?php esc_html_e( 'Always on', 'a2e-consent' ); ?></span>
				</div>
				<p><?php echo esc_html( opt( 'desc_necessary' ) ); ?></p>
				<details><summary><?php esc_html_e( 'Cookies used', 'a2e-consent' ); ?></summary><?php echo self::cookie_rows( $list['necessary'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></details>
			</div>
			<?php foreach ( $cats as $key => $cat ) : ?>
				<div class="a2ec-cat">
					<div class="a2ec-cat__head">
						<h3><label for="a2ec-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $cat[0] ); ?></label></h3>
						<span class="a2ec-switch"><input type="checkbox" role="switch" id="a2ec-<?php echo esc_attr( $key ); ?>" data-a2ec-cat="<?php echo esc_attr( $key ); ?>"><span aria-hidden="true"></span></span>
					</div>
					<p><?php echo esc_html( $cat[1] ); ?></p>
					<?php if ( 'marketing' === $key ) : ?>
						<p class="a2ec-note" data-a2ec-gpc hidden><?php esc_html_e( 'Off because your browser sends a Global Privacy Control signal.', 'a2e-consent' ); ?></p>
					<?php endif; ?>
					<details><summary><?php esc_html_e( 'Cookies used', 'a2e-consent' ); ?></summary><?php echo self::cookie_rows( $list[ $key ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></details>
				</div>
			<?php endforeach; ?>
			<p class="a2ec-id" data-a2ec-id hidden><?php esc_html_e( 'Your consent ID:', 'a2e-consent' ); ?> <code></code> <span><?php esc_html_e( 'Quote it if you ask us about your data.', 'a2e-consent' ); ?></span></p>
		</div>
		<div class="a2ec-prefs__foot">
			<button type="button" class="a2ec-btn" data-a2ec="reject"><?php echo esc_html( opt( 'label_reject' ) ); ?></button>
			<button type="button" class="a2ec-btn a2ec-btn--quiet" data-a2ec="save"><?php esc_html_e( 'Save my choices', 'a2e-consent' ); ?></button>
			<button type="button" class="a2ec-btn" data-a2ec="accept"><?php echo esc_html( opt( 'label_accept' ) ); ?></button>
		</div>
	</div>
</dialog>
<button type="button" class="a2ec-float<?php echo esc_attr( $own ); ?>" data-a2ec="open" aria-label="<?php esc_attr_e( 'Cookie settings', 'a2e-consent' ); ?>" hidden><?php echo self::icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
<div class="a2ec-toast" role="status" aria-live="polite"></div>
		<?php
		if ( self::tags_run() ) {
			foreach ( array( 'analytics', 'marketing' ) as $cat ) {
				$code = (string) opt( 'code_' . $cat );
				if ( '' !== trim( $code ) ) {
					echo '<template id="a2ec-code-' . esc_attr( $cat ) . '">' . $code . '</template>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Saved by an administrator with unfiltered_html.
				}
			}
		}
	}

	/**
	 * [a2e_cookie_settings text="Cookie settings"]: a link that opens the preferences window.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode_settings( $atts ) {
		$atts = shortcode_atts( array( 'text' => __( 'Cookie settings', 'a2e-consent' ) ), $atts, 'a2e_cookie_settings' );
		return '<button type="button" class="a2ec-link" data-a2ec="open">' . esc_html( $atts['text'] ) . '</button>';
	}

	/**
	 * [a2e_cookie_list]: the cookies this site uses, for your privacy or cookie policy page.
	 *
	 * @return string
	 */
	public static function shortcode_list() {
		$list  = self::cookie_list();
		$names = array(
			'necessary' => __( 'Necessary', 'a2e-consent' ),
			'analytics' => __( 'Analytics (only with your consent)', 'a2e-consent' ),
			'marketing' => __( 'Marketing (only with your consent)', 'a2e-consent' ),
		);
		$html  = '<div class="a2ec-list">';
		foreach ( $names as $key => $label ) {
			$html .= '<h3>' . esc_html( $label ) . '</h3>';
			if ( ! $list[ $key ] ) {
				$html .= '<p>' . esc_html__( 'None in use at the moment.', 'a2e-consent' ) . '</p>';
				continue;
			}
			$html .= '<div class="a2ec-table-wrap"><table class="a2ec-table"><thead><tr><th>' . esc_html__( 'Cookie', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Set by', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Purpose', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Kept for', 'a2e-consent' ) . '</th></tr></thead><tbody>';
			foreach ( $list[ $key ] as $r ) {
				$html .= '<tr><td><code>' . esc_html( $r[0] ) . '</code></td><td>' . esc_html( $r[1] ) . '</td><td>' . esc_html( $r[2] ) . '</td><td>' . esc_html( $r[3] ) . '</td></tr>';
			}
			$html .= '</tbody></table></div>';
		}
		$html .= '<p><button type="button" class="a2ec-link" data-a2ec="open">' . esc_html__( 'Change your cookie settings', 'a2e-consent' ) . '</button></p></div>';
		return $html;
	}
}
