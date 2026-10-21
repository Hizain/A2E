<?php
/**
 * Email templates and the branded, email-safe HTML renderer.
 *
 * The body is written in a simple, friendly format (blank-line paragraphs, "## heading",
 * "- bullet", and [[Label|https://link]] buttons) with merge tags like {{first_name}}.
 * The renderer wraps it in a theme-styled shell with inline styles so it looks right in every
 * email client, and it strips long dashes and always adds your contact details and an
 * unsubscribe link.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Templates.
 */
class Templates {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the template post type (admin only).
	 */
	public static function register() {
		register_post_type(
			TEMPLATE,
			array(
				'labels'       => array(
					'name'          => __( 'Templates', 'a2e-mail' ),
					'singular_name' => __( 'Template', 'a2e-mail' ),
					'add_new_item'  => __( 'Add New Template', 'a2e-mail' ),
					'edit_item'     => __( 'Edit Template', 'a2e-mail' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => array( 'title' ),
			)
		);
	}

	/**
	 * Brand colours, taken from the theme when present.
	 *
	 * @return array
	 */
	public static function palette() {
		return array(
			'ink'     => '#0b1030',
			'muted'   => '#4b5375',
			'faint'   => '#7c83a2',
			'bg'      => '#eef1f8',
			'surface' => '#ffffff',
			'line'    => '#e4e7f2',
			'brand'   => '#2b4cf2',
			'brand2'  => '#1d8cf8',
			'brand3'  => '#7a3cf0',
			'good'    => '#0d9b5f',
		);
	}

	/**
	 * Default templates, created once on activation. All wording avoids long dashes.
	 *
	 * @return array slug => [ name, subject, preheader, category, body ]
	 */
	public static function defaults() {
		$sig = "\n\n{{signature}}";
		return array(
			'welcome'      => array(
				'name'      => __( 'Welcome a new client', 'a2e-mail' ),
				'subject'   => __( 'Welcome to A2E, {{first_name}}', 'a2e-mail' ),
				'preheader' => __( 'A quick hello and what happens next with your store.', 'a2e-mail' ),
				'category'  => 'clients',
				'body'      => __( "Hello {{first_name}},\n\nThank you for trusting A2E with your eBay store. I wanted to say hello personally and tell you what happens next.\n\nOver the next business day a real person from our team will reach out to confirm your store details, your niches and the access you want to give us. From there we get to work: researching products, writing listings that rank, and keeping your store healthy every day.\n\n## You are never left guessing\n\nYou will always know what we are doing and why. If something will not work for your store, we will tell you before you spend a cent.\n\nIf anything comes up, reply to this email or reach us any time:\n\n- WhatsApp: {{whatsapp}}\n- Call: {{phone}}\n- Email: {{contact_email}}\n\n[[See your plan and next steps|{{site_url}}]]", 'a2e-mail' ) . $sig,
			),
			'audit_followup' => array(
				'name'      => __( 'Follow up on a free audit request', 'a2e-mail' ),
				'subject'   => __( 'Your free eBay store audit, {{first_name}}', 'a2e-mail' ),
				'preheader' => __( 'A clear plan for your store, with a fixed price and no pressure.', 'a2e-mail' ),
				'category'  => 'leads',
				'body'      => __( "Hello {{first_name}},\n\nThank you for asking us to look at your eBay store. We take this seriously, so I want to be clear and useful rather than pushy.\n\nWe review your listings, pricing and account health, then send a short written plan with a fixed price. If we are not the right fit, we will say so honestly.\n\nTo make the audit accurate, it helps to know your main niches and where you feel stuck right now. You can reply here, or reach me directly:\n\n- WhatsApp: {{whatsapp}}\n- Call: {{phone}}\n- Email: {{contact_email}}\n\nNo pressure either way. I am happy to answer questions first.\n\n[[Request your free audit|{{site_url}}]]", 'a2e-mail' ) . $sig,
			),
			'promo'        => array(
				'name'      => __( 'Seasonal promotion', 'a2e-mail' ),
				'subject'   => __( 'A focused offer for your eBay store', 'a2e-mail' ),
				'preheader' => __( 'A simple way to add profit-checked listings this month.', 'a2e-mail' ),
				'category'  => 'promotions',
				'body'      => __( "Hello {{first_name}},\n\nI will keep this short. For a limited time we are helping sellers add a batch of profit-checked listings to their store, researched the same careful way we work for every client.\n\n## What you get\n\n- Products chosen on real sold data, not recycled lists\n- SEO titles and complete item specifics\n- Clean, mobile-friendly descriptions\n- A delivery report with the margin on every product\n\nIf this is useful, I would be glad to walk you through it. If the timing is wrong, no problem at all.\n\n- WhatsApp: {{whatsapp}}\n- Call: {{phone}}\n- Email: {{contact_email}}\n\n[[See the details|{{site_url}}]]", 'a2e-mail' ) . $sig,
			),
			'checkin'      => array(
				'name'      => __( 'Monthly check-in with a client', 'a2e-mail' ),
				'subject'   => __( 'A quick check-in on your store, {{first_name}}', 'a2e-mail' ),
				'preheader' => __( 'How things are going and what is next.', 'a2e-mail' ),
				'category'  => 'clients',
				'body'      => __( "Hello {{first_name}},\n\nI wanted to check in on your store. Our goal every month is steady, honest progress: new researched listings, quick fixes when a supplier changes, and fast answers to your buyers.\n\nIf there is anything you would like us to focus on next, tell me and we will build it into the plan.\n\n- WhatsApp: {{whatsapp}}\n- Call: {{phone}}\n- Email: {{contact_email}}\n\nThank you for working with us.", 'a2e-mail' ) . $sig,
			),
			'plain'        => array(
				'name'      => __( 'Blank, branded message', 'a2e-mail' ),
				'subject'   => __( 'A note from A2E Dropshipping', 'a2e-mail' ),
				'preheader' => '',
				'category'  => 'general',
				'body'      => __( "Hello {{first_name}},\n\nWrite your message here. Leave a blank line between paragraphs. Start a line with ## for a heading, or with - for a bullet point. Add a button like this:\n\n[[Button label|https://a2edropshipping.com/]]", 'a2e-mail' ) . $sig,
			),
		);
	}

	/**
	 * Create the default templates once.
	 */
	public static function seed() {
		if ( get_option( 'a2email_templates_seeded' ) ) {
			return;
		}
		update_option( 'a2email_templates_seeded', VERSION, false );
		foreach ( self::defaults() as $slug => $tpl ) {
			if ( get_page_by_path( $slug, OBJECT, TEMPLATE ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => TEMPLATE,
					'post_status'  => 'publish',
					'post_name'    => $slug,
					'post_title'   => $tpl['name'],
					'post_content' => $tpl['body'],
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_subject', $tpl['subject'] );
				update_post_meta( $id, '_preheader', $tpl['preheader'] );
				update_post_meta( $id, '_category', $tpl['category'] );
			}
		}
	}

	/**
	 * Add the "What we offer" template once (for one-off sends from Compose).
	 */
	public static function seed_offer() {
		if ( get_option( 'a2email_templates_v13' ) ) {
			return;
		}
		update_option( 'a2email_templates_v13', VERSION, false );
		if ( get_page_by_path( 'what-we-offer', OBJECT, TEMPLATE ) ) {
			return;
		}
		$step = Automations::welcome_step();
		$id   = wp_insert_post(
			array(
				'post_type'    => TEMPLATE,
				'post_status'  => 'publish',
				'post_name'    => 'what-we-offer',
				'post_title'   => __( 'What we offer (A2E Dropshipping)', 'a2e-mail' ),
				'post_content' => $step['body'],
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_subject', $step['subject'] );
			update_post_meta( $id, '_preheader', $step['preheader'] );
			update_post_meta( $id, '_category', 'promotions' );
		}
	}

	/**
	 * All templates for a dropdown.
	 *
	 * @return array id => [ name, subject, preheader, body ]
	 */
	public static function all() {
		$posts = get_posts( array( 'post_type' => TEMPLATE, 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
		$out   = array();
		foreach ( $posts as $p ) {
			$out[ $p->ID ] = array(
				'name'      => $p->post_title,
				'subject'   => (string) get_post_meta( $p->ID, '_subject', true ),
				'preheader' => (string) get_post_meta( $p->ID, '_preheader', true ),
				'category'  => (string) get_post_meta( $p->ID, '_category', true ),
				'body'      => $p->post_content,
			);
		}
		return $out;
	}

	/**
	 * Merge-tag values for a contact (missing values degrade gracefully).
	 *
	 * @param array $contact Contact row (may be partial for previews).
	 * @return array
	 */
	public static function merge_values( $contact ) {
		$name    = isset( $contact['name'] ) ? trim( $contact['name'] ) : '';
		$first   = $name ? preg_split( '/\s+/', $name )[0] : __( 'there', 'a2e-mail' );
		$token   = isset( $contact['token'] ) ? $contact['token'] : '';
		$on_list = Contacts::has_tag( $contact, Contacts::TAG_WEEKLY ) || ( isset( $contact['source'] ) && 'newsletter' === $contact['source'] );
		$blog_id = (int) get_option( 'page_for_posts' );
		return array(
			'first_name'      => $first,
			'name'            => $name ? $name : __( 'there', 'a2e-mail' ),
			'email'           => isset( $contact['email'] ) ? $contact['email'] : '',
			'business'        => setting( 'business_name' ),
			'whatsapp'        => setting( 'whatsapp' ),
			'phone'           => setting( 'phone' ) ? setting( 'phone' ) : setting( 'whatsapp' ),
			'contact_email'   => setting( 'contact_email' ),
			'site_url'        => home_url( '/' ),
			'audit_url'       => self::page_url( 'quote-request' ),
			'consultation_url' => self::page_url( 'consultation' ),
			'pricing_url'     => self::page_url( 'pricing' ),
			'tools_url'       => self::page_url( 'free-tools' ),
			'blog_url'        => $blog_id ? get_permalink( $blog_id ) : self::page_url( 'blog' ),
			'weekly_join_url' => ( Newsletter::enabled() && ! $on_list ) ? Newsletter::join_url( $token ) : '',
			'unsubscribe_url' => Unsubscribe::url( $token ),
		);
	}

	/**
	 * A page on this site by path (an A2E Prime page when the theme is active), else the homepage.
	 *
	 * @param string $path Page path.
	 * @return string
	 */
	public static function page_url( $path ) {
		$page = get_page_by_path( $path );
		if ( $page && 'publish' === $page->post_status ) {
			return get_permalink( $page );
		}
		return function_exists( 'a2e_url' ) ? a2e_url( $path ) : home_url( '/' );
	}

	/**
	 * Expand the blocks that are written as one tag: {{signature}} and {{weekly_invite}}.
	 *
	 * @param string $body   Body source.
	 * @param array  $values Merge values.
	 * @return string
	 */
	protected static function expand_blocks( $body, $values ) {
		$invite = '';
		if ( ! empty( $values['weekly_join_url'] ) ) {
			$invite = __( "## Free weekly tips\n\nWant practical ideas to grow your eBay and Shopify stores? Join our short weekly newsletter: one email a week, and you can leave any time.\n\n[[Yes, send me the weekly tips|{{weekly_join_url}}]]", 'a2e-mail' );
		}
		return str_replace( array( '{{signature}}', '{{weekly_invite}}' ), array( self::signature_text(), $invite ), $body );
	}

	/**
	 * Replace merge tags in a string.
	 *
	 * @param string $text   Text.
	 * @param array  $values Values.
	 * @return string
	 */
	public static function apply_merge( $text, $values ) {
		foreach ( $values as $key => $value ) {
			$text = str_replace( '{{' . $key . '}}', $value, $text );
		}
		// Any tag we do not know is removed so it never shows raw.
		$text = preg_replace( '/\{\{[a-z_]+\}\}/i', '', $text );
		return $text;
	}

	/**
	 * Convert the simple body format into email-safe HTML blocks (no shell).
	 *
	 * @param string $body   Body source.
	 * @param array  $values Merge values.
	 * @return string
	 */
	public static function body_to_html( $body, $values ) {
		$c    = self::palette();
		$body = self::expand_blocks( $body, $values );
		$body = self::apply_merge( $body, $values );
		$body = no_dashes( $body );
		$blocks = preg_split( "/\n\s*\n/", str_replace( array( "\r\n", "\r" ), "\n", trim( $body ) ) );
		$html   = '';
		foreach ( $blocks as $block ) {
			$block = trim( $block );
			if ( '' === $block ) {
				continue;
			}
			// Button: [[Label|url]]
			if ( preg_match( '/^\[\[(.+?)\|(.+?)\]\]$/', $block, $m ) ) {
				$html .= self::button( $m[1], $m[2] );
				continue;
			}
			// Image: ![alt](https://image-url)
			if ( preg_match( '/^!\[(.*?)\]\((https?:\/\/[^\s)]+)\)$/', $block, $m ) ) {
				$html .= '<img src="' . esc_url( $m[2] ) . '" alt="' . esc_attr( $m[1] ) . '" width="544" style="display:block;width:100%;max-width:544px;height:auto;border:0;border-radius:12px;margin:6px 0 14px;">';
				continue;
			}
			// Divider.
			if ( '---' === $block ) {
				$html .= '<hr style="border:0;border-top:1px solid ' . $c['line'] . ';margin:26px 0 4px;">';
				continue;
			}
			// Small heading.
			if ( 0 === strpos( $block, '### ' ) ) {
				$html .= '<h3 style="margin:20px 0 6px;font-size:16px;line-height:1.4;color:' . $c['ink'] . ';font-weight:700;">' . self::inline( trim( substr( $block, 4 ) ) ) . '</h3>';
				continue;
			}
			// Heading.
			if ( 0 === strpos( $block, '## ' ) ) {
				$html .= '<h2 style="margin:28px 0 10px;font-size:19px;line-height:1.3;color:' . $c['ink'] . ';font-weight:700;">' . esc_html( trim( substr( $block, 3 ) ) ) . '</h2>';
				continue;
			}
			// Bullet list.
			$lines = explode( "\n", $block );
			if ( '- ' === substr( ltrim( $lines[0] ), 0, 2 ) ) {
				$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:6px 0 14px;"><tbody>';
				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( '' === $line ) {
						continue;
					}
					$line  = ltrim( $line, '- ' );
					$html .= '<tr><td valign="top" style="width:22px;padding:3px 0;color:' . $c['good'] . ';font-size:15px;line-height:1.6;">&#10003;</td><td style="padding:3px 0;color:' . $c['muted'] . ';font-size:15px;line-height:1.6;">' . self::inline( $line ) . '</td></tr>';
				}
				$html .= '</tbody></table>';
				continue;
			}
			// Paragraph (single newlines become <br>).
			$html .= '<p style="margin:0 0 15px;color:' . $c['muted'] . ';font-size:15px;line-height:1.65;">' . nl2br( self::inline( $block ) ) . '</p>';
		}
		return $html;
	}

	/**
	 * Inline formatting: escape, then allow **bold** and plain links.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	protected static function inline( $text ) {
		$text = esc_html( $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$c    = self::palette();
		// [label](https://link) or a bare https://link.
		$text = preg_replace_callback(
			'#\[([^\]]+)\]\((https?://[^\s)]+)\)|(https?://[^\s<]+)#',
			function ( $m ) use ( $c ) {
				$url   = isset( $m[3] ) && '' !== $m[3] ? $m[3] : $m[2];
				$label = isset( $m[3] ) && '' !== $m[3] ? $m[3] : $m[1];
				return '<a href="' . $url . '" style="color:' . $c['brand'] . ';text-decoration:underline;">' . $label . '</a>';
			},
			$text
		);
		return $text;
	}

	/**
	 * A branded button.
	 *
	 * @param string $label Label.
	 * @param string $url   URL.
	 * @return string
	 */
	public static function button( $label, $url ) {
		$c = self::palette();
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 20px;"><tbody><tr><td align="center" bgcolor="' . $c['brand'] . '" style="border-radius:10px;background:linear-gradient(120deg,' . $c['brand2'] . ',' . $c['brand'] . ' 55%,' . $c['brand3'] . ');">'
			. '<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">' . esc_html( $label ) . '</a>'
			. '</td></tr></tbody></table>';
	}

	/**
	 * The signature block text (expanded before merge tags).
	 *
	 * @return string
	 */
	protected static function signature_text() {
		$founder = function_exists( 'a2e_opt' ) ? a2e_opt( 'founder_name' ) : '';
		$role    = function_exists( 'a2e_opt' ) ? a2e_opt( 'founder_role' ) : '';
		$lines   = array();
		$lines[] = __( 'Warm regards,', 'a2e-mail' );
		$lines[] = ( $founder ? $founder : '{{business}}' ) . ( $role ? "\n" . $role : '' );
		$lines[] = '{{business}}';
		return implode( "\n", $lines );
	}

	/**
	 * Wrap body HTML in the full branded email shell with header, footer, contact details and
	 * an unsubscribe link.
	 *
	 * @param string $body_html Rendered body HTML.
	 * @param array  $values    Merge values.
	 * @param string $preheader Hidden preview text.
	 * @return string
	 */
	public static function wrap( $body_html, $values, $preheader = '' ) {
		$c        = self::palette();
		$business = $values['business'];
		$logo     = '';
		$logo_id  = (int) get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$src = wp_get_attachment_image_url( $logo_id, 'medium' );
			if ( $src ) {
				$logo = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $business ) . '" height="40" style="height:40px;width:auto;display:block;border:0;">';
			}
		}
		if ( '' === $logo ) {
			$logo = '<span style="font-size:22px;font-weight:800;letter-spacing:-0.02em;color:#ffffff;">' . esc_html( $business ) . '</span>';
		}

		$pre = $preheader ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;">' . esc_html( no_dashes( $preheader ) ) . '</div>' : '';

		// Contact row.
		$contacts = array();
		if ( $values['whatsapp'] ) {
			$contacts[] = '<a href="' . esc_url( wa_link() ) . '" style="color:' . $c['brand'] . ';text-decoration:none;">' . esc_html__( 'WhatsApp', 'a2e-mail' ) . ' ' . esc_html( $values['whatsapp'] ) . '</a>';
		}
		if ( $values['phone'] && tel_link() ) {
			$contacts[] = '<a href="' . esc_url( tel_link() ) . '" style="color:' . $c['brand'] . ';text-decoration:none;">' . esc_html__( 'Call', 'a2e-mail' ) . ' ' . esc_html( $values['phone'] ) . '</a>';
		}
		if ( $values['contact_email'] ) {
			$contacts[] = '<a href="mailto:' . esc_attr( $values['contact_email'] ) . '" style="color:' . $c['brand'] . ';text-decoration:none;">' . esc_html( $values['contact_email'] ) . '</a>';
		}
		$contact_row = $contacts ? '<p style="margin:0 0 10px;color:' . $c['muted'] . ';font-size:13px;line-height:1.7;">' . implode( ' &nbsp;&bull;&nbsp; ', $contacts ) . '</p>' : '';

		$address = setting( 'postal_address' ) ? '<p style="margin:0 0 6px;color:' . $c['faint'] . ';font-size:12px;line-height:1.6;">' . esc_html( no_dashes( setting( 'postal_address' ) ) ) . '</p>' : '';
		$unsub   = '<p style="margin:0;color:' . $c['faint'] . ';font-size:12px;line-height:1.6;">'
			. esc_html__( 'You are receiving this because you contacted A2E or subscribed.', 'a2e-mail' ) . ' '
			. '<a href="' . esc_url( $values['unsubscribe_url'] ) . '" style="color:' . $c['faint'] . ';text-decoration:underline;">' . esc_html__( 'Unsubscribe', 'a2e-mail' ) . '</a></p>';

		ob_start();
		?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title><?php echo esc_html( $business ); ?></title></head>
<body style="margin:0;padding:0;background:<?php echo esc_attr( $c['bg'] ); ?>;-webkit-text-size-adjust:100%;"><?php echo $pre; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo esc_attr( $c['bg'] ); ?>;"><tbody><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background:<?php echo esc_attr( $c['surface'] ); ?>;border-radius:16px;overflow:hidden;border:1px solid <?php echo esc_attr( $c['line'] ); ?>;">
<tbody>
<tr><td style="padding:22px 28px;background:linear-gradient(120deg,<?php echo esc_attr( $c['brand2'] ); ?>,<?php echo esc_attr( $c['brand'] ); ?> 55%,<?php echo esc_attr( $c['brand3'] ); ?>);"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
<tr><td style="padding:30px 28px 10px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;"><?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
<tr><td style="padding:16px 28px 26px;border-top:1px solid <?php echo esc_attr( $c['line'] ); ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<?php echo $contact_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<p style="margin:0 0 6px;color:<?php echo esc_attr( $c['faint'] ); ?>;font-size:12px;line-height:1.6;">&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . $business ); ?></p>
<?php echo $address; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php echo $unsub; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</td></tr>
</tbody></table>
</td></tr></tbody></table>
</body></html>
		<?php
		return trim( ob_get_clean() );
	}

	/**
	 * Full rendered email (subject + html + text) for one contact.
	 *
	 * @param array $message subject, preheader, body.
	 * @param array $contact Contact row.
	 * @return array subject, html, text.
	 */
	public static function render( $message, $contact ) {
		$values    = self::merge_values( $contact );
		$subject   = (string) $message['subject'];
		if ( '' === trim( isset( $contact['name'] ) ? (string) $contact['name'] : '' ) ) {
			// No name: "{{first_name}}, here is..." becomes "Here is..." rather than "there, here is...".
			$subject = preg_replace( array( '/^\s*\{\{first_name\}\}\s*,?\s*/', '/\s*,?\s*\{\{first_name\}\}/' ), '', $subject );
			$subject = ucfirst( $subject );
		}
		$subject   = no_dashes( self::apply_merge( $subject, $values ) );
		$preheader = isset( $message['preheader'] ) ? (string) $message['preheader'] : '';
		$body_html = self::body_to_html( (string) $message['body'], $values );
		$html      = self::wrap( $body_html, $values, $preheader );
		$text      = self::to_text( (string) $message['body'], $values );
		return array( 'subject' => $subject, 'html' => $html, 'text' => $text );
	}

	/**
	 * Plain-text alternative.
	 *
	 * @param string $body   Body source.
	 * @param array  $values Merge values.
	 * @return string
	 */
	public static function to_text( $body, $values ) {
		$body = self::expand_blocks( $body, $values );
		$body = self::apply_merge( $body, $values );
		$body = no_dashes( $body );
		$body = preg_replace( '/^!\[.*?\]\(\S+\)\s*$/m', '', $body );
		$body = preg_replace( '/^#{2,3}\s+/m', '', $body );
		$body = preg_replace( '/\[\[(.+?)\|(.+?)\]\]/', '$1: $2', $body );
		$body = preg_replace( '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '$1 ($2)', $body );
		$body = preg_replace( "/\n{3,}/", "\n\n", $body );
		$body = preg_replace( '/\*\*(.+?)\*\*/', '$1', $body );
		$foot = "\n\n----\n" . sprintf( __( 'WhatsApp %1$s  |  Call %2$s  |  %3$s', 'a2e-mail' ), $values['whatsapp'], $values['phone'], $values['contact_email'] );
		$foot .= "\n" . sprintf( __( 'Unsubscribe: %s', 'a2e-mail' ), $values['unsubscribe_url'] );
		return trim( $body ) . $foot;
	}
}
