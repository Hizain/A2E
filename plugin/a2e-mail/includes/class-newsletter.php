<?php
/**
 * The weekly newsletter. Once a week, on the day and time you choose, an issue is built for you
 * from what is new on your blog, a rotating tip of the week, a free tool of the week and a short
 * "how we can help" section, then sent to your weekly list through the normal sending queue
 * (within your limits, with one-click unsubscribe). You can preview it, send yourself a test,
 * add a note for the next issue, send an issue now or skip a week.
 *
 * People join the weekly list by signing up through your newsletter form, by tapping the join
 * button in your welcome email, or when you add them by hand and tick that they asked for it.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Newsletter.
 */
class Newsletter {

	const OPTION = 'a2email_newsletter';
	const STATE  = 'a2email_newsletter_state';
	const LOCK   = 'a2email_nl_lock';

	/**
	 * Hooks.
	 */
	public static function init() {
		// Runs before the queue on the one-minute schedule, so a new issue starts sending at once.
		add_action( CRON_SEND, array( __CLASS__, 'maybe_send' ), 5 );
		add_action( 'admin_post_a2email_newsletter_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_a2email_newsletter_test', array( __CLASS__, 'test' ) );
		add_action( 'admin_post_a2email_newsletter_now', array( __CLASS__, 'send_now' ) );
		add_action( 'admin_post_a2email_newsletter_skip', array( __CLASS__, 'skip' ) );
		add_action( 'admin_post_nopriv_a2email_join', array( __CLASS__, 'join' ) );
		add_action( 'admin_post_a2email_join', array( __CLASS__, 'join' ) );
	}

	/**
	 * Settings with defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$defaults = array(
			'enabled'     => '0',
			'day'         => 'tuesday',
			'time'        => '10:00',
			'audience'    => 'weekly',
			'subject'     => __( 'Weekly tips: {{headline}}', 'a2e-mail' ),
			'preheader'   => __( 'Practical ideas to grow your eBay and Shopify stores, from {{business}}.', 'a2e-mail' ),
			'opening'     => __( 'Here is your weekly roundup from {{business}}: practical ideas to grow your eBay and Shopify stores, plus what is new on our blog.', 'a2e-mail' ),
			'note'        => '',
			'posts'       => '1',
			'posts_count' => '3',
			'tip'         => '1',
			'tool'        => '1',
			'offer'       => '1',
			'need_posts'  => '0',
			'tips'        => self::default_tips(),
			'offer_text'  => self::default_offer(),
		);
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	/**
	 * Is the weekly newsletter switched on?
	 *
	 * @return bool
	 */
	public static function enabled() {
		$s = self::settings();
		return '1' === $s['enabled'];
	}

	/**
	 * Schedule and history state.
	 *
	 * @return array next_at, last_at, issue, last_id.
	 */
	public static function state() {
		$state = get_option( self::STATE, array() );
		return wp_parse_args( is_array( $state ) ? $state : array(), array( 'next_at' => 0, 'last_at' => 0, 'issue' => 0, 'last_id' => 0 ) );
	}

	/**
	 * Update state (merged).
	 *
	 * @param array $values Values.
	 */
	protected static function update_state( $values ) {
		update_option( self::STATE, array_merge( self::state(), $values ), false );
	}

	/**
	 * Week days for the schedule.
	 *
	 * @return array
	 */
	public static function days() {
		return array(
			'monday'    => __( 'Monday', 'a2e-mail' ),
			'tuesday'   => __( 'Tuesday', 'a2e-mail' ),
			'wednesday' => __( 'Wednesday', 'a2e-mail' ),
			'thursday'  => __( 'Thursday', 'a2e-mail' ),
			'friday'    => __( 'Friday', 'a2e-mail' ),
			'saturday'  => __( 'Saturday', 'a2e-mail' ),
			'sunday'    => __( 'Sunday', 'a2e-mail' ),
		);
	}

	/**
	 * The first send slot (chosen day and time, in the site's time zone) after a moment.
	 *
	 * @param int $after Unix time.
	 * @return int Unix time.
	 */
	public static function next_slot( $after ) {
		$s    = self::settings();
		$day  = array_key_exists( $s['day'], self::days() ) ? $s['day'] : 'tuesday';
		$hm   = array_map( 'intval', explode( ':', (string) $s['time'] ) );
		$base = ( new \DateTimeImmutable( '@' . (int) $after ) )->setTimezone( wp_timezone() );
		$slot = $base->modify( $day )->setTime( isset( $hm[0] ) ? min( 23, $hm[0] ) : 10, isset( $hm[1] ) ? min( 59, $hm[1] ) : 0 );
		if ( $slot->getTimestamp() <= (int) $after ) {
			$slot = $slot->modify( '+7 days' );
		}
		return $slot->getTimestamp();
	}

	/**
	 * Send the issue when its time has come (called every minute).
	 */
	public static function maybe_send() {
		if ( ! self::enabled() ) {
			return;
		}
		$state = self::state();
		if ( ! $state['next_at'] ) {
			self::update_state( array( 'next_at' => self::next_slot( time() ) ) );
			return;
		}
		if ( time() < (int) $state['next_at'] ) {
			return;
		}
		// One run at a time (add_option fails if another run holds the lock).
		$held = (int) get_option( self::LOCK, 0 );
		if ( $held && $held < time() - 600 ) {
			delete_option( self::LOCK );
		}
		if ( ! add_option( self::LOCK, time(), '', 'no' ) ) {
			return;
		}
		// Move the schedule on first, so a problem never repeats every minute.
		self::update_state( array( 'next_at' => self::next_slot( time() ) ) );
		self::create_issue( false );
		delete_option( self::LOCK );
	}

	/**
	 * Build the next issue and queue it to the audience.
	 *
	 * @param bool $manual True when you pressed "Send now" (sends even with no new posts).
	 * @return array ok, message, id.
	 */
	public static function create_issue( $manual ) {
		$s     = self::settings();
		$state = self::state();
		$no    = (int) $state['issue'] + 1;
		$built = self::build( $no, $state['last_at'] ? (int) $state['last_at'] : time() - WEEK_IN_SECONDS );

		if ( ! $manual && '1' === $s['need_posts'] && 0 === $built['post_count'] ) {
			log( 'newsletter', __( 'Skipped this week\'s newsletter: no new blog posts.', 'a2e-mail' ) );
			return array( 'ok' => false, 'message' => __( 'Skipped: there are no new blog posts this week.', 'a2e-mail' ) );
		}
		if ( ! Contacts::count_for_segment( $s['audience'] ) ) {
			log( 'newsletter', __( 'The weekly newsletter was not sent: nobody is on its list yet.', 'a2e-mail' ) );
			return array( 'ok' => false, 'message' => __( 'Nobody is on the newsletter list yet, so nothing was sent.', 'a2e-mail' ) );
		}

		/* translators: 1: issue number 2: headline */
		$title = sprintf( __( 'Weekly newsletter #%1$d: %2$s', 'a2e-mail' ), $no, $built['headline'] );
		$id    = wp_insert_post( wp_slash( array( 'post_type' => CPT, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $built['body'] ) ) );
		if ( ! $id || is_wp_error( $id ) ) {
			return array( 'ok' => false, 'message' => __( 'The issue could not be created.', 'a2e-mail' ) );
		}
		Composer::update_message(
			$id,
			array(
				'subject'   => $built['subject'],
				'preheader' => $built['preheader'],
				'segment'   => $s['audience'],
				'status'    => 'draft',
				'kind'      => 'newsletter',
				'issue'     => $no,
			)
		);
		$res = Queue::enqueue( $id );
		if ( ! $res['ok'] ) {
			wp_delete_post( $id, true );
			return $res;
		}
		self::update_state( array( 'issue' => $no, 'last_at' => time(), 'last_id' => (int) $id ) );
		// The note was for this issue only.
		if ( '' !== $s['note'] ) {
			$s['note'] = '';
			update_option( self::OPTION, $s, false );
		}
		/* translators: 1: issue number 2: recipients */
		log( 'newsletter', sprintf( __( 'Weekly newsletter #%1$d queued to %2$d people.', 'a2e-mail' ), $no, $res['queued'] ) );
		$res['id'] = (int) $id;
		return $res;
	}

	/**
	 * Build an issue: subject, preheader and body in the light email format.
	 *
	 * @param int $no    Issue number (picks the tip and tool of the week).
	 * @param int $since Include posts published after this time.
	 * @return array subject, preheader, body, headline, post_count, tip, tool.
	 */
	public static function build( $no, $since ) {
		$s      = self::settings();
		$blocks = array( __( 'Hello {{first_name}},', 'a2e-mail' ) );
		if ( '' !== trim( $s['opening'] ) ) {
			$blocks[] = trim( $s['opening'] );
		}
		if ( '' !== trim( $s['note'] ) ) {
			$blocks[] = trim( $s['note'] );
		}
		$headline = '';

		// New on the blog.
		$posts = array();
		if ( '1' === $s['posts'] ) {
			$posts = get_posts(
				array(
					'post_type'        => 'post',
					'post_status'      => 'publish',
					'numberposts'      => max( 1, min( 6, (int) $s['posts_count'] ) ),
					'orderby'          => 'date',
					'order'            => 'DESC',
					'suppress_filters' => false,
					'date_query'       => array( array( 'column' => 'post_date_gmt', 'after' => gmdate( 'Y-m-d H:i:s', (int) $since ) ) ),
				)
			);
		}
		if ( $posts ) {
			$blocks[] = '---';
			$blocks[] = '## ' . __( 'New on the blog', 'a2e-mail' );
			$pictured = false;
			foreach ( $posts as $i => $post ) {
				$title = self::plain( get_the_title( $post ) );
				$url   = get_permalink( $post );
				if ( 0 === $i ) {
					$headline = $title;
				}
				// One picture per issue keeps it light: the first post that has a featured image.
				$image = $pictured ? '' : get_the_post_thumbnail_url( $post, 'large' );
				if ( $image ) {
					$blocks[] = '![' . str_replace( array( '[', ']' ), '', $title ) . '](' . $image . ')';
					$pictured = true;
				}
				$blocks[] = '### [' . str_replace( array( '[', ']' ), array( '(', ')' ), $title ) . '](' . $url . ')';
				$excerpt  = self::plain( wp_trim_words( has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content ), 32 ) );
				if ( $excerpt ) {
					$blocks[] = $excerpt;
				}
				$blocks[] = 0 === $i ? '[[' . __( 'Read the article', 'a2e-mail' ) . '|' . $url . ']]' : '[' . __( 'Read the article', 'a2e-mail' ) . '](' . $url . ')';
			}
		}

		// Tip of the week.
		$tip = null;
		if ( '1' === $s['tip'] ) {
			$tips = self::tips();
			if ( $tips ) {
				$tip      = $tips[ ( max( 1, $no ) - 1 ) % count( $tips ) ];
				$blocks[] = '---';
				/* translators: %s: tip title */
				$blocks[] = '## ' . sprintf( __( 'Tip of the week: %s', 'a2e-mail' ), $tip['title'] );
				$blocks[] = $tip['text'];
				$headline = $headline ? $headline : $tip['title'];
			}
		}

		// Free tool of the week (A2E Prime's free tools).
		$tool = null;
		if ( '1' === $s['tool'] ) {
			$tools = self::tools();
			if ( $tools ) {
				$tool     = $tools[ ( max( 1, $no ) - 1 ) % count( $tools ) ];
				$blocks[] = '---';
				/* translators: %s: tool name */
				$blocks[] = '## ' . sprintf( __( 'Free tool of the week: %s', 'a2e-mail' ), $tool['name'] );
				if ( $tool['short'] ) {
					$blocks[] = $tool['short'];
				}
				/* translators: %s: tool name */
				$blocks[] = '[[' . sprintf( __( 'Try the %s', 'a2e-mail' ), $tool['name'] ) . '|' . $tool['url'] . ']]';
			}
		}

		// How we can help.
		if ( '1' === $s['offer'] && '' !== trim( $s['offer_text'] ) ) {
			$blocks[] = '---';
			$blocks[] = trim( $s['offer_text'] );
		}

		$blocks[] = '{{signature}}';
		$headline = $headline ? $headline : sprintf( __( 'News from %s', 'a2e-mail' ), setting( 'business_name' ) );

		return array(
			'subject'    => str_replace( array( '{{headline}}', '{{issue}}' ), array( $headline, (string) $no ), $s['subject'] ),
			'preheader'  => str_replace( array( '{{headline}}', '{{issue}}', '{{business}}' ), array( $headline, (string) $no, setting( 'business_name' ) ), $s['preheader'] ),
			'body'       => implode( "\n\n", $blocks ),
			'headline'   => $headline,
			'post_count' => count( $posts ),
			'tip'        => $tip,
			'tool'       => $tool,
		);
	}

	/**
	 * Text from a post, safe for the light email format (no tags, no markers at line starts).
	 *
	 * @param string $text Text.
	 * @return string
	 */
	protected static function plain( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		$text = str_replace( array( '[[', ']]', '{{', '}}' ), '', $text );
		return ltrim( $text, "#-!> \t" );
	}

	/**
	 * The tip bank: blocks separated by a blank line, first line is the title.
	 *
	 * @return array List of [ title, text ].
	 */
	public static function tips() {
		$s     = self::settings();
		$out   = array();
		$parts = preg_split( "/\n\s*\n/", str_replace( array( "\r\n", "\r" ), "\n", trim( (string) $s['tips'] ) ) );
		foreach ( $parts as $part ) {
			$lines = explode( "\n", trim( $part ), 2 );
			$title = trim( $lines[0] );
			$text  = isset( $lines[1] ) ? trim( preg_replace( '/\s*\n\s*/', ' ', $lines[1] ) ) : '';
			if ( '' !== $title && '' !== $text ) {
				$out[] = array( 'title' => $title, 'text' => $text );
			}
		}
		return $out;
	}

	/**
	 * Free tools from the A2E Prime theme (and plugins that add tools), if the theme is active.
	 *
	 * @return array List of [ name, short, url ].
	 */
	public static function tools() {
		if ( ! function_exists( 'a2e_tools' ) ) {
			return array();
		}
		$out = array();
		foreach ( (array) a2e_tools() as $slug => $tool ) {
			if ( empty( $tool['name'] ) ) {
				continue;
			}
			$out[] = array(
				'name'  => $tool['name'],
				'short' => isset( $tool['short'] ) ? self::plain( $tool['short'] ) : '',
				'url'   => function_exists( 'a2e_tool_url' ) ? a2e_tool_url( $slug ) : home_url( '/free-tools/' . $slug . '/' ),
			);
		}
		return $out;
	}

	/**
	 * Default tip bank (one a week, then it starts again).
	 *
	 * @return string
	 */
	public static function default_tips() {
		$tips = array(
			array( __( 'Use all 80 characters of your eBay titles', 'a2e-mail' ), __( 'Buyers search with specific words. Lead with what the item is, then the brand, model, size, colour and key feature. Skip filler like "L@@K" or "wow". A full, specific title brings more views without spending a cent on ads.', 'a2e-mail' ) ),
			array( __( 'Fill in every item specific', 'a2e-mail' ), __( 'eBay uses item specifics in search and in the filters buyers click, like brand, size, type and colour. A listing with missing specifics can drop out of filtered results completely, so fill in every recommended specific for your category.', 'a2e-mail' ) ),
			array( __( 'Price after every fee, not before', 'a2e-mail' ), __( 'Your real profit is what is left after the final value fee, the per-order fee, any promoted listing fee and your product and shipping cost. Check each product with a fee calculator before you list it, and set a minimum margin you never go below.', 'a2e-mail' ) ),
			array( __( 'Keep your handling time realistic', 'a2e-mail' ), __( 'Late shipments count against your seller level. Set a handling time you can always meet, upload valid tracking on time and use a carrier that scans packages promptly. Shipping on time, every time, protects Top Rated status.', 'a2e-mail' ) ),
			array( __( 'Fix problems before they become cases', 'a2e-mail' ), __( 'A case that closes without the seller fixing it hurts your account standing. When a buyer reports a problem, reply fast and offer a refund, replacement or return straight away. It costs far less than the damage to your account.', 'a2e-mail' ) ),
			array( __( 'Check supplier price and stock every day', 'a2e-mail' ), __( 'Supplier prices and stock change often. If you sell an item you cannot send and have to cancel, eBay counts it as a transaction defect. Daily price and stock checks keep your listings accurate and your margins safe, and this is exactly the kind of job automation does well.', 'a2e-mail' ) ),
			array( __( 'Photos win the click', 'a2e-mail' ), __( 'Use clear, bright photos on a plain background, show the item from several angles and add close-ups of the details. eBay lets you add up to 24 photos to most listings at no extra cost, so use them.', 'a2e-mail' ) ),
			array( __( 'Promote listings with a margin plan', 'a2e-mail' ), __( 'With Promoted Listings General you pay only when a buyer clicks your ad and buys within 30 days. Start with a modest ad rate on items that already sell, and make sure your margin still works after the ad fee.', 'a2e-mail' ) ),
			array( __( 'Follow your sell-through rate', 'a2e-mail' ), __( 'Sell-through rate is the number of sold listings divided by sold plus active listings for the same product. A higher rate means steady demand. Build your store around products that sell through well and drop the ones that just sit.', 'a2e-mail' ) ),
			array( __( 'Earn the Top Rated Plus badge', 'a2e-mail' ), __( 'Top Rated sellers who offer same or 1 business day handling and free returns of 30 days or longer get the Top Rated Plus badge on those listings, plus a discount on final value fees. The badge builds trust and helps your listings stand out.', 'a2e-mail' ) ),
			array( __( 'Build a brand, not just listings', 'a2e-mail' ), __( 'A branded eBay store with a logo, clear store categories and a consistent listing style makes buyers trust you and come back. Organise your categories so buyers can browse more of your items in one visit.', 'a2e-mail' ) ),
			array( __( 'Give your Shopify store trust signals', 'a2e-mail' ), __( 'Shoppers decide in seconds. Show clear delivery times, a simple returns policy, real reviews and an easy way to contact you. Compress your images so pages load fast on phones, where most people shop.', 'a2e-mail' ) ),
			array( __( 'Let automation do the repetitive work', 'a2e-mail' ), __( 'AI tools like Claude can draft listing titles and descriptions, compare supplier prices and flag listings that need attention, so you spend your time on decisions instead of copy and paste. Always review the output before it goes live.', 'a2e-mail' ) ),
		);
		return implode(
			"\n\n",
			array_map(
				function ( $t ) {
					return $t[0] . "\n" . $t[1];
				},
				$tips
			)
		);
	}

	/**
	 * Default "how we can help" section.
	 *
	 * @return string
	 */
	public static function default_offer() {
		return __( "## How A2E Dropshipping can help you grow\n\nWe are A2E Dropshipping. We help sellers grow faster with a real team and Claude automation:\n\n- **Amazon to eBay dropshipping stores:** product research, SEO listings and daily price and stock checks\n- **Branded eBay stores:** store design, branding and listings that win repeat buyers\n- **Shopify stores:** setup, products and ongoing management\n- **Claude automation:** AI-assisted research, listing writing and routine store work\n\n[[Get a free store audit|{{audit_url}}]]\n\nQuestions? Reply to this email or message us on WhatsApp {{whatsapp}}.", 'a2e-mail' );
	}

	/**
	 * The link that adds a contact to the weekly list (with a confirm step).
	 *
	 * @param string $token Contact token.
	 * @return string
	 */
	public static function join_url( $token ) {
		if ( '' === (string) $token ) {
			return '';
		}
		return add_query_arg( array( 'action' => 'a2email_join', 't' => rawurlencode( $token ) ), admin_url( 'admin-post.php' ) );
	}

	/**
	 * Join the weekly list. A GET shows a confirm button (so link scanners never sign anyone up);
	 * the POST from that button adds the person.
	 */
	public static function join() {
		$token = isset( $_REQUEST['t'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post  = 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET' );
		if ( 'preview' === $token ) {
			Unsubscribe::page( __( 'This is a preview', 'a2e-mail' ), __( 'This was a test email, so there is nothing to join.', 'a2e-mail' ) );
		}
		$contact = Contacts::get_by_token( $token );
		if ( ! $contact || Contacts::STATUS_BOUNCED === $contact['status'] ) {
			Unsubscribe::page( __( 'Link not recognised', 'a2e-mail' ), __( 'This link is not valid. Reply to any of our emails and we will add you by hand.', 'a2e-mail' ) );
		}
		$s    = self::settings();
		$days = self::days();
		$day  = isset( $days[ $s['day'] ] ) ? $days[ $s['day'] ] : '';
		if ( ! $post ) {
			$body = '<p>' . esc_html( sprintf( __( 'Get one short email a week with practical tips to grow your eBay and Shopify stores, sent to %s. You can leave any time.', 'a2e-mail' ), $contact['email'] ) ) . '</p>'
				. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:18px;">'
				. '<input type="hidden" name="action" value="a2email_join">'
				. '<input type="hidden" name="t" value="' . esc_attr( $contact['token'] ) . '">'
				. '<button type="submit" class="a2e-btn">' . esc_html__( 'Yes, send me the weekly tips', 'a2e-mail' ) . '</button>'
				. '</form>';
			Unsubscribe::page( __( 'Join our weekly tips', 'a2e-mail' ), $body, true );
		}
		global $wpdb;
		if ( Contacts::STATUS_SUBSCRIBED !== $contact['status'] ) {
			// They asked to hear from us again.
			$wpdb->update( Contacts::table(), array( 'status' => Contacts::STATUS_SUBSCRIBED, 'unsubscribed_at' => null, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $contact['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		Contacts::add_tag( (int) $contact['id'], Contacts::TAG_WEEKLY );
		log( 'newsletter', sprintf( __( '%s joined the weekly newsletter.', 'a2e-mail' ), $contact['email'] ) );
		/* translators: %s: week day */
		$when = $day ? sprintf( __( 'Your first issue arrives on %s.', 'a2e-mail' ), $day ) : '';
		Unsubscribe::page( __( 'You are on the list', 'a2e-mail' ), trim( __( 'Thank you. You will get our weekly tips by email.', 'a2e-mail' ) . ' ' . $when . ' ' . __( 'You can leave any time from the link at the bottom of every email.', 'a2e-mail' ) ) );
	}

	/**
	 * Check nonce and capability for the admin actions.
	 */
	protected static function guard() {
		check_admin_referer( 'a2email_newsletter' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
	}

	/**
	 * Redirect back to the newsletter screen with a notice.
	 *
	 * @param string $type success|error.
	 * @param string $msg  Message.
	 */
	protected static function back( $type, $msg ) {
		set_transient( 'a2email_notice_' . get_current_user_id(), array( $type, $msg ), 60 );
		wp_safe_redirect( admin_page_url( 'newsletter' ) );
		exit;
	}

	/**
	 * Save the settings from the form (shared by Save and Send a test).
	 */
	protected static function save_from_request() {
		$in  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$old = self::settings();
		$txt = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : '';
		};
		$area = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? trim( wp_strip_all_tags( wp_check_invalid_utf8( $in[ $key ] ) ) ) : '';
		};
		$segments = Contacts::segments();
		$time     = preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $txt( 'time' ) ) ? $txt( 'time' ) : '10:00';
		$values   = array(
			'enabled'     => empty( $in['enabled'] ) ? '0' : '1',
			'day'         => array_key_exists( $txt( 'day' ), self::days() ) ? $txt( 'day' ) : 'tuesday',
			'time'        => $time,
			'audience'    => isset( $segments[ $txt( 'audience' ) ] ) ? $txt( 'audience' ) : 'weekly',
			'subject'     => $txt( 'subject' ) ? $txt( 'subject' ) : $old['subject'],
			'preheader'   => $txt( 'preheader' ),
			'opening'     => $area( 'opening' ),
			'note'        => $area( 'note' ),
			'posts'       => empty( $in['posts'] ) ? '0' : '1',
			'posts_count' => (string) max( 1, min( 6, (int) ( $in['posts_count'] ?? 3 ) ) ),
			'tip'         => empty( $in['tip'] ) ? '0' : '1',
			'tool'        => empty( $in['tool'] ) ? '0' : '1',
			'offer'       => empty( $in['offer'] ) ? '0' : '1',
			'need_posts'  => empty( $in['need_posts'] ) ? '0' : '1',
			'tips'        => $area( 'tips' ) ? $area( 'tips' ) : self::default_tips(),
			'offer_text'  => $area( 'offer_text' ),
		);
		update_option( self::OPTION, $values, false );
		// A new day, time or switching on resets the schedule from now.
		if ( '1' === $values['enabled'] && ( '1' !== $old['enabled'] || $old['day'] !== $values['day'] || $old['time'] !== $values['time'] || ! self::state()['next_at'] ) ) {
			self::update_state( array( 'next_at' => self::next_slot( time() ) ) );
		}
		return $values;
	}

	/**
	 * Save handler.
	 */
	public static function save() {
		self::guard();
		$values = self::save_from_request();
		$msg    = '1' === $values['enabled']
			/* translators: %s: date and time */
			? sprintf( __( 'Saved. The next issue goes out on %s.', 'a2e-mail' ), self::format_time( self::state()['next_at'] ) )
			: __( 'Saved. The weekly newsletter is off; tick "Send it every week" to start it.', 'a2e-mail' );
		self::back( 'success', $msg );
	}

	/**
	 * Send a test of the next issue (saves first).
	 */
	public static function test() {
		self::guard();
		self::save_from_request();
		$to = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! is_email( $to ) ) {
			self::back( 'error', __( 'Enter a valid email address to test.', 'a2e-mail' ) );
		}
		$state   = self::state();
		$built   = self::build( (int) $state['issue'] + 1, $state['last_at'] ? (int) $state['last_at'] : time() - WEEK_IN_SECONDS );
		$contact = array( 'name' => wp_get_current_user()->display_name, 'email' => $to, 'token' => 'preview' );
		$render  = Templates::render( $built, $contact );
		$ok      = Mailer::send( $to, sprintf( __( '[Test] %s', 'a2e-mail' ), $render['subject'] ), $render['html'], $render['text'], Unsubscribe::url( 'preview' ) );
		self::back( $ok ? 'success' : 'error', $ok ? sprintf( __( 'Test of the next issue sent to %s.', 'a2e-mail' ), $to ) : __( 'The test could not be sent. Check A2E Mail > Settings.', 'a2e-mail' ) );
	}

	/**
	 * Send the next issue now. The weekly schedule then skips ahead so people do not get two
	 * issues within a few days.
	 */
	public static function send_now() {
		self::guard();
		$res = self::create_issue( true );
		if ( $res['ok'] ) {
			if ( self::enabled() ) {
				self::update_state( array( 'next_at' => self::next_slot( time() + 3 * DAY_IN_SECONDS ) ) );
			}
			Queue::process();
		}
		self::back( $res['ok'] ? 'success' : 'error', $res['message'] );
	}

	/**
	 * Skip the next scheduled issue.
	 */
	public static function skip() {
		self::guard();
		$state = self::state();
		$from  = max( time(), (int) $state['next_at'] );
		self::update_state( array( 'next_at' => self::next_slot( $from + 60 ) ) );
		/* translators: %s: date and time */
		self::back( 'success', sprintf( __( 'Skipped. The next issue now goes out on %s.', 'a2e-mail' ), self::format_time( self::state()['next_at'] ) ) );
	}

	/**
	 * Date and time in the site's format and time zone.
	 *
	 * @param int $ts Unix time.
	 * @return string
	 */
	public static function format_time( $ts ) {
		return $ts ? wp_date( 'l, ' . get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $ts ) : __( 'not scheduled', 'a2e-mail' );
	}

	/**
	 * Past issues (newest first).
	 *
	 * @param int $limit Max.
	 * @return array Message posts.
	 */
	public static function issues( $limit = 20 ) {
		return get_posts(
			array(
				'post_type'   => CPT,
				'post_status' => 'publish',
				'numberposts' => $limit,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'meta_key'    => '_kind', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
				'meta_value'  => 'newsletter', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
			)
		);
	}

	/**
	 * Render the Newsletter screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s        = self::settings();
		$state    = self::state();
		$segments = Contacts::segments();
		$audience = isset( $segments[ $s['audience'] ] ) ? $segments[ $s['audience'] ] : $segments['weekly'];
		$built    = self::build( (int) $state['issue'] + 1, $state['last_at'] ? (int) $state['last_at'] : time() - WEEK_IN_SECONDS );
		$sample   = Contacts::recipients( $s['audience'], 1 );
		$preview  = Templates::render( $built, $sample ? Contacts::get( (int) $sample[0]['id'] ) : array( 'name' => __( 'Jordan Lee', 'a2e-mail' ), 'email' => 'jordan@example.com', 'token' => 'preview' ) );
		$issues   = self::issues();
		$post_url = esc_url( admin_url( 'admin-post.php' ) );
		$on       = '1' === $s['enabled'];
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'Weekly newsletter', 'a2e-mail' ); ?></h1>
			<p class="a2email-lead"><?php esc_html_e( 'Once a week, on the day and time you choose, a newsletter is built for you and sent to your list: what is new on your blog, a tip of the week, a free tool of the week and how A2E can help. Preview it, send yourself a test, add a note for the next issue, or skip a week.', 'a2e-mail' ); ?></p>
			<?php Admin::print_notice(); ?>

			<div class="a2email-stats">
				<div class="a2email-stat"><span><?php esc_html_e( 'Status', 'a2e-mail' ); ?></span><strong><span class="a2email-pill a2email-pill--<?php echo $on ? 'sent' : 'paused'; ?>"><?php echo $on ? esc_html__( 'On', 'a2e-mail' ) : esc_html__( 'Off', 'a2e-mail' ); ?></span></strong></div>
				<div class="a2email-stat"><span><?php esc_html_e( 'Next issue', 'a2e-mail' ); ?></span><strong class="a2email-stat__small"><?php echo $on ? esc_html( self::format_time( $state['next_at'] ? $state['next_at'] : self::next_slot( time() ) ) ) : esc_html__( 'Turn it on below', 'a2e-mail' ); ?></strong></div>
				<div class="a2email-stat"><span><?php esc_html_e( 'People who will get it', 'a2e-mail' ); ?></span><strong><?php echo (int) $audience['count']; ?></strong></div>
				<div class="a2email-stat"><span><?php esc_html_e( 'Issues sent', 'a2e-mail' ); ?></span><strong><?php echo (int) $state['issue']; ?></strong></div>
			</div>

			<div class="a2email-compose">
				<form method="post" action="<?php echo $post_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="a2email-compose__form">
					<?php wp_nonce_field( 'a2email_newsletter' ); ?>

					<p class="a2email-field a2email-switch"><label><input type="checkbox" name="enabled" value="1" <?php checked( $on ); ?>> <?php esc_html_e( 'Send it every week, automatically', 'a2e-mail' ); ?></label></p>

					<div class="a2email-row2">
						<p class="a2email-field">
							<label for="a2nl-day"><?php esc_html_e( 'Day', 'a2e-mail' ); ?></label>
							<select id="a2nl-day" name="day" class="a2email-input">
								<?php foreach ( self::days() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['day'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p class="a2email-field">
							<label for="a2nl-time"><?php esc_html_e( 'Time', 'a2e-mail' ); ?></label>
							<input type="time" id="a2nl-time" name="time" class="a2email-input" value="<?php echo esc_attr( $s['time'] ); ?>">
							<span class="a2email-help"><?php printf( esc_html__( 'Your site time zone: %s', 'a2e-mail' ), esc_html( wp_timezone_string() ) ); ?></span>
						</p>
					</div>

					<p class="a2email-field">
						<label for="a2nl-audience"><?php esc_html_e( 'Send to', 'a2e-mail' ); ?></label>
						<select id="a2nl-audience" name="audience" class="a2email-input">
							<?php foreach ( $segments as $key => $seg ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['audience'], $key ); ?>><?php echo esc_html( $seg['label'] ); ?> (<?php echo (int) $seg['count']; ?>)</option>
							<?php endforeach; ?>
						</select>
						<span class="a2email-help"><?php esc_html_e( 'Recommended: "Weekly newsletter list". It holds everyone who signed up through your newsletter form, everyone who tapped "Yes, send me the weekly tips" in your welcome email, and contacts you add by hand who asked for it. Your privacy policy says the newsletter goes to people who subscribed, so only choose a wider group if those people expect a weekly email from you.', 'a2e-mail' ); ?></span>
					</p>

					<p class="a2email-field">
						<label for="a2nl-subject"><?php esc_html_e( 'Subject line', 'a2e-mail' ); ?></label>
						<input type="text" id="a2nl-subject" name="subject" class="a2email-input" value="<?php echo esc_attr( $s['subject'] ); ?>">
						<span class="a2email-help"><?php esc_html_e( '{{headline}} becomes the newest blog post title (or the tip of the week when there is no new post). {{issue}} is the issue number.', 'a2e-mail' ); ?></span>
					</p>
					<p class="a2email-field">
						<label for="a2nl-preheader"><?php esc_html_e( 'Preview text (shown after the subject in the inbox)', 'a2e-mail' ); ?></label>
						<input type="text" id="a2nl-preheader" name="preheader" class="a2email-input" value="<?php echo esc_attr( $s['preheader'] ); ?>">
					</p>
					<p class="a2email-field">
						<label for="a2nl-opening"><?php esc_html_e( 'Opening line (every issue)', 'a2e-mail' ); ?></label>
						<textarea id="a2nl-opening" name="opening" rows="3" class="a2email-input"><?php echo esc_textarea( $s['opening'] ); ?></textarea>
					</p>
					<p class="a2email-field">
						<label for="a2nl-note"><?php esc_html_e( 'Note for the next issue only (optional)', 'a2e-mail' ); ?></label>
						<textarea id="a2nl-note" name="note" rows="4" class="a2email-input a2email-body" placeholder="<?php esc_attr_e( 'For example: This month we are offering a free listing review to the first 10 people who reply.', 'a2e-mail' ); ?>"><?php echo esc_textarea( $s['note'] ); ?></textarea>
						<span class="a2email-help"><?php esc_html_e( 'Shown under the opening line, then cleared once that issue is sent. Same simple format as Compose: blank line between paragraphs, ## heading, - bullet, [[Button|https://link]].', 'a2e-mail' ); ?></span>
					</p>

					<fieldset class="a2email-field a2email-triggers">
						<legend><?php esc_html_e( 'What goes in each issue', 'a2e-mail' ); ?></legend>
						<label><input type="checkbox" name="posts" value="1" <?php checked( '1', $s['posts'] ); ?>> <?php esc_html_e( 'New blog posts since the last issue, up to', 'a2e-mail' ); ?> <input type="number" name="posts_count" min="1" max="6" value="<?php echo esc_attr( $s['posts_count'] ); ?>" class="small-text"></label>
						<label><input type="checkbox" name="tip" value="1" <?php checked( '1', $s['tip'] ); ?>> <?php esc_html_e( 'Tip of the week (a different one each week, from your tip list below)', 'a2e-mail' ); ?></label>
						<label><input type="checkbox" name="tool" value="1" <?php checked( '1', $s['tool'] ); ?>> <?php esc_html_e( 'Free tool of the week (one of your free tools, a different one each week)', 'a2e-mail' ); ?></label>
						<label><input type="checkbox" name="offer" value="1" <?php checked( '1', $s['offer'] ); ?>> <?php esc_html_e( 'How A2E can help (your offer, below)', 'a2e-mail' ); ?></label>
						<label><input type="checkbox" name="need_posts" value="1" <?php checked( '1', $s['need_posts'] ); ?>> <?php esc_html_e( 'Skip a week when there is no new blog post', 'a2e-mail' ); ?></label>
					</fieldset>

					<details class="a2email-more">
						<summary><?php esc_html_e( 'Edit your offer section', 'a2e-mail' ); ?></summary>
						<textarea name="offer_text" rows="12" class="a2email-input a2email-body"><?php echo esc_textarea( $s['offer_text'] ); ?></textarea>
					</details>
					<details class="a2email-more">
						<summary><?php printf( esc_html__( 'Edit your tips (%d)', 'a2e-mail' ), count( self::tips() ) ); ?></summary>
						<textarea name="tips" rows="16" class="a2email-input a2email-body"><?php echo esc_textarea( $s['tips'] ); ?></textarea>
						<span class="a2email-help"><?php esc_html_e( 'One tip per block, with a blank line between tips. The first line is the title, the rest is the tip. Issue 1 uses the first tip, issue 2 the second, and so on, then it starts again.', 'a2e-mail' ); ?></span>
					</details>

					<div class="a2email-actions">
						<button type="submit" name="action" value="a2email_newsletter_save" class="button button-primary"><?php esc_html_e( 'Save', 'a2e-mail' ); ?></button>
						<span class="a2email-test">
							<input type="email" name="test_email" class="a2email-input a2email-input--inline" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
							<button type="submit" name="action" value="a2email_newsletter_test" class="button"><?php esc_html_e( 'Save and send me a test', 'a2e-mail' ); ?></button>
						</span>
					</div>
				</form>

				<div class="a2email-preview">
					<div class="a2email-preview__bar">
						<span class="a2email-preview__label"><?php printf( esc_html__( 'Preview of issue #%d (saved settings)', 'a2e-mail' ), (int) $state['issue'] + 1 ); ?></span>
						<span class="a2email-preview__subject"><?php echo esc_html( $preview['subject'] ); ?></span>
						<span class="a2email-muted"><?php echo esc_html( self::summary( $built ) ); ?></span>
					</div>
					<iframe id="a2email-nl-frame" title="<?php esc_attr_e( 'Newsletter preview', 'a2e-mail' ); ?>" srcdoc="<?php echo esc_attr( $preview['html'] ); ?>"></iframe>
					<div class="a2email-preview__foot">
						<form method="post" action="<?php echo $post_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" onsubmit="return confirm('<?php echo esc_js( sprintf( __( 'Send this issue now to %d people?', 'a2e-mail' ), (int) $audience['count'] ) ); ?>');">
							<?php wp_nonce_field( 'a2email_newsletter' ); ?>
							<input type="hidden" name="action" value="a2email_newsletter_now">
							<button type="submit" class="button"><?php esc_html_e( 'Send this issue now', 'a2e-mail' ); ?></button>
						</form>
						<?php if ( $on ) : ?>
							<form method="post" action="<?php echo $post_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
								<?php wp_nonce_field( 'a2email_newsletter' ); ?>
								<input type="hidden" name="action" value="a2email_newsletter_skip">
								<button type="submit" class="button-link"><?php esc_html_e( 'Skip the next issue', 'a2e-mail' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<h2 class="a2email-h2"><?php esc_html_e( 'Past issues', 'a2e-mail' ); ?></h2>
			<?php if ( ! $issues ) : ?>
				<p class="a2email-muted"><?php esc_html_e( 'No issues sent yet.', 'a2e-mail' ); ?></p>
			<?php else : ?>
				<table class="widefat striped a2email-table">
					<thead><tr>
						<th><?php esc_html_e( 'Issue', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Sent', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Delivered', 'a2e-mail' ); ?></th>
						<th></th>
					</tr></thead>
					<tbody>
					<?php
					foreach ( $issues as $p ) :
						$m    = Composer::get_message( $p->ID );
						$prog = Queue::progress( $p->ID );
						?>
						<tr>
							<td><strong><?php echo esc_html( $m['title'] ); ?></strong><br><span class="a2email-muted"><?php echo esc_html( $m['subject'] ); ?></span></td>
							<td><?php echo esc_html( get_the_date( '', $p ) . ' ' . get_the_time( '', $p ) ); ?></td>
							<td><?php echo (int) $prog['sent']; ?> / <?php echo (int) $prog['total']; ?><?php echo $prog['failed'] ? ' <span class="a2email-danger">(' . (int) $prog['failed'] . ' ' . esc_html__( 'failed', 'a2e-mail' ) . ')</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td class="a2email-row-actions"><a href="<?php echo esc_url( admin_page_url( 'messages', array( 'message' => $p->ID ) ) ); ?>"><?php esc_html_e( 'Progress', 'a2e-mail' ); ?></a> <a href="<?php echo esc_url( admin_page_url( 'compose', array( 'message' => $p->ID ) ) ); ?>"><?php esc_html_e( 'View', 'a2e-mail' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * One line on what the next issue holds.
	 *
	 * @param array $built Built issue.
	 * @return string
	 */
	protected static function summary( $built ) {
		$parts = array();
		/* translators: %d: posts */
		$parts[] = sprintf( _n( '%d new post', '%d new posts', $built['post_count'], 'a2e-mail' ), $built['post_count'] );
		if ( $built['tip'] ) {
			/* translators: %s: tip */
			$parts[] = sprintf( __( 'tip: %s', 'a2e-mail' ), $built['tip']['title'] );
		}
		if ( $built['tool'] ) {
			/* translators: %s: tool */
			$parts[] = sprintf( __( 'tool: %s', 'a2e-mail' ), $built['tool']['name'] );
		}
		return implode( ' · ', $parts );
	}
}
