<?php
/**
 * Admin screens: Insights dashboard, Visitors, Consent log and Settings.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin.
 */
class Admin {

	const SLUG = 'a2ec';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_a2ec_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_a2ec_reask', array( __CLASS__, 'reask' ) );
		add_action( 'admin_post_a2ec_wipe', array( __CLASS__, 'wipe' ) );
		add_action( 'admin_post_a2ec_forget', array( __CLASS__, 'forget' ) );
		add_action( 'admin_post_a2ec_export', array( __CLASS__, 'export' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'widget' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( A2EC_FILE ), array( __CLASS__, 'links' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_menu_page( __( 'Visitor insights', 'a2e-consent' ), __( 'Insights', 'a2e-consent' ), 'manage_options', self::SLUG, array( __CLASS__, 'render_dashboard' ), 'dashicons-chart-area', 6 );
		add_submenu_page( self::SLUG, __( 'Visitor insights', 'a2e-consent' ), __( 'Dashboard', 'a2e-consent' ), 'manage_options', self::SLUG, array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( self::SLUG, __( 'Visitors', 'a2e-consent' ), __( 'Visitors', 'a2e-consent' ), 'manage_options', 'a2ec-visitors', array( __CLASS__, 'render_visitors' ) );
		add_submenu_page( self::SLUG, __( 'Consent log', 'a2e-consent' ), __( 'Consent log', 'a2e-consent' ), 'manage_options', 'a2ec-log', array( __CLASS__, 'render_log' ) );
		add_submenu_page( self::SLUG, __( 'Cookie banner and tracking settings', 'a2e-consent' ), __( 'Settings', 'a2e-consent' ), 'manage_options', 'a2ec-settings', array( __CLASS__, 'render_settings' ) );
	}

	/**
	 * Plugins screen links.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=a2ec-settings' ) ) . '">' . esc_html__( 'Settings', 'a2e-consent' ) . '</a>' );
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Insights', 'a2e-consent' ) . '</a>' );
		return $links;
	}

	/**
	 * Admin assets on this plugin's screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$ours   = false !== strpos( $hook, 'a2ec' ) || ( $screen && in_array( $screen->post_type, lead_types(), true ) ) || 'index.php' === $hook;
		if ( $ours ) {
			wp_enqueue_style( 'a2ec-admin', A2EC_URL . 'assets/admin.css', array(), VERSION );
		}
	}

	/**
	 * Notice after an action.
	 */
	public static function notices() {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( (string) $screen->id, 'a2ec' ) ) {
			return;
		}
		$msg = get_transient( 'a2ec_notice_' . get_current_user_id() );
		if ( $msg ) {
			delete_transient( 'a2ec_notice_' . get_current_user_id() );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
		if ( '1' !== opt( 'banner_on' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'The cookie banner is switched off, so nothing from this plugin runs on your site and your theme\'s Google Analytics tag runs without asking. Switch it on in Insights → Settings.', 'a2e-consent' ) . '</p></div>';
		}
	}

	/**
	 * Remember a notice and go back.
	 *
	 * @param string $msg  Message.
	 * @param string $page Admin page.
	 * @param array  $args Extra query args.
	 */
	protected static function back( $msg, $page, $args = array() ) {
		set_transient( 'a2ec_notice_' . get_current_user_id(), $msg, 60 );
		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Check rights and nonce.
	 *
	 * @param string $action Nonce action.
	 */
	protected static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'a2e-consent' ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Readable click type.
	 *
	 * @param string $kind Kind.
	 * @return string
	 */
	public static function click_label( $kind ) {
		$labels = array(
			'whatsapp' => __( 'WhatsApp', 'a2e-consent' ),
			'phone'    => __( 'Phone', 'a2e-consent' ),
			'email'    => __( 'Email link', 'a2e-consent' ),
			'whop'     => __( 'Whop checkout', 'a2e-consent' ),
			'outbound' => __( 'Other website', 'a2e-consent' ),
			'download' => __( 'Download', 'a2e-consent' ),
			'share'    => __( 'Share', 'a2e-consent' ),
		);
		return isset( $labels[ $kind ] ) ? $labels[ $kind ] : ucfirst( $kind );
	}

	/**
	 * Header with the page title and tabs.
	 *
	 * @param string $title  Title.
	 * @param string $active Active page slug.
	 */
	protected static function header( $title, $active ) {
		$tabs = array(
			self::SLUG      => __( 'Dashboard', 'a2e-consent' ),
			'a2ec-visitors' => __( 'Visitors', 'a2e-consent' ),
			'a2ec-log'      => __( 'Consent log', 'a2e-consent' ),
			'a2ec-settings' => __( 'Settings', 'a2e-consent' ),
		);
		echo '<div class="wrap a2ec-admin"><h1 class="a2ec-h1">' . esc_html( $title ) . '</h1>';
		echo '<nav class="nav-tab-wrapper a2ec-tabs">';
		foreach ( $tabs as $slug => $label ) {
			printf( '<a href="%s" class="nav-tab%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . $slug ) ), $slug === $active ? ' nav-tab-active' : '', esc_html( $label ) );
		}
		echo '</nav>';
	}

	/**
	 * Selected range in days.
	 *
	 * @return int
	 */
	protected static function days() {
		$d = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
		return in_array( $d, array( 1, 7, 30, 90, 365 ), true ) ? $d : 30;
	}

	/**
	 * Change against the period before, as a small badge.
	 *
	 * @param float $now    Now.
	 * @param float $before Before.
	 * @return string
	 */
	protected static function delta( $now, $before ) {
		if ( ! $before ) {
			return $now ? '<span class="a2ec-delta is-new">' . esc_html__( 'new', 'a2e-consent' ) . '</span>' : '';
		}
		$pct = round( ( $now - $before ) / $before * 100 );
		/* translators: %s: value in the period before */
		$tip = esc_attr( sprintf( __( 'Period before: %s', 'a2e-consent' ), number_format_i18n( $before, is_float( $before ) ? 1 : 0 ) ) );
		if ( 0.0 === (float) $pct ) {
			return '<span class="a2ec-delta" title="' . $tip . '">0%</span>';
		}
		$text = $pct > 999 ? '+999%' : ( $pct > 0 ? '+' : '' ) . $pct . '%';
		return '<span class="a2ec-delta ' . ( $pct > 0 ? 'is-up' : 'is-down' ) . '" title="' . $tip . '">' . esc_html( $text ) . '</span>';
	}

	/**
	 * A list of bars.
	 *
	 * @param array  $rows  [label, value, extra].
	 * @param string $empty Empty text.
	 */
	protected static function bars( $rows, $empty ) {
		if ( ! $rows ) {
			echo '<p class="a2ec-empty">' . esc_html( $empty ) . '</p>';
			return;
		}
		$max = max( 1, max( array_map( function ( $r ) {
			return (int) $r[1];
		}, $rows ) ) );
		echo '<ul class="a2ec-bars">';
		foreach ( $rows as $r ) {
			printf(
				'<li><span class="a2ec-bars__label">%1$s</span><span class="a2ec-bars__value">%2$s%3$s</span><span class="a2ec-bars__track"><span style="width:%4$s%%"></span></span></li>',
				esc_html( $r[0] ),
				esc_html( number_format_i18n( (int) $r[1] ) ),
				isset( $r[2] ) && '' !== $r[2] ? ' <em>' . esc_html( $r[2] ) . '</em>' : '',
				esc_attr( (string) max( 2, round( (int) $r[1] / $max * 100 ) ) )
			);
		}
		echo '</ul>';
	}

	/**
	 * Lead name/email for display.
	 *
	 * @param int $lead_id Lead.
	 * @return array{name:string,email:string,link:string}|null
	 */
	protected static function lead_info( $lead_id ) {
		$post = $lead_id ? get_post( $lead_id ) : null;
		if ( ! $post ) {
			return null;
		}
		$email = (string) get_post_meta( $lead_id, '_a2e_email', true );
		$name  = preg_replace( '/^[^:]+:\s*/', '', $post->post_title );
		return array(
			'name'  => $name ? $name : $email,
			'email' => $email,
			'link'  => (string) get_edit_post_link( $lead_id, 'raw' ),
		);
	}

	/* Dashboard ---------------------------------------------------------------------- */

	/**
	 * Dashboard.
	 */
	public static function render_dashboard() {
		$days          = self::days();
		list( $f, $t ) = Insights::range( $days );
		list( $pf, $pt ) = Insights::range( $days, 1 );
		$s             = Insights::summary( $f, $t );
		$p             = Insights::summary( $pf, $pt );
		$consent       = Insights::consent_stats( $f, $t );
		$clicks        = Insights::click_totals( $f, $t );
		$pclicks       = Insights::click_totals( $pf, $pt );
		$contact       = 0;
		$pcontact      = 0;
		foreach ( array( 'whatsapp', 'phone', 'email', 'whop' ) as $k ) {
			$contact  += isset( $clicks[ $k ] ) ? $clicks[ $k ] : 0;
			$pcontact += isset( $pclicks[ $k ] ) ? $pclicks[ $k ] : 0;
		}

		self::header( __( 'Visitor insights', 'a2e-consent' ), self::SLUG );
		echo '<div class="a2ec-top">';
		echo '<p class="a2ec-lead">';
		if ( $consent['total'] ) {
			/* translators: %d: percent */
			echo esc_html( sprintf( __( 'These numbers count visitors who allowed analytics cookies: %d%% of the cookie choices made in this period. Everyone else is invisible here by design.', 'a2e-consent' ), $consent['analytics_pct'] ) );
		} else {
			esc_html_e( 'These numbers count visitors who allowed analytics cookies. Everyone else is invisible here by design.', 'a2e-consent' );
		}
		echo '</p><div class="a2ec-range">';
		foreach ( array( 1 => __( 'Today', 'a2e-consent' ), 7 => __( '7 days', 'a2e-consent' ), 30 => __( '30 days', 'a2e-consent' ), 90 => __( '90 days', 'a2e-consent' ), 365 => __( '12 months', 'a2e-consent' ) ) as $d => $label ) {
			printf( '<a href="%s" class="%s">%s</a>', esc_url( add_query_arg( array( 'page' => self::SLUG, 'days' => $d ), admin_url( 'admin.php' ) ) ), $d === $days ? 'is-active' : '', esc_html( $label ) );
		}
		echo '</div></div>';

		if ( is_excluded_staff() ) {
			echo '<p class="a2ec-hint">' . esc_html__( 'Your own visits are not counted while you are signed in. To test, open the site in a private window and accept analytics.', 'a2e-consent' ) . '</p>';
		}
		if ( '1' !== opt( 'insights' ) ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Visitor insights are switched off in Settings, so no new visits are recorded.', 'a2e-consent' ) . '</p></div>';
		}

		$tiles = array(
			array( __( 'Visitors', 'a2e-consent' ), number_format_i18n( $s['visitors'] ), self::delta( $s['visitors'], $p['visitors'] ) ),
			array( __( 'New visitors', 'a2e-consent' ), number_format_i18n( $s['new'] ), self::delta( $s['new'], $p['new'] ) ),
			array( __( 'Visits', 'a2e-consent' ), number_format_i18n( $s['visits'] ), self::delta( $s['visits'], $p['visits'] ) ),
			array( __( 'Page views', 'a2e-consent' ), number_format_i18n( $s['views'] ), self::delta( $s['views'], $p['views'] ) ),
			array( __( 'Time on a page', 'a2e-consent' ), duration( $s['avg_time'] ), self::delta( $s['avg_time'], $p['avg_time'] ) ),
			array( __( 'Leads (forms sent)', 'a2e-consent' ), number_format_i18n( $s['leads'] ), self::delta( $s['leads'], $p['leads'] ) ),
			array( __( 'Lead rate', 'a2e-consent' ), $s['lead_rate'] . '%', self::delta( $s['lead_rate'], $p['lead_rate'] ) ),
			array( __( 'WhatsApp, call, email and Whop clicks', 'a2e-consent' ), number_format_i18n( $contact ), self::delta( $contact, $pcontact ) ),
		);
		echo '<div class="a2ec-tiles">';
		foreach ( $tiles as $tile ) {
			echo '<div class="a2ec-tile"><span>' . esc_html( $tile[0] ) . '</span><strong>' . esc_html( $tile[1] ) . '</strong>' . $tile[2] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<div class="a2ec-tile a2ec-tile--live"><span>' . esc_html__( 'On the site now', 'a2e-consent' ) . '</span><strong>' . esc_html( number_format_i18n( Insights::live() ) ) . '</strong><span class="a2ec-live-dot" aria-hidden="true"></span></div>';
		echo '</div>';

		self::chart( Insights::daily( $f, $t ), $days );

		echo '<div class="a2ec-panels">';

		// Channels.
		$channels = array();
		foreach ( Insights::sources( $f, $t, 'medium', 10 ) as $r ) {
			/* translators: %d: leads */
			$channels[] = array( medium_label( $r['medium'] ), $r['visitors'], $r['leads'] ? sprintf( _n( '%d lead', '%d leads', (int) $r['leads'], 'a2e-consent' ), (int) $r['leads'] ) : '' );
		}
		echo '<section class="a2ec-panel"><h2>' . esc_html__( 'How visitors find you', 'a2e-consent' ) . '</h2><p class="a2ec-sub">' . esc_html__( 'Visitors per channel, with the leads each one brought.', 'a2e-consent' ) . '</p>';
		self::bars( $channels, __( 'No visits recorded yet.', 'a2e-consent' ) );
		echo '</section>';

		// Sources table.
		$sources = Insights::sources( $f, $t, 'source', 12 );
		echo '<section class="a2ec-panel a2ec-panel--wide"><h2>' . esc_html__( 'Top sources', 'a2e-consent' ) . '</h2><p class="a2ec-sub">' . esc_html__( 'Search engines, social networks, AI assistants, adverts and websites that sent visitors.', 'a2e-consent' ) . '</p>';
		if ( $sources ) {
			echo '<table class="widefat striped a2ec-table"><thead><tr><th>' . esc_html__( 'Source', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Channel', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Visitors', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Visits', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Leads', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Lead rate', 'a2e-consent' ) . '</th></tr></thead><tbody>';
			foreach ( $sources as $r ) {
				$rate = $r['visitors'] ? round( $r['leads'] / $r['visitors'] * 100, 1 ) : 0;
				printf( '<tr><td><strong>%s</strong></td><td>%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s%%</td></tr>', esc_html( $r['source'] ), esc_html( medium_label( $r['medium'] ) ), esc_html( number_format_i18n( $r['visitors'] ) ), esc_html( number_format_i18n( $r['visits'] ) ), esc_html( number_format_i18n( $r['leads'] ) ), esc_html( (string) $rate ) );
			}
			echo '</tbody></table>';
		} else {
			echo '<p class="a2ec-empty">' . esc_html__( 'No visits recorded yet.', 'a2e-consent' ) . '</p>';
		}
		echo '</section>';

		// Pages.
		$pages = Insights::pages( $f, $t, 12 );
		echo '<section class="a2ec-panel a2ec-panel--wide"><h2>' . esc_html__( 'Most read pages', 'a2e-consent' ) . '</h2>';
		if ( $pages ) {
			echo '<table class="widefat striped a2ec-table"><thead><tr><th>' . esc_html__( 'Page', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Views', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Visitors', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Time on page', 'a2e-consent' ) . '</th></tr></thead><tbody>';
			foreach ( $pages as $r ) {
				$avg = $r['timed'] ? $r['seconds'] / $r['timed'] : 0;
				printf( '<tr><td><a href="%s" target="_blank" rel="noopener">%s</a><br><code>%s</code></td><td class="num">%s</td><td class="num">%s</td><td class="num">%s</td></tr>', esc_url( home_url( $r['path'] ) ), esc_html( $r['title'] ? $r['title'] : $r['path'] ), esc_html( $r['path'] ), esc_html( number_format_i18n( $r['views'] ) ), esc_html( number_format_i18n( $r['visitors'] ) ), esc_html( duration( $avg ) ) );
			}
			echo '</tbody></table>';
		} else {
			echo '<p class="a2ec-empty">' . esc_html__( 'No page views recorded yet.', 'a2e-consent' ) . '</p>';
		}
		echo '</section>';

		// Landing pages.
		$land = array();
		foreach ( Insights::landings( $f, $t, 8 ) as $r ) {
			$land[] = array( $r['title'] ? $r['title'] : $r['path'], $r['visits'] );
		}
		echo '<section class="a2ec-panel"><h2>' . esc_html__( 'Where visits start', 'a2e-consent' ) . '</h2><p class="a2ec-sub">' . esc_html__( 'The first page people see.', 'a2e-consent' ) . '</p>';
		self::bars( $land, __( 'No visits recorded yet.', 'a2e-consent' ) );
		echo '</section>';

		// Clicks.
		$crow = array();
		foreach ( Insights::clicks( $f, $t, 10 ) as $r ) {
			$target = preg_replace( '#^(https?://|mailto:|tel:)#', '', (string) $r['target'] );
			$crow[] = array( self::click_label( $r['type'] ) . ( $r['label'] ? ': ' . $r['label'] : '' ), $r['clicks'], substr( $target, 0, 48 ) );
		}
		echo '<section class="a2ec-panel"><h2>' . esc_html__( 'Clicks', 'a2e-consent' ) . '</h2><p class="a2ec-sub">' . esc_html__( 'WhatsApp, Whop checkout, phone, email and links to other websites.', 'a2e-consent' ) . '</p>';
		self::bars( $crow, __( 'No clicks recorded yet.', 'a2e-consent' ) );
		echo '</section>';

		// Campaigns.
		$camps = Insights::sources( $f, $t, 'campaign', 8 );
		if ( $camps ) {
			$rows = array();
			foreach ( $camps as $r ) {
				/* translators: %d: leads */
				$rows[] = array( $r['campaign'] . ' · ' . $r['source'], $r['visitors'], $r['leads'] ? sprintf( _n( '%d lead', '%d leads', (int) $r['leads'], 'a2e-consent' ), (int) $r['leads'] ) : '' );
			}
			echo '<section class="a2ec-panel"><h2>' . esc_html__( 'Campaigns', 'a2e-consent' ) . '</h2><p class="a2ec-sub">' . esc_html__( 'From utm_campaign in your links.', 'a2e-consent' ) . '</p>';
			self::bars( $rows, '' );
			echo '</section>';
		}

		// Devices and countries.
		$dev = array();
		foreach ( Insights::split( $f, $t, 'device' ) as $r ) {
			$dev[] = array( ucfirst( $r['name'] ), $r['visitors'] );
		}
		echo '<section class="a2ec-panel"><h2>' . esc_html__( 'Devices', 'a2e-consent' ) . '</h2>';
		self::bars( $dev, __( 'No visits recorded yet.', 'a2e-consent' ) );
		$cty = array();
		foreach ( Insights::split( $f, $t, 'country' ) as $r ) {
			$cty[] = array( self::country_name( $r['name'] ), $r['visitors'] );
		}
		echo '<h2 class="a2ec-h2-gap">' . esc_html__( 'Countries', 'a2e-consent' ) . '</h2>';
		self::bars( $cty, __( 'Your host does not tell WordPress the visitor\'s country. Cloudflare (free plan) adds it automatically.', 'a2e-consent' ) );
		echo '</section>';

		// Recent leads.
		$leads = Insights::leads( $f, $t, 8 );
		echo '<section class="a2ec-panel a2ec-panel--wide"><h2>' . esc_html__( 'Recent leads and how they found you', 'a2e-consent' ) . '</h2>';
		if ( $leads ) {
			echo '<table class="widefat striped a2ec-table"><thead><tr><th>' . esc_html__( 'When', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Who', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Form', 'a2e-consent' ) . '</th><th>' . esc_html__( 'First found you', 'a2e-consent' ) . '</th><th></th></tr></thead><tbody>';
			foreach ( $leads as $r ) {
				$info  = self::lead_info( (int) $r['lead_id'] );
				$j     = $r['lead_id'] ? get_post_meta( (int) $r['lead_id'], '_a2ec_journey', true ) : array();
				$first = is_array( $j ) && ! empty( $j['first_source'] ) ? Leads::source_text( $j['first_source'], $j['first_medium'] ) : Leads::source_text( $r['source'], $r['medium'] );
				printf(
					'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td><td><a href="%5$s">%6$s</a></td></tr>',
					esc_html( local_time( $r['created'], 'j M, H:i' ) ),
					$info ? '<a href="' . esc_url( $info['link'] ) . '">' . esc_html( $info['name'] ) . '</a>' : '<span class="a2ec-muted">' . esc_html__( 'Not linked', 'a2e-consent' ) . '</span>',
					esc_html( $r['title'] ),
					esc_html( $first ),
					esc_url( admin_url( 'admin.php?page=a2ec-visitors&visitor=' . rawurlencode( $r['visitor'] ) ) ),
					esc_html__( 'Journey', 'a2e-consent' )
				);
			}
			echo '</tbody></table>';
		} else {
			echo '<p class="a2ec-empty">' . esc_html__( 'No forms sent by visitors who allowed analytics in this period.', 'a2e-consent' ) . '</p>';
		}
		echo '</section>';

		// Consent.
		echo '<section class="a2ec-panel"><h2>' . esc_html__( 'Cookie choices', 'a2e-consent' ) . '</h2>';
		if ( $consent['total'] ) {
			/* translators: %s: number of choices */
			echo '<p class="a2ec-sub">' . esc_html( sprintf( _n( '%s choice made in this period.', '%s choices made in this period.', $consent['total'], 'a2e-consent' ), number_format_i18n( $consent['total'] ) ) ) . '</p>';
			self::bars(
				array(
					array( __( 'Accepted all', 'a2e-consent' ), $consent['accept_all'], $consent['accept_pct'] . '%' ),
					array( __( 'Rejected all', 'a2e-consent' ), $consent['reject_all'], $consent['reject_pct'] . '%' ),
					array( __( 'Chose their own', 'a2e-consent' ), $consent['custom'], $consent['custom_pct'] . '%' ),
					array( __( 'Allow analytics', 'a2e-consent' ), $consent['analytics'], $consent['analytics_pct'] . '%' ),
					array( __( 'Allow marketing', 'a2e-consent' ), $consent['marketing'], $consent['marketing_pct'] . '%' ),
				),
				''
			);
		} else {
			echo '<p class="a2ec-empty">' . esc_html__( 'No cookie choices recorded yet.', 'a2e-consent' ) . '</p>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=a2ec-log' ) ) . '">' . esc_html__( 'Open the consent log', 'a2e-consent' ) . ' →</a></p></section>';

		echo '</div></div>';
	}

	/**
	 * Country name from its code.
	 *
	 * @param string $cc Code.
	 * @return string
	 */
	protected static function country_name( $cc ) {
		$names = array(
			'GB' => 'United Kingdom', 'US' => 'United States', 'PK' => 'Pakistan', 'IN' => 'India', 'CA' => 'Canada', 'AU' => 'Australia', 'DE' => 'Germany',
			'FR' => 'France', 'IT' => 'Italy', 'ES' => 'Spain', 'NL' => 'Netherlands', 'IE' => 'Ireland', 'AE' => 'United Arab Emirates', 'SA' => 'Saudi Arabia',
			'BD' => 'Bangladesh', 'PH' => 'Philippines', 'NG' => 'Nigeria', 'ZA' => 'South Africa', 'NZ' => 'New Zealand', 'SG' => 'Singapore', 'MY' => 'Malaysia',
			'TR' => 'Türkiye', 'EG' => 'Egypt', 'BR' => 'Brazil', 'MX' => 'Mexico', 'PL' => 'Poland', 'SE' => 'Sweden', 'CN' => 'China', 'HK' => 'Hong Kong',
		);
		return isset( $names[ $cc ] ) ? $names[ $cc ] . ' (' . $cc . ')' : $cc;
	}

	/**
	 * Daily chart (bars = visitors, dots = leads).
	 *
	 * @param array $daily Days.
	 * @param int   $days  Range.
	 */
	protected static function chart( $daily, $days ) {
		if ( $days < 7 ) {
			return;
		}
		if ( $days > 90 ) {
			// Group into weeks for the 12-month view.
			$weeks = array();
			$i     = 0;
			foreach ( $daily as $day => $v ) {
				$key = (int) floor( $i++ / 7 );
				if ( ! isset( $weeks[ $key ] ) ) {
					$weeks[ $key ] = array( $day, array( 0, 0, 0 ) );
				}
				$weeks[ $key ][1][0] += $v[0];
				$weeks[ $key ][1][1] += $v[1];
				$weeks[ $key ][1][2] += $v[2];
			}
			$daily = array();
			foreach ( $weeks as $w ) {
				$daily[ $w[0] ] = $w[1];
			}
		}
		$max   = max( 1, max( array_map( function ( $v ) {
			return $v[0];
		}, $daily ) ) );
		$n     = count( $daily );
		$every = max( 1, (int) ceil( $n / 8 ) );
		$total = array_sum( array_map( function ( $v ) {
			return $v[0];
		}, $daily ) );
		echo '<section class="a2ec-chart" aria-label="' . esc_attr__( 'Visitors per day', 'a2e-consent' ) . '">';
		echo '<div class="a2ec-chart__head"><h2>' . esc_html( $days > 90 ? __( 'Visitors per week', 'a2e-consent' ) : __( 'Visitors per day', 'a2e-consent' ) ) . '</h2><span class="a2ec-legend"><i class="is-bar"></i>' . esc_html__( 'Visitors', 'a2e-consent' ) . ' <i class="is-dot"></i>' . esc_html__( 'Leads', 'a2e-consent' ) . '</span></div>';
		if ( ! $total ) {
			echo '<p class="a2ec-empty">' . esc_html__( 'Nothing recorded in this period yet. Visits appear here a few seconds after someone who allowed analytics opens a page.', 'a2e-consent' ) . '</p></section>';
			return;
		}
		echo '<div class="a2ec-chart__plot" style="--n:' . (int) $n . '">';
		$i = 0;
		foreach ( $daily as $day => $v ) {
			$label = wp_date( 'j M', strtotime( $day . ' 12:00:00' ) );
			/* translators: 1: date, 2: visitors, 3: page views, 4: leads */
			$tip = sprintf( __( '%1$s: %2$d visitors, %3$d page views, %4$d leads', 'a2e-consent' ), $label, $v[0], $v[1], $v[2] );
			printf(
				'<div class="a2ec-chart__col" title="%1$s"><span class="a2ec-chart__bar" style="height:%2$s%%"></span>%3$s<span class="a2ec-chart__x">%4$s</span></div>',
				esc_attr( $tip ),
				esc_attr( (string) ( $v[0] ? max( 2, round( $v[0] / $max * 100 ) ) : 0 ) ),
				$v[2] ? '<span class="a2ec-chart__lead" style="bottom:' . esc_attr( (string) min( 100, round( $v[0] / $max * 100 ) ) ) . '%">' . (int) $v[2] . '</span>' : '',
				0 === $i % $every ? esc_html( $label ) : ''
			);
			$i++;
		}
		echo '</div></section>';
	}

	/* Visitors ------------------------------------------------------------------------ */

	/**
	 * Visitors list or one visitor's journey.
	 */
	public static function render_visitors() {
		// phpcs:disable WordPress.Security.NonceVerification
		$visitor = isset( $_GET['visitor'] ) ? sanitize_key( wp_unslash( $_GET['visitor'] ) ) : '';
		if ( $visitor ) {
			self::render_journey( $visitor );
			return;
		}
		$leads_only = ! empty( $_GET['leads'] );
		$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable

		$args = array(
			'page'       => $page,
			'per_page'   => 25,
			'leads_only' => $leads_only,
		);
		if ( '' !== $search ) {
			if ( preg_match( '/^[a-f0-9]{16}$/', $search ) ) {
				$args['visitors'] = array( $search );
			} elseif ( is_email( $search ) ) {
				$args['visitors'] = Insights::by_email( $search )['visitors'];
				$args['visitors'] = $args['visitors'] ? $args['visitors'] : array( 'none' );
			} else {
				$args['visitors'] = array( 'none' );
			}
		}
		$res = Insights::visitors( $args );

		self::header( __( 'Visitors', 'a2e-consent' ), 'a2ec-visitors' );
		echo '<p class="a2ec-lead">' . esc_html__( 'Everyone who allowed analytics cookies, newest activity first. Visitors are anonymous (a random ID) until they send you a form; then you see their name and the journey that led to it.', 'a2e-consent' ) . '</p>';
		echo '<form method="get" class="a2ec-filter"><input type="hidden" name="page" value="a2ec-visitors">';
		printf( '<a class="button%s" href="%s">%s</a> ', $leads_only ? '' : ' button-primary', esc_url( admin_url( 'admin.php?page=a2ec-visitors' ) ), esc_html__( 'All visitors', 'a2e-consent' ) );
		printf( '<a class="button%s" href="%s">%s</a>', $leads_only ? ' button-primary' : '', esc_url( admin_url( 'admin.php?page=a2ec-visitors&leads=1' ) ), esc_html__( 'Became leads', 'a2e-consent' ) );
		printf( '<span class="a2ec-search"><input type="search" name="s" value="%s" placeholder="%s"> <button class="button">%s</button></span>', esc_attr( $search ), esc_attr__( 'Email or visitor ID', 'a2e-consent' ), esc_html__( 'Find', 'a2e-consent' ) );
		echo '</form>';

		if ( ! $res['rows'] ) {
			echo '<div class="a2ec-panel"><p class="a2ec-empty">' . esc_html( '' !== $search ? __( 'No visitor found. Only people who allowed analytics and then sent a form can be found by email.', 'a2e-consent' ) : __( 'No visitors recorded yet. They appear here as soon as someone allows analytics cookies on your site.', 'a2e-consent' ) ) . '</p></div></div>';
			return;
		}
		echo '<div class="a2ec-table-wrap"><table class="widefat striped a2ec-table a2ec-visitors"><thead><tr><th>' . esc_html__( 'Visitor', 'a2e-consent' ) . '</th><th>' . esc_html__( 'First found you', 'a2e-consent' ) . '</th><th>' . esc_html__( 'First page', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Visits', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Pages', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Time', 'a2e-consent' ) . '</th><th class="num">' . esc_html__( 'Clicks', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Device', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Last seen', 'a2e-consent' ) . '</th></tr></thead><tbody>';
		foreach ( $res['rows'] as $r ) {
			$info = self::lead_info( (int) $r['lead_id'] );
			$link = admin_url( 'admin.php?page=a2ec-visitors&visitor=' . rawurlencode( $r['visitor'] ) );
			if ( $info ) {
				$who = '<a href="' . esc_url( $link ) . '"><strong>' . esc_html( $info['name'] ) . '</strong></a><br><span class="a2ec-muted">' . esc_html( $info['email'] ) . '</span> <span class="a2ec-pill is-lead">' . esc_html__( 'Lead', 'a2e-consent' ) . '</span>';
			} else {
				$who = '<a href="' . esc_url( $link ) . '"><strong>' . esc_html__( 'Anonymous', 'a2e-consent' ) . '</strong></a> <code>' . esc_html( substr( $r['visitor'], 0, 8 ) ) . '</code>' . ( (int) $r['leads'] ? ' <span class="a2ec-pill is-lead">' . esc_html__( 'Lead', 'a2e-consent' ) . '</span>' : '' );
			}
			printf(
				'<tr><td>%1$s</td><td>%2$s</td><td><code>%3$s</code></td><td class="num">%4$s</td><td class="num">%5$s</td><td class="num">%6$s</td><td class="num">%7$s</td><td>%8$s</td><td>%9$s</td></tr>',
				$who, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( Leads::source_text( $r['first']['source'], $r['first']['medium'] ) . ( $r['first']['campaign'] ? ' · ' . $r['first']['campaign'] : '' ) ),
				esc_html( $r['first']['path'] ),
				esc_html( number_format_i18n( $r['visits'] ) ),
				esc_html( number_format_i18n( $r['views'] ) ),
				esc_html( duration( $r['seconds'] ) ),
				esc_html( number_format_i18n( $r['clicks'] ) ),
				esc_html( ucfirst( $r['first']['device'] ) . ( $r['first']['country'] ? ' · ' . $r['first']['country'] : '' ) ),
				esc_html( human_time_diff( strtotime( $r['last_seen'] . ' UTC' ) ) . ' ' . __( 'ago', 'a2e-consent' ) )
			);
		}
		echo '</tbody></table></div>';
		self::pager( $res['total'], 25, $page );
		echo '</div>';
	}

	/**
	 * Pagination.
	 *
	 * @param int $total Total.
	 * @param int $per   Per page.
	 * @param int $page  Page.
	 */
	protected static function pager( $total, $per, $page ) {
		$pages = (int) ceil( $total / $per );
		if ( $pages < 2 ) {
			return;
		}
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $page,
				'total'   => $pages,
			)
		);
		echo '</div></div>';
	}

	/**
	 * One visitor's journey.
	 *
	 * @param string $visitor Visitor ID.
	 */
	protected static function render_journey( $visitor ) {
		$visits = Insights::journey( $visitor );
		self::header( __( 'Visitor journey', 'a2e-consent' ), 'a2ec-visitors' );
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=a2ec-visitors' ) ) . '">← ' . esc_html__( 'All visitors', 'a2e-consent' ) . '</a></p>';
		if ( ! $visits ) {
			echo '<div class="a2ec-panel"><p class="a2ec-empty">' . esc_html__( 'Nothing recorded for this visitor (it may have been deleted or cleaned up after the time you keep data for).', 'a2e-consent' ) . '</p></div></div>';
			return;
		}
		$lead_id = 0;
		$views   = 0;
		$seconds = 0;
		foreach ( $visits as $v ) {
			foreach ( $v['steps'] as $s ) {
				if ( 'lead' === $s['type'] && $s['lead_id'] ) {
					$lead_id = (int) $s['lead_id'];
				}
				if ( 'view' === $s['type'] ) {
					$views++;
					$seconds += (int) $s['seconds'];
				}
			}
		}
		$info  = self::lead_info( $lead_id );
		$first = $visits[0];
		$last  = end( $visits );

		echo '<div class="a2ec-person">';
		echo '<div class="a2ec-person__id"><span class="a2ec-avatar" aria-hidden="true">' . esc_html( $info ? strtoupper( substr( $info['name'], 0, 1 ) ) : '?' ) . '</span><div>';
		if ( $info ) {
			echo '<h2>' . esc_html( $info['name'] ) . '</h2><p><a href="mailto:' . esc_attr( $info['email'] ) . '">' . esc_html( $info['email'] ) . '</a> · <a href="' . esc_url( $info['link'] ) . '">' . esc_html__( 'Open the lead', 'a2e-consent' ) . '</a></p>';
		} else {
			echo '<h2>' . esc_html__( 'Anonymous visitor', 'a2e-consent' ) . '</h2><p class="a2ec-muted">' . esc_html__( 'ID', 'a2e-consent' ) . ' <code>' . esc_html( $visitor ) . '</code></p>';
		}
		echo '</div></div><dl class="a2ec-facts">';
		$facts = array(
			__( 'First found you', 'a2e-consent' ) => Leads::source_text( $first['source'], $first['medium'] ) . ( $first['campaign'] ? ' · ' . $first['campaign'] : '' ),
			__( 'First visit', 'a2e-consent' )     => local_time( $first['start'] ),
			__( 'Last seen', 'a2e-consent' )       => local_time( $last['end'] ),
			__( 'Visits', 'a2e-consent' )          => number_format_i18n( count( $visits ) ),
			__( 'Pages', 'a2e-consent' )           => number_format_i18n( $views ),
			__( 'Time on site', 'a2e-consent' )    => duration( $seconds ),
		);
		foreach ( $facts as $k => $v ) {
			echo '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
		}
		echo '</dl></div>';

		echo '<ol class="a2ec-timeline">';
		foreach ( array_reverse( $visits, true ) as $n => $v ) {
			$where = Leads::source_text( $v['source'], $v['medium'] ) . ( $v['campaign'] ? ' · ' . $v['campaign'] : '' ) . ( $v['ref_host'] && $v['ref_host'] !== $v['source'] ? ' · ' . $v['ref_host'] : '' );
			echo '<li class="a2ec-visit"><div class="a2ec-visit__head"><strong>' . esc_html( sprintf( /* translators: %d: visit number */ __( 'Visit %d', 'a2e-consent' ), $n + 1 ) ) . '</strong> <span>' . esc_html( local_time( $v['start'] ) ) . '</span> <span class="a2ec-pill">' . esc_html( $where ) . '</span> <span class="a2ec-muted">' . esc_html( ucfirst( $v['device'] ) . ( $v['country'] ? ' · ' . $v['country'] : '' ) ) . '</span></div><ul class="a2ec-steps">';
			foreach ( $v['steps'] as $s ) {
				$time = local_time( $s['created'], get_option( 'time_format' ) );
				if ( 'view' === $s['type'] ) {
					printf( '<li class="is-view"><span class="a2ec-steps__t">%1$s</span> <a href="%2$s" target="_blank" rel="noopener">%3$s</a> <code>%4$s</code>%5$s</li>', esc_html( $time ), esc_url( home_url( $s['path'] ) ), esc_html( $s['title'] ? $s['title'] : $s['path'] ), esc_html( $s['path'] ), $s['seconds'] ? ' <span class="a2ec-muted">· ' . esc_html( duration( $s['seconds'] ) ) . '</span>' : '' );
				} elseif ( 'lead' === $s['type'] ) {
					printf( '<li class="is-lead"><span class="a2ec-steps__t">%1$s</span> <strong>%2$s</strong> %3$s</li>', esc_html( $time ), esc_html__( 'Sent a form:', 'a2e-consent' ), esc_html( $s['title'] ) );
				} else {
					printf( '<li class="is-click"><span class="a2ec-steps__t">%1$s</span> %2$s %3$s <span class="a2ec-muted">%4$s</span></li>', esc_html( $time ), esc_html( sprintf( /* translators: %s: link type */ __( 'Clicked %s', 'a2e-consent' ), self::click_label( $s['type'] ) ) ), $s['title'] ? '"' . esc_html( $s['title'] ) . '"' : '', esc_html( preg_replace( '#^(https?://|mailto:|tel:)#', '', $s['target'] ) ) );
				}
			}
			echo '</ul></li>';
		}
		echo '</ol>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="a2ec-forget" onsubmit="return confirm(\'' . esc_js( __( 'Delete everything recorded about this visitor? This cannot be undone.', 'a2e-consent' ) ) . '\');">';
		wp_nonce_field( 'a2ec_forget' );
		echo '<input type="hidden" name="action" value="a2ec_forget"><input type="hidden" name="visitor" value="' . esc_attr( $visitor ) . '"><button class="button button-link-delete">' . esc_html__( 'Delete this visitor\'s data', 'a2e-consent' ) . '</button></form>';
		echo '</div>';
	}

	/**
	 * Delete one visitor.
	 */
	public static function forget() {
		self::guard( 'a2ec_forget' );
		$visitor = isset( $_POST['visitor'] ) ? sanitize_key( wp_unslash( $_POST['visitor'] ) ) : '';
		$n       = Insights::forget( $visitor );
		/* translators: %d: rows */
		self::back( sprintf( __( 'Deleted %d records for that visitor.', 'a2e-consent' ), $n ), 'a2ec-visitors' );
	}

	/* Consent log ----------------------------------------------------------------------- */

	/**
	 * Choice label.
	 *
	 * @param string $choice Choice.
	 * @return string
	 */
	protected static function choice_label( $choice ) {
		$labels = array(
			'accept_all' => __( 'Accepted all', 'a2e-consent' ),
			'reject_all' => __( 'Rejected all', 'a2e-consent' ),
			'custom'     => __( 'Chose their own', 'a2e-consent' ),
		);
		return isset( $labels[ $choice ] ) ? $labels[ $choice ] : $choice;
	}

	/**
	 * Consent log.
	 */
	public static function render_log() {
		global $wpdb;
		// phpcs:disable WordPress.Security.NonceVerification
		$search = isset( $_GET['s'] ) ? sanitize_key( wp_unslash( $_GET['s'] ) ) : '';
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable
		$per   = 50;
		$t     = table( 'log' );
		$where = $search ? $wpdb->prepare( 'WHERE consent_id LIKE %s', $wpdb->esc_like( $search ) . '%' ) : '';
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} {$where}" ); // phpcs:ignore WordPress.DB
		$rows  = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} {$where} ORDER BY id DESC LIMIT %d OFFSET %d", $per, ( $page - 1 ) * $per ), ARRAY_A ); // phpcs:ignore WordPress.DB

		list( $f, $to ) = Insights::range( 30 );
		$st             = Insights::consent_stats( $f, $to );

		self::header( __( 'Consent log', 'a2e-consent' ), 'a2ec-log' );
		echo '<p class="a2ec-lead">' . esc_html__( 'Proof of consent: every choice made in the cookie banner, with a random consent ID (visitors can see theirs in Cookie settings), what they chose, the banner version and page. No IP addresses or names are stored.', 'a2e-consent' ) . '</p>';
		echo '<div class="a2ec-tiles a2ec-tiles--small">';
		$tiles = array(
			array( __( 'Choices (30 days)', 'a2e-consent' ), number_format_i18n( $st['total'] ) ),
			array( __( 'Accepted all', 'a2e-consent' ), $st['accept_pct'] . '%' ),
			array( __( 'Rejected all', 'a2e-consent' ), $st['reject_pct'] . '%' ),
			array( __( 'Chose their own', 'a2e-consent' ), $st['custom_pct'] . '%' ),
			array( __( 'Allow analytics', 'a2e-consent' ), $st['analytics_pct'] . '%' ),
			array( __( 'Allow marketing', 'a2e-consent' ), $st['marketing_pct'] . '%' ),
		);
		foreach ( $tiles as $tile ) {
			echo '<div class="a2ec-tile"><span>' . esc_html( $tile[0] ) . '</span><strong>' . esc_html( $tile[1] ) . '</strong></div>';
		}
		echo '</div>';

		echo '<div class="a2ec-filter"><form method="get"><input type="hidden" name="page" value="a2ec-log"><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Consent ID', 'a2e-consent' ) . '"> <button class="button">' . esc_html__( 'Find', 'a2e-consent' ) . '</button></form>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'a2ec_export' );
		echo '<input type="hidden" name="action" value="a2ec_export"><button class="button">' . esc_html__( 'Download as CSV', 'a2e-consent' ) . '</button></form></div>';

		if ( ! $rows ) {
			echo '<div class="a2ec-panel"><p class="a2ec-empty">' . esc_html__( 'No choices recorded yet.', 'a2e-consent' ) . '</p></div></div>';
			return;
		}
		$yes = '<span class="a2ec-yes">' . esc_html__( 'Yes', 'a2e-consent' ) . '</span>';
		$no  = '<span class="a2ec-no">' . esc_html__( 'No', 'a2e-consent' ) . '</span>';
		echo '<div class="a2ec-table-wrap"><table class="widefat striped a2ec-table"><thead><tr><th>' . esc_html__( 'Date', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Consent ID', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Choice', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Analytics', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Marketing', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Version', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Page', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Browser', 'a2e-consent' ) . '</th><th>' . esc_html__( 'Country', 'a2e-consent' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			printf(
				'<tr><td>%1$s</td><td><code>%2$s</code></td><td>%3$s%4$s</td><td>%5$s</td><td>%6$s</td><td>%7$d</td><td><code>%8$s</code></td><td>%9$s</td><td>%10$s</td></tr>',
				esc_html( local_time( $r['created'] ) ),
				esc_html( $r['consent_id'] ),
				esc_html( self::choice_label( $r['choice'] ) ),
				$r['gpc'] ? ' <span class="a2ec-pill" title="' . esc_attr__( 'The browser sent a Global Privacy Control signal', 'a2e-consent' ) . '">GPC</span>' : '',
				$r['analytics'] ? $yes : $no, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$r['marketing'] ? $yes : $no, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				(int) $r['version'],
				esc_html( $r['path'] ),
				esc_html( $r['browser'] ),
				esc_html( $r['country'] )
			);
		}
		echo '</tbody></table></div>';
		self::pager( $total, $per, $page );
		echo '</div>';
	}

	/**
	 * Download the consent log.
	 */
	public static function export() {
		global $wpdb;
		self::guard( 'a2ec_export' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consent-log-' . wp_date( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $out, array( 'date_utc', 'consent_id', 'choice', 'analytics', 'marketing', 'banner_version', 'gpc', 'page', 'browser', 'country' ) );
		$t    = table( 'log' );
		$last = PHP_INT_MAX;
		do {
			$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE id < %d ORDER BY id DESC LIMIT 2000", $last ), ARRAY_A ); // phpcs:ignore WordPress.DB
			foreach ( $rows as $r ) {
				$last = (int) $r['id'];
				fputcsv( $out, array( $r['created'], $r['consent_id'], $r['choice'], $r['analytics'] ? 'yes' : 'no', $r['marketing'] ? 'yes' : 'no', $r['version'], $r['gpc'] ? 'yes' : 'no', $r['path'], $r['browser'], $r['country'] ) );
			}
		} while ( count( $rows ) === 2000 );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/* Settings -------------------------------------------------------------------------- */

	/**
	 * Text field.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $help  Help.
	 * @param array  $attrs Extra attributes.
	 */
	protected static function text( $key, $label, $help = '', $attrs = array() ) {
		$extra = '';
		foreach ( $attrs as $k => $v ) {
			$extra .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
		printf( '<p class="a2ec-field"><label for="a2ec-%1$s">%2$s</label><input type="text" class="regular-text" id="a2ec-%1$s" name="a2ec[%1$s]" value="%3$s"%4$s>%5$s</p>', esc_attr( $key ), esc_html( $label ), esc_attr( (string) opt( $key ) ), $extra, $help ? '<span class="a2ec-help">' . esc_html( $help ) . '</span>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Textarea.
	 *
	 * @param string $key      Key.
	 * @param string $label    Label.
	 * @param string $help     Help.
	 * @param int    $rows     Rows.
	 * @param bool   $code     Code style.
	 * @param bool   $disabled Disabled.
	 */
	protected static function area( $key, $label, $help = '', $rows = 3, $code = false, $disabled = false ) {
		printf( '<p class="a2ec-field"><label for="a2ec-%1$s">%2$s</label><textarea class="large-text%3$s" rows="%4$d" id="a2ec-%1$s" name="a2ec[%1$s]"%5$s>%6$s</textarea>%7$s</p>', esc_attr( $key ), esc_html( $label ), $code ? ' code' : '', (int) $rows, $disabled ? ' disabled' : '', esc_textarea( (string) opt( $key ) ), $help ? '<span class="a2ec-help">' . esc_html( $help ) . '</span>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Checkbox.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $help  Help.
	 */
	protected static function check( $key, $label, $help = '' ) {
		printf( '<p class="a2ec-field"><label class="a2ec-check"><input type="hidden" name="a2ec[%1$s]" value="0"><input type="checkbox" name="a2ec[%1$s]" value="1"%2$s> %3$s</label>%4$s</p>', esc_attr( $key ), checked( '1', opt( $key ), false ), esc_html( $label ), $help ? '<span class="a2ec-help">' . esc_html( $help ) . '</span>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Select.
	 *
	 * @param string $key     Key.
	 * @param string $label   Label.
	 * @param array  $options value => label.
	 * @param string $help    Help.
	 */
	protected static function select( $key, $label, $options, $help = '' ) {
		echo '<p class="a2ec-field"><label for="a2ec-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><select id="a2ec-' . esc_attr( $key ) . '" name="a2ec[' . esc_attr( $key ) . ']">';
		foreach ( $options as $v => $l ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $v ), selected( (string) $v, (string) opt( $key ), false ), esc_html( $l ) );
		}
		echo '</select>' . ( $help ? '<span class="a2ec-help">' . esc_html( $help ) . '</span>' : '' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Months options.
	 *
	 * @param int[] $list Months.
	 * @return array
	 */
	protected static function months( $list ) {
		$out = array();
		foreach ( $list as $m ) {
			/* translators: %d: months */
			$out[ $m ] = sprintf( _n( '%d month', '%d months', $m, 'a2e-consent' ), $m );
		}
		return $out;
	}

	/**
	 * Settings screen.
	 */
	public static function render_settings() {
		$theme_ga = function_exists( 'a2e_opt' ) ? clean_id( a2e_opt( 'ga4_id' ) ) : '';
		$can_code = current_user_can( 'unfiltered_html' );
		self::header( __( 'Cookie banner and tracking settings', 'a2e-consent' ), 'a2ec-settings' );
		?>
		<p class="a2ec-lead"><?php esc_html_e( 'Visitors choose which cookies they allow. Until they say yes, Google Analytics, Tag Manager, the Meta Pixel, Clarity, your own pasted codes and the Insights visitor counter do not run. "Accept all" and "Reject all" are always the same size, as UK and EU rules expect.', 'a2e-consent' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2ec-settings">
			<?php wp_nonce_field( 'a2ec_save' ); ?>
			<input type="hidden" name="action" value="a2ec_save">

			<section class="a2ec-card">
				<h2><?php esc_html_e( 'Cookie banner', 'a2e-consent' ); ?></h2>
				<?php self::check( 'banner_on', __( 'Show the cookie banner and hold back tracking until visitors choose', 'a2e-consent' ), __( 'When off, nothing from this plugin runs on your site and your theme\'s own Google Analytics tag runs for everyone without asking.', 'a2e-consent' ) ); ?>
				<div class="a2ec-two">
					<?php
					self::select(
						'layout',
						__( 'Style', 'a2e-consent' ),
						array(
							'card' => __( 'Card in the bottom-left corner (recommended)', 'a2e-consent' ),
							'bar'  => __( 'Bar across the bottom of the screen', 'a2e-consent' ),
						)
					);
					self::select( 'lifetime', __( 'Ask again after', 'a2e-consent' ), self::months( array( 3, 6, 12 ) ), __( 'How long a choice is remembered. Six months is a common, safe choice.', 'a2e-consent' ) );
					?>
				</div>
				<?php
				self::text( 'title', __( 'Heading', 'a2e-consent' ) );
				self::area( 'message', __( 'Message', 'a2e-consent' ), __( 'A "Privacy policy" link is added after it.', 'a2e-consent' ), 3 );
				?>
				<div class="a2ec-three">
					<?php
					self::text( 'label_accept', __( 'Accept button', 'a2e-consent' ) );
					self::text( 'label_reject', __( 'Reject button', 'a2e-consent' ) );
					self::text( 'label_choose', __( 'Choose button', 'a2e-consent' ) );
					?>
				</div>
				<?php
				self::text( 'privacy_url', __( 'Privacy policy link', 'a2e-consent' ), __( 'Leave empty to use your privacy policy page.', 'a2e-consent' ), array( 'placeholder' => privacy_url() ) );
				self::check( 'footer_link', __( 'Add a "Cookie settings" link to the footer (next to Privacy) so visitors can change their mind', 'a2e-consent' ) );
				self::check( 'float_btn', __( 'Also show a small round cookie button in the bottom-left corner after a choice', 'a2e-consent' ), __( 'Used automatically on themes without the A2E footer.', 'a2e-consent' ) );
				self::check( 'gpc', __( 'Respect Global Privacy Control (browsers that ask sites not to share data keep marketing cookies off)', 'a2e-consent' ) );
				?>
			</section>

			<section class="a2ec-card">
				<h2><?php esc_html_e( 'What each group of cookies does', 'a2e-consent' ); ?></h2>
				<p class="a2ec-muted"><?php esc_html_e( 'Shown in the Cookie settings window. The list of cookies under each one is built automatically from the codes you set up below.', 'a2e-consent' ); ?></p>
				<?php
				self::area( 'desc_necessary', __( 'Necessary (always on)', 'a2e-consent' ), '', 2 );
				self::area( 'desc_analytics', __( 'Analytics', 'a2e-consent' ), '', 3 );
				self::area( 'desc_marketing', __( 'Marketing', 'a2e-consent' ), '', 2 );
				?>
			</section>

			<section class="a2ec-card">
				<h2><?php esc_html_e( 'Tracking codes', 'a2e-consent' ); ?></h2>
				<p class="a2ec-muted"><?php esc_html_e( 'Paste only the IDs. Each one loads only for visitors who allow its group. Do not also add these codes in another plugin or in the theme header, or they would run without consent.', 'a2e-consent' ); ?></p>
				<div class="a2ec-two">
					<?php
					self::text(
						'ga4_id',
						__( 'Google Analytics 4 Measurement ID (analytics)', 'a2e-consent' ),
						$theme_ga ? sprintf( /* translators: %s: ID */ __( 'Leave empty to use %s from A2E → Site Settings. Your theme no longer loads it on its own: it now waits for consent.', 'a2e-consent' ), $theme_ga ) : __( 'Looks like G-XXXXXXXXXX.', 'a2e-consent' ),
						array( 'placeholder' => $theme_ga ? $theme_ga : 'G-XXXXXXXXXX' )
					);
					self::text( 'gtm_id', __( 'Google Tag Manager container ID', 'a2e-consent' ), __( 'Looks like GTM-XXXXXXX. Loads after analytics or marketing consent; Google Consent Mode tells each tag what is allowed.', 'a2e-consent' ), array( 'placeholder' => 'GTM-XXXXXXX' ) );
					self::text( 'meta_pixel', __( 'Meta Pixel ID (marketing)', 'a2e-consent' ), __( 'The number from Meta Events Manager. Sends PageView, Lead (when a form is sent), Contact (WhatsApp, call or email clicks) and InitiateCheckout (Whop clicks).', 'a2e-consent' ), array( 'placeholder' => '123456789012345' ) );
					self::text( 'clarity_id', __( 'Microsoft Clarity project ID (analytics)', 'a2e-consent' ), __( 'Heatmaps and session recordings. From Clarity → Settings → Overview.', 'a2e-consent' ), array( 'placeholder' => 'abcd1234ef' ) );
					?>
				</div>
				<fieldset class="a2ec-field">
					<legend><?php esc_html_e( 'Google Consent Mode', 'a2e-consent' ); ?></legend>
					<label class="a2ec-check"><input type="radio" name="a2ec[mode]" value="basic" <?php checked( 'basic', opt( 'mode' ) ); ?>> <?php esc_html_e( 'Basic (recommended): Google tags load only after consent.', 'a2e-consent' ); ?></label>
					<label class="a2ec-check"><input type="radio" name="a2ec[mode]" value="advanced" <?php checked( 'advanced', opt( 'mode' ) ); ?>> <?php esc_html_e( 'Advanced: Google tags load straight away in "denied" mode and send cookieless pings so Google can model the gaps. Ask your adviser before choosing this in the UK or EU.', 'a2e-consent' ); ?></label>
				</fieldset>
				<?php
				$help = $can_code ? __( 'Full <script> snippets for anything else (TikTok, LinkedIn, Pinterest, Hotjar...). They run only after this group is allowed.', 'a2e-consent' ) : __( 'Only an administrator who can post unfiltered HTML can change this.', 'a2e-consent' );
				self::area( 'code_analytics', __( 'Other analytics code', 'a2e-consent' ), $help, 4, true, ! $can_code );
				self::area( 'code_marketing', __( 'Other marketing code', 'a2e-consent' ), $help, 4, true, ! $can_code );
				?>
			</section>

			<section class="a2ec-card">
				<h2><?php esc_html_e( 'Visitor insights', 'a2e-consent' ); ?></h2>
				<?php
				self::check( 'insights', __( 'Record visits from people who allow analytics in your own Insights dashboard', 'a2e-consent' ), __( 'Stored in your WordPress database only. No IP addresses are stored.', 'a2e-consent' ) );
				self::check( 'link_leads', __( 'When they send a form, save how they found you on the lead (source, pages read, visits)', 'a2e-consent' ) );
				self::check( 'exclude_staff', __( 'Do not count visits or load tracking codes for signed-in staff (you, editors, authors)', 'a2e-consent' ) );
				?>
				<div class="a2ec-two">
					<?php
					self::select( 'keep_hits', __( 'Delete visit history after', 'a2e-consent' ), self::months( array( 3, 6, 13, 25 ) ) );
					self::select( 'keep_log', __( 'Delete consent records after', 'a2e-consent' ), self::months( array( 12, 24, 36, 60 ) ), __( 'Keep them as long as you rely on that consent.', 'a2e-consent' ) );
					?>
				</div>
			</section>

			<p class="submit"><button class="button button-primary button-large"><?php esc_html_e( 'Save settings', 'a2e-consent' ); ?></button></p>
		</form>

		<section class="a2ec-card">
			<h2><?php esc_html_e( 'Ask everyone again', 'a2e-consent' ); ?></h2>
			<p class="a2ec-muted">
				<?php
				/* translators: %d: version */
				echo esc_html( sprintf( __( 'Use this after you add a new tracking code or change what you do with the data. Every visitor sees the banner again on their next page. Banner version now: %d.', 'a2e-consent' ), (int) opt( 'version' ) ) );
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Show the cookie banner to every visitor again?', 'a2e-consent' ) ); ?>');">
				<?php wp_nonce_field( 'a2ec_reask' ); ?>
				<input type="hidden" name="action" value="a2ec_reask">
				<button class="button"><?php esc_html_e( 'Ask every visitor again', 'a2e-consent' ); ?></button>
			</form>
		</section>

		<section class="a2ec-card">
			<h2><?php esc_html_e( 'Shortcodes', 'a2e-consent' ); ?></h2>
			<ul class="a2ec-codes">
				<li><code>[a2e_cookie_list]</code> <?php esc_html_e( 'A table of every cookie the site uses, grouped. Add it to your privacy policy page.', 'a2e-consent' ); ?></li>
				<li><code>[a2e_cookie_settings text="Change cookie settings"]</code> <?php esc_html_e( 'A link that opens the Cookie settings window. Any link to #cookie-settings works too.', 'a2e-consent' ); ?></li>
				<li><code>&lt;script type="text/plain" data-a2ec="marketing"&gt;...&lt;/script&gt;</code> <?php esc_html_e( 'Wrap any script in your pages like this and it waits for consent (use analytics or marketing).', 'a2e-consent' ); ?></li>
			</ul>
			<p class="a2ec-muted"><?php esc_html_e( 'Suggested privacy policy wording is in Settings → Privacy → Policy guide.', 'a2e-consent' ); ?></p>
		</section>

		<section class="a2ec-card a2ec-card--danger">
			<h2><?php esc_html_e( 'Delete all visitor data', 'a2e-consent' ); ?></h2>
			<p class="a2ec-muted"><?php esc_html_e( 'Removes every recorded visit, click and lead journey. Consent records are kept.', 'a2e-consent' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Delete all visitor data? This cannot be undone.', 'a2e-consent' ) ); ?>');">
				<?php wp_nonce_field( 'a2ec_wipe' ); ?>
				<input type="hidden" name="action" value="a2ec_wipe">
				<button class="button button-link-delete"><?php esc_html_e( 'Delete all visitor data', 'a2e-consent' ); ?></button>
			</form>
		</section>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	public static function save() {
		self::guard( 'a2ec_save' );
		$in  = isset( $_POST['a2ec'] ) && is_array( $_POST['a2ec'] ) ? wp_unslash( $_POST['a2ec'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cur = settings();
		$new = $cur;
		foreach ( array( 'banner_on', 'footer_link', 'float_btn', 'gpc', 'insights', 'link_leads', 'exclude_staff' ) as $k ) {
			$new[ $k ] = isset( $in[ $k ] ) && '1' === (string) $in[ $k ] ? '1' : '0';
		}
		foreach ( array( 'title', 'label_accept', 'label_reject', 'label_choose' ) as $k ) {
			$v         = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : '';
			$new[ $k ] = '' !== $v ? $v : defaults()[ $k ];
		}
		foreach ( array( 'message', 'desc_necessary', 'desc_analytics', 'desc_marketing' ) as $k ) {
			$v         = isset( $in[ $k ] ) ? sanitize_textarea_field( $in[ $k ] ) : '';
			$new[ $k ] = '' !== trim( $v ) ? $v : defaults()[ $k ];
		}
		$new['privacy_url'] = isset( $in['privacy_url'] ) ? esc_url_raw( trim( $in['privacy_url'] ) ) : '';
		$new['layout']      = isset( $in['layout'] ) && 'bar' === $in['layout'] ? 'bar' : 'card';
		$new['mode']        = isset( $in['mode'] ) && 'advanced' === $in['mode'] ? 'advanced' : 'basic';
		$new['lifetime']    = isset( $in['lifetime'] ) && in_array( (int) $in['lifetime'], array( 3, 6, 12 ), true ) ? (int) $in['lifetime'] : 6;
		$new['keep_hits']   = isset( $in['keep_hits'] ) && in_array( (int) $in['keep_hits'], array( 3, 6, 13, 25 ), true ) ? (int) $in['keep_hits'] : 13;
		$new['keep_log']    = isset( $in['keep_log'] ) && in_array( (int) $in['keep_log'], array( 12, 24, 36, 60 ), true ) ? (int) $in['keep_log'] : 24;
		$new['ga4_id']      = isset( $in['ga4_id'] ) ? clean_id( $in['ga4_id'] ) : '';
		$new['gtm_id']      = isset( $in['gtm_id'] ) ? clean_id( $in['gtm_id'] ) : '';
		$new['meta_pixel']  = isset( $in['meta_pixel'] ) ? preg_replace( '/\D/', '', $in['meta_pixel'] ) : '';
		$new['clarity_id']  = isset( $in['clarity_id'] ) ? preg_replace( '/[^a-z0-9]/i', '', $in['clarity_id'] ) : '';
		if ( current_user_can( 'unfiltered_html' ) ) {
			foreach ( array( 'code_analytics', 'code_marketing' ) as $k ) {
				$new[ $k ] = isset( $in[ $k ] ) ? str_replace( '</template', '&lt;/template', (string) $in[ $k ] ) : '';
			}
		}
		update_settings( $new );
		self::back( __( 'Settings saved. If your site uses a page cache, clear it so visitors get the new settings.', 'a2e-consent' ), 'a2ec-settings' );
	}

	/**
	 * Show the banner to everyone again.
	 */
	public static function reask() {
		self::guard( 'a2ec_reask' );
		$s            = settings();
		$s['version'] = (int) $s['version'] + 1;
		update_settings( $s );
		self::back( __( 'Done. Every visitor will be asked again. Clear your page cache if you use one.', 'a2e-consent' ), 'a2ec-settings' );
	}

	/**
	 * Delete all visitor data.
	 */
	public static function wipe() {
		global $wpdb;
		self::guard( 'a2ec_wipe' );
		$wpdb->query( 'DELETE FROM ' . table( 'hits' ) ); // phpcs:ignore WordPress.DB
		delete_post_meta_by_key( '_a2ec_journey' );
		delete_post_meta_by_key( '_a2ec_vid' );
		self::back( __( 'All visitor data deleted.', 'a2e-consent' ), 'a2ec-settings' );
	}

	/* WordPress dashboard widget -------------------------------------------------------- */

	/**
	 * Register the widget.
	 */
	public static function widget() {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget( 'a2ec_widget', __( 'Visitor insights: last 7 days', 'a2e-consent' ), array( __CLASS__, 'render_widget' ) );
		}
	}

	/**
	 * Widget.
	 */
	public static function render_widget() {
		list( $f, $t ) = Insights::range( 7 );
		$s             = Insights::summary( $f, $t );
		$src           = Insights::sources( $f, $t, 'source', 3 );
		echo '<div class="a2ec-widget"><div class="a2ec-tiles a2ec-tiles--small">';
		foreach ( array( array( __( 'Visitors', 'a2e-consent' ), $s['visitors'] ), array( __( 'Page views', 'a2e-consent' ), $s['views'] ), array( __( 'Leads', 'a2e-consent' ), $s['leads'] ) ) as $tile ) {
			echo '<div class="a2ec-tile"><span>' . esc_html( $tile[0] ) . '</span><strong>' . esc_html( number_format_i18n( $tile[1] ) ) . '</strong></div>';
		}
		echo '</div>';
		if ( $src ) {
			echo '<p><strong>' . esc_html__( 'Top sources:', 'a2e-consent' ) . '</strong> ';
			$bits = array();
			foreach ( $src as $r ) {
				$bits[] = esc_html( $r['source'] ) . ' (' . esc_html( number_format_i18n( $r['visitors'] ) ) . ')';
			}
			echo implode( ', ', $bits ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Open Insights', 'a2e-consent' ) . '</a></p></div>';
	}
}
