<?php
/**
 * A2E → Site Settings: business details, homepage copy, fallback plans, eBay fee rates,
 * integrations and supplier logos — all editable without touching code.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'A2E_OPTION', 'a2e_prime' );

/**
 * Settings schema grouped into admin tabs.
 * Field: key => array( label, type, default, help ). Types: text, textarea, url, email,
 * image, toggle, lines, select (help = options array).
 *
 * @return array
 */
function a2e_settings_schema() {
	static $schema = null;
	if ( null !== $schema ) {
		return $schema;
	}

	$brand_fields = array();
	foreach ( a2e_brands() as $key => $brand ) {
		$brand_fields[ 'brand_logo_' . $key ] = array( $brand[0] . ' logo', 'image', '', '' );
	}

	$schema = array(
		'business' => array(
			'title'  => __( 'Business', 'a2e' ),
			'desc'   => __( 'Contact details and trust numbers used across the whole site and in structured data for Google.', 'a2e' ),
			'fields' => array(
				'whatsapp'      => array( 'WhatsApp number (with country code)', 'text', '+12067764962', 'Used for every “Chat on WhatsApp” button.' ),
				'email'         => array( 'Public email', 'email', '', 'Shown on the contact page. Leave empty to hide.' ),
				'lead_email'    => array( 'Send new leads to', 'email', '', 'Where quote, consultation and contact form messages are emailed. Defaults to the site admin email.' ),
				'fiverr_url'    => array( 'Fiverr profile URL', 'url', 'https://www.fiverr.com/hizain', '' ),
				'rating'        => array( 'Average rating', 'text', '5.0', '' ),
				'reviews'       => array( 'Number of reviews', 'text', '528', '' ),
				'since'         => array( 'In business since (year)', 'text', '2018', '' ),
				'response_time' => array( 'Reply-time promise', 'text', 'We reply within one business day — usually much sooner.', 'Keep it honest: this appears next to every form.' ),
				'founder_name'  => array( 'Founder name', 'text', 'Zain Ahmad', '' ),
				'founder_role'  => array( 'Founder title', 'text', 'Founder & lead strategist', '' ),
				'founder_photo' => array( 'Founder photo', 'image', '', 'Square photo, at least 300×300px. Initials are shown when empty.' ),
				'founder_quote' => array( 'Founder note', 'textarea', 'I started A2E because I watched too many sellers burn months on products that never sold. We do the slow, careful work — research, listings, daily checks — so your store grows on data, not luck. If something will not work for you, we will tell you before you spend a cent.', '' ),
				'logo_dark'     => array( 'Logo for dark mode', 'image', '', 'Light/white artwork for dark backgrounds. The normal logo is set in Appearance → Customize → Site Identity.' ),
				'og_image'      => array( 'Default social sharing image', 'image', '', '1200×630px. Used when a page has no featured image.' ),
				'social_linkedin'  => array( 'LinkedIn URL', 'url', '', '' ),
				'social_facebook'  => array( 'Facebook URL', 'url', '', '' ),
				'social_instagram' => array( 'Instagram URL', 'url', '', '' ),
				'social_youtube'   => array( 'YouTube URL', 'url', '', '' ),
				'social_x'         => array( 'X (Twitter) URL', 'url', '', '' ),
			),
		),
		'home'     => array(
			'title'  => __( 'Homepage', 'a2e' ),
			'desc'   => __( 'Headlines, buttons and numbers on the homepage. Leave a field empty to restore its default.', 'a2e' ),
			'fields' => array(
				'use_wp_menus'     => array( 'Use my WordPress menus instead of the built-in mega menu', 'toggle', '0', 'Off = the designed mega menu (recommended). On = menus assigned in Appearance → Menus.' ),
				'announcement'     => array( 'Top announcement bar', 'text', 'New: 13 free eBay seller tools — fee, profit & title calculators', 'Leave empty to hide the bar.' ),
				'announcement_url' => array( 'Announcement link', 'url', '/free-tools/', '' ),
				'hero_kicker'      => array( 'Hero — small label', 'text', 'Research · Listings · Daily store management', '' ),
				'hero_title'       => array( 'Hero — headline', 'text', 'Amazon to eBay dropshipping', '' ),
				'hero_accent'      => array( 'Hero — highlighted ending', 'text', 'that turns listings into profit.', 'Shown in the gradient serif style.' ),
				'hero_lead'        => array( 'Hero — supporting text', 'textarea', 'We find products that sell, write eBay listings that rank, and run your store every day — sourcing from Amazon, Walmart and 20+ trusted suppliers. One expert team. One simple monthly plan. Cancel anytime.', '' ),
				'hero_primary_label'   => array( 'Hero — main button', 'text', 'See plans & pricing', '' ),
				'hero_primary_url'     => array( 'Hero — main button link', 'url', '/pricing/', '' ),
				'hero_secondary_label' => array( 'Hero — second button', 'text', 'Get a free store audit', '' ),
				'hero_secondary_url'   => array( 'Hero — second button link', 'url', '/quote-request/', '' ),
				'hero_points'      => array( 'Hero — small promises (one per line)', 'lines', "No long-term contract\nYour eBay account stays 100% yours\nTalk to a real person on WhatsApp", '' ),
				'stat_1_number'    => array( 'Stat 1 — number', 'text', '528', '' ),
				'stat_1_label'     => array( 'Stat 1 — label', 'text', 'five-star client reviews', '' ),
				'stat_2_number'    => array( 'Stat 2 — number', 'text', '25K+', '' ),
				'stat_2_label'     => array( 'Stat 2 — label', 'text', 'products researched & listed', '' ),
				'stat_3_number'    => array( 'Stat 3 — number', 'text', '500+', '' ),
				'stat_3_label'     => array( 'Stat 3 — label', 'text', 'stores & projects supported', '' ),
				'stat_4_number'    => array( 'Stat 4 — number', 'text', '8+', '' ),
				'stat_4_label'     => array( 'Stat 4 — label', 'text', 'years selling on eBay', '' ),
				'final_title'      => array( 'Closing section — headline', 'text', 'Ready to make eBay your best sales channel?', '' ),
				'final_text'       => array( 'Closing section — text', 'textarea', 'Get a free store audit with a clear plan and a fixed price. No pressure, no jargon — and if we are not the right fit, we will tell you.', '' ),
				'show_simulator'   => array( 'Show the profit simulator', 'toggle', '1', '' ),
				'show_tools'       => array( 'Show the free tools section', 'toggle', '1', '' ),
				'show_founder'     => array( 'Show the founder note', 'toggle', '1', '' ),
				'show_blog'        => array( 'Show latest blog posts', 'toggle', '1', '' ),
			),
		),
		'faq'      => array(
			'title'  => __( 'FAQ', 'a2e' ),
			'desc'   => __( 'Questions shown on the homepage, pricing and services pages (and marked up for Google). Leave a question empty to hide it.', 'a2e' ),
			'fields' => array(
				'faq_1_q' => array( 'Question 1', 'text', 'What exactly do you do for my eBay store?', '' ),
				'faq_1_a' => array( 'Answer 1', 'textarea', 'We handle the work that makes a dropshipping store profitable: product research checked against real eBay fees, SEO listings with complete item specifics, store setup, stock and price monitoring, order routines, buyer messages and account health. You choose a one-time listing package or a monthly plan where we run the store with you.', '' ),
				'faq_2_q' => array( 'Question 2', 'text', 'Is Amazon to eBay dropshipping allowed?', '' ),
				'faq_2_a' => array( 'Answer 2', 'textarea', 'eBay allows dropshipping, but its policy makes you responsible for delivery and does not allow buying an item from another retailer or marketplace to have it shipped straight to your buyer. That is why we build a multi-supplier sourcing and fulfilment workflow — wholesale and dropship suppliers, not just retail checkouts — and review your setup against the current rules during onboarding so your account stays healthy.', '' ),
				'faq_3_q' => array( 'Question 3', 'text', 'Do I need an existing eBay account?', '' ),
				'faq_3_a' => array( 'Answer 3', 'textarea', 'No. New sellers start with store setup and a first listing batch. Established sellers can go straight to listings, a store clean-up or monthly management. We plan around your selling limits either way.', '' ),
				'faq_4_q' => array( 'Question 4', 'text', 'How does billing work — can I cancel?', '' ),
				'faq_4_a' => array( 'Answer 4', 'textarea', 'Monthly plans are billed securely through Whop. You can update your card or cancel future renewals from your account at any time — there is no long-term contract. One-time packages are charged once.', '' ),
				'faq_5_q' => array( 'Question 5', 'text', 'Can you guarantee sales or profit?', '' ),
				'faq_5_a' => array( 'Answer 5', 'textarea', 'No honest service can — results depend on your account history, product choices, pricing, competition and suppliers. What we guarantee is careful, transparent work, profit checks before anything is listed, and a clear monthly report.', '' ),
				'faq_6_q' => array( 'Question 6', 'text', 'Will I keep full control of my account?', '' ),
				'faq_6_a' => array( 'Answer 6', 'textarea', 'Always. Your eBay account, listings, customers and profits stay yours. We work with the access you choose to give — ideally eBay’s own team permissions — and you can remove it whenever you like.', '' ),
				'faq_7_q' => array( 'Question 7', 'text', 'How fast will my listings go live?', '' ),
				'faq_7_a' => array( 'Answer 7', 'textarea', 'You get a written delivery schedule before any work starts. Most first batches start going live within the first week, depending on your plan volume and how ready your store is.', '' ),
				'faq_8_q' => array( 'Question 8', 'text', 'Can I talk to someone before I pay?', '' ),
				'faq_8_a' => array( 'Answer 8', 'textarea', 'Of course. Request a free store audit or message us on WhatsApp — a real person will look at your store and recommend the right plan, even if it is the smallest one.', '' ),
			),
		),
		'pricing'  => array(
			'title'  => __( 'Plans (fallback)', 'a2e' ),
			'desc'   => __( 'When the A2E Whop Sync plugin has published plans, those are shown automatically and these are ignored. Until then, these plans appear and their buttons go to the link you set (or the quote form).', 'a2e' ),
			'fields' => array(
				'plan_featured'   => array( 'Highlighted plan', 'select', '2', array( '1' => 'Plan 1', '2' => 'Plan 2', '3' => 'Plan 3' ) ),
				'plan_1_badge'    => array( 'Plan 1 — badge', 'text', 'Starter', '' ),
				'plan_1_name'     => array( 'Plan 1 — name', 'text', '100 Researched Listings', '' ),
				'plan_1_price'    => array( 'Plan 1 — price', 'text', '$99', '' ),
				'plan_1_period'   => array( 'Plan 1 — period', 'text', 'one-time', '' ),
				'plan_1_note'     => array( 'Plan 1 — note', 'text', 'Pay once. No subscription.', '' ),
				'plan_1_desc'     => array( 'Plan 1 — description', 'textarea', 'A focused first batch: researched products listed on your eBay store with SEO-ready content.', '' ),
				'plan_1_features' => array( 'Plan 1 — features', 'lines', "100 researched products\nProfit checked after eBay fees\nSEO titles & full item specifics\nClean, mobile-friendly descriptions\nListed on your store + delivery report", '' ),
				'plan_1_button'   => array( 'Plan 1 — button', 'text', 'Get started', '' ),
				'plan_1_url'      => array( 'Plan 1 — checkout link', 'url', '', 'Optional. Leave empty to send buyers to the quote form.' ),
				'plan_2_badge'    => array( 'Plan 2 — badge', 'text', 'Most popular', '' ),
				'plan_2_name'     => array( 'Plan 2 — name', 'text', 'Growth Management', '' ),
				'plan_2_price'    => array( 'Plan 2 — price', 'text', '$200', '' ),
				'plan_2_period'   => array( 'Plan 2 — period', 'text', '/month', '' ),
				'plan_2_note'     => array( 'Plan 2 — note', 'text', '+ $50 one-time store audit & setup', '' ),
				'plan_2_desc'     => array( 'Plan 2 — description', 'textarea', 'Fresh listings every month plus hands-on management of one eBay account.', '' ),
				'plan_2_features' => array( 'Plan 2 — features', 'lines', "150 researched listings every month\nDaily store & account management\nOrder, stock and price checks\nBuyer messages & issue handling\nMonthly performance report\nPriority WhatsApp support", '' ),
				'plan_2_button'   => array( 'Plan 2 — button', 'text', 'Start Growth plan', '' ),
				'plan_2_url'      => array( 'Plan 2 — checkout link', 'url', '', '' ),
				'plan_3_badge'    => array( 'Plan 3 — badge', 'text', 'Best value', '' ),
				'plan_3_name'     => array( 'Plan 3 — name', 'text', 'Scale Management', '' ),
				'plan_3_price'    => array( 'Plan 3 — price', 'text', '$400', '' ),
				'plan_3_period'   => array( 'Plan 3 — period', 'text', '/month', '' ),
				'plan_3_note'     => array( 'Plan 3 — note', 'text', 'No setup fee', '' ),
				'plan_3_desc'     => array( 'Plan 3 — description', 'textarea', 'High-volume listings and complete A-to-Z management for stores ready to scale.', '' ),
				'plan_3_features' => array( 'Plan 3 — features', 'lines', "500 researched listings every month\nComplete A-to-Z store management\nSupplier & inventory monitoring\nGrowth priorities every month\nMonthly strategy call & report\nNo setup fee", '' ),
				'plan_3_button'   => array( 'Plan 3 — button', 'text', 'Start Scale plan', '' ),
				'plan_3_url'      => array( 'Plan 3 — checkout link', 'url', '', '' ),
				'custom_title'    => array( 'Custom plan — headline', 'text', 'Need more than 500 listings or a custom mix?', '' ),
				'custom_text'     => array( 'Custom plan — text', 'textarea', 'Tell us your listing volume and what you want managed. We will build a plan around your store instead of forcing you into a package.', '' ),
			),
		),
		'fees'     => array(
			'title'  => __( 'eBay fees', 'a2e' ),
			'desc'   => __( 'Rates used by every free calculator (eBay US, most categories). If eBay changes its fees, update them here and all tools update at once.', 'a2e' ),
			'fields' => array(
				'fee_checked'       => array( 'Rates last checked (shown to visitors)', 'text', 'October 2026', '' ),
				'fee_rate'          => array( 'Final value fee — most categories (%)', 'text', '13.6', '' ),
				'fee_rate_media'    => array( 'Final value fee — books, movies & music (%)', 'text', '15.3', '' ),
				'fee_rate_guitars'  => array( 'Final value fee — guitars & basses (%)', 'text', '6.7', '' ),
				'fee_rate_sneakers' => array( 'Final value fee — athletic shoes $150+ (%)', 'text', '8', '' ),
				'fee_threshold'     => array( 'Rate applies up to (USD)', 'text', '7500', '' ),
				'fee_rate_above'    => array( 'Rate above that amount (%)', 'text', '2.35', '' ),
				'fee_order_small'   => array( 'Per-order fee — small orders (USD)', 'text', '0.30', '' ),
				'fee_order_large'   => array( 'Per-order fee — larger orders (USD)', 'text', '0.40', '' ),
				'fee_order_limit'   => array( 'Small-order limit (USD)', 'text', '10', '' ),
				'fee_international' => array( 'International fee (%)', 'text', '1.65', '' ),
				'fee_below'         => array( 'Extra fee for Below Standard sellers (%)', 'text', '6', '' ),
				'fee_top_rated'     => array( 'Top Rated Plus discount (%)', 'text', '10', '' ),
			),
		),
		'tracking' => array(
			'title'  => __( 'Search & analytics', 'a2e' ),
			'desc'   => __( 'Paste only the codes — the theme writes the tags. Leave empty if an SEO or analytics plugin already handles this.', 'a2e' ),
			'fields' => array(
				'ga4_id'     => array( 'Google Analytics 4 Measurement ID', 'text', '', 'Looks like G-XXXXXXXXXX.' ),
				'gsc_verify' => array( 'Google Search Console verification code', 'text', '', 'Only the content="…" value.' ),
				'bing_verify' => array( 'Bing Webmaster verification code', 'text', '', '' ),
			),
		),
		'logos'    => array(
			'title'  => __( 'Supplier logos', 'a2e' ),
			'desc'   => __( 'By default supplier and marketplace names are shown as typeset wordmarks in brand colours. If you hold the rights to an official logo file, upload it here to replace the typeset version (transparent PNG or SVG, about 160×48px).', 'a2e' ),
			'fields' => $brand_fields,
		),
	);

	$schema = apply_filters( 'a2e_settings_schema', $schema );
	return $schema;
}

/**
 * Flat defaults.
 *
 * @return array
 */
function a2e_settings_defaults() {
	$defaults = array();
	foreach ( a2e_settings_schema() as $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			$defaults[ $key ] = $field[2];
		}
	}
	return $defaults;
}

/**
 * Read one setting. Empty saved text falls back to the default so a cleared field restores it,
 * except optional fields whose default is already empty.
 *
 * @param string $key Key.
 * @return string
 */
function a2e_opt( $key ) {
	static $defaults = null, $types = null;
	if ( null === $defaults ) {
		$defaults = array();
		$types    = array();
		foreach ( a2e_settings_schema() as $group ) {
			foreach ( $group['fields'] as $k => $field ) {
				$defaults[ $k ] = $field[2];
				$types[ $k ]    = $field[1];
			}
		}
	}
	if ( ! isset( $GLOBALS['a2e_opt_cache'] ) ) {
		$saved                    = get_option( A2E_OPTION, array() );
		$GLOBALS['a2e_opt_cache'] = is_array( $saved ) ? $saved : array();
	}
	$saved   = $GLOBALS['a2e_opt_cache'];
	$value   = array_key_exists( $key, $saved ) ? $saved[ $key ] : null;
	$default = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '';

	if ( isset( $types[ $key ] ) && 'toggle' === $types[ $key ] ) {
		return null === $value ? $default : (string) $value;
	}
	if ( null === $value || '' === trim( (string) $value ) ) {
		return $default;
	}
	return (string) $value;
}

/**
 * Drop the per-request settings cache whenever the option changes.
 */
function a2e_flush_opt_cache() {
	unset( $GLOBALS['a2e_opt_cache'] );
}
add_action( 'update_option_' . A2E_OPTION, 'a2e_flush_opt_cache' );
add_action( 'add_option_' . A2E_OPTION, 'a2e_flush_opt_cache' );

/**
 * Sanitize the whole settings array on save.
 *
 * @param array $input Raw input.
 * @return array
 */
function a2e_sanitize_settings( $input ) {
	$input  = is_array( $input ) ? $input : array();
	$stored = get_option( A2E_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();
	$tab    = isset( $input['__tab'] ) ? sanitize_key( $input['__tab'] ) : '';
	$schema = a2e_settings_schema();
	$clean  = $stored;

	foreach ( $schema as $group_key => $group ) {
		if ( $tab && $tab !== $group_key ) {
			continue; // Only the submitted tab is posted; keep the others untouched.
		}
		foreach ( $group['fields'] as $key => $field ) {
			$type  = $field[1];
			$value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
			switch ( $type ) {
				case 'toggle':
					$clean[ $key ] = empty( $input[ $key ] ) ? '0' : '1';
					break;
				case 'url':
				case 'image':
					$value         = trim( (string) $value );
					$clean[ $key ] = ( 0 === strpos( $value, '/' ) && 0 !== strpos( $value, '//' ) ) ? sanitize_text_field( $value ) : esc_url_raw( $value );
					break;
				case 'email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'textarea':
				case 'lines':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'select':
					$options       = is_array( $field[3] ) ? $field[3] : array();
					$clean[ $key ] = isset( $options[ $value ] ) ? (string) $value : (string) $field[2];
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
	}
	return $clean;
}

/**
 * Register the setting.
 */
function a2e_register_settings() {
	register_setting(
		'a2e_settings',
		A2E_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'a2e_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'a2e_register_settings' );

/**
 * Admin menu: top-level "A2E" menu.
 */
function a2e_settings_menu() {
	add_menu_page( __( 'A2E Site Settings', 'a2e' ), __( 'A2E', 'a2e' ), 'manage_options', 'a2e-settings', 'a2e_render_settings_page', 'dashicons-chart-area', 3 );
	add_submenu_page( 'a2e-settings', __( 'A2E Site Settings', 'a2e' ), __( 'Site Settings', 'a2e' ), 'manage_options', 'a2e-settings', 'a2e_render_settings_page' );
}
add_action( 'admin_menu', 'a2e_settings_menu' );

/**
 * Render the settings page.
 */
function a2e_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$schema = a2e_settings_schema();
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'business'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( $schema[ $tab ] ) ) {
		$tab = 'business';
	}
	$saved = get_option( A2E_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();
	$group = $schema[ $tab ];
	wp_enqueue_media();
	?>
	<div class="wrap a2e-admin">
		<h1 class="a2e-admin__title"><?php esc_html_e( 'A2E Site Settings', 'a2e' ); ?></h1>
		<p class="a2e-admin__lead"><?php esc_html_e( 'Everything visitors see — contact details, homepage copy, plans and calculator rates — is editable here.', 'a2e' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site ↗', 'a2e' ); ?></a></p>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $schema as $key => $g ) : ?>
				<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'a2e-settings', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $g['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php settings_errors(); ?>
		<form method="post" action="options.php" class="a2e-admin__form">
			<?php settings_fields( 'a2e_settings' ); ?>
			<input type="hidden" name="<?php echo esc_attr( A2E_OPTION ); ?>[__tab]" value="<?php echo esc_attr( $tab ); ?>">
			<?php if ( ! empty( $group['desc'] ) ) : ?><p class="description a2e-admin__desc"><?php echo esc_html( $group['desc'] ); ?></p><?php endif; ?>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $group['fields'] as $key => $field ) :
					list( $label, $type, $default ) = $field;
					$help  = isset( $field[3] ) && is_string( $field[3] ) ? $field[3] : '';
					$name  = A2E_OPTION . '[' . $key . ']';
					$id    = 'a2e-' . $key;
					$value = array_key_exists( $key, $saved ) ? (string) $saved[ $key ] : ( 'toggle' === $type ? (string) $default : '' );
					?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php if ( 'toggle' === $type ) : ?>
								<label><input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( '1', $value ); ?>> <?php esc_html_e( 'On', 'a2e' ); ?></label>
							<?php elseif ( 'textarea' === $type || 'lines' === $type ) : ?>
								<textarea class="large-text" rows="<?php echo 'lines' === $type ? 6 : 4; ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $default ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
							<?php elseif ( 'select' === $type ) : ?>
								<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
									<?php foreach ( (array) $field[3] as $opt_value => $opt_label ) : ?>
										<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( '' === $value ? (string) $default : $value, (string) $opt_value ); ?>><?php echo esc_html( $opt_label ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php elseif ( 'image' === $type ) : ?>
								<div class="a2e-media">
									<input type="text" class="regular-text a2e-media__url" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://">
									<button type="button" class="button a2e-media__pick"><?php esc_html_e( 'Choose image', 'a2e' ); ?></button>
									<span class="a2e-media__preview"><?php echo $value ? '<img src="' . esc_url( $value ) . '" alt="">' : ''; ?></span>
								</div>
							<?php else : ?>
								<input type="<?php echo 'email' === $type ? 'email' : ( 'url' === $type ? 'text' : 'text' ); ?>" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $default ); ?>">
							<?php endif; ?>
							<?php if ( $help ) : ?><p class="description"><?php echo esc_html( $help ); ?></p><?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( __( 'Save changes', 'a2e' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * One-time import of business details, logos, plans and tracking codes from the previous
 * A2E theme so switching themes keeps the live site's settings.
 */
function a2e_migrate_previous_theme() {
	if ( get_option( 'a2e_prime_migrated' ) ) {
		return;
	}
	update_option( 'a2e_prime_migrated', A2E_VERSION, false );

	$settings = get_option( A2E_OPTION, array() );
	$settings = is_array( $settings ) ? $settings : array();
	$old_mods = array();
	foreach ( array( 'a2e-dropshipping-authority-blue', 'a2e-dropshipping-pro', 'a2e-dropshipping' ) as $slug ) {
		$mods = get_option( 'theme_mods_' . $slug );
		if ( is_array( $mods ) ) {
			$old_mods = $mods;
			break;
		}
	}

	$map = array(
		'a2e_whatsapp'          => 'whatsapp',
		'a2e_email'             => 'email',
		'a2e_fiverr_url'        => 'fiverr_url',
		'a2e_rating'            => 'rating',
		'a2e_reviews'           => 'reviews',
		'a2e_ga4_id'            => 'ga4_id',
		'a2e_gsc_verification'  => 'gsc_verify',
		'a2e_bing_verification' => 'bing_verify',
		'a2e_social_linkedin'   => 'social_linkedin',
		'a2e_social_facebook'   => 'social_facebook',
		'a2e_social_instagram'  => 'social_instagram',
		'a2e_social_youtube'    => 'social_youtube',
		'a2e_social_x'          => 'social_x',
	);
	foreach ( $map as $old => $new ) {
		if ( ! empty( $old_mods[ $old ] ) && empty( $settings[ $new ] ) && is_string( $old_mods[ $old ] ) ) {
			$settings[ $new ] = $old_mods[ $old ];
		}
	}
	if ( ! empty( $old_mods['a2e_header_logo_dark'] ) && empty( $settings['logo_dark'] ) ) {
		$url = wp_get_attachment_image_url( absint( $old_mods['a2e_header_logo_dark'] ), 'full' );
		if ( $url ) {
			$settings['logo_dark'] = $url;
		}
	}
	if ( ! empty( $old_mods['a2e_og_image'] ) && empty( $settings['og_image'] ) ) {
		$url = is_numeric( $old_mods['a2e_og_image'] ) ? wp_get_attachment_image_url( absint( $old_mods['a2e_og_image'] ), 'full' ) : $old_mods['a2e_og_image'];
		if ( $url ) {
			$settings['og_image'] = $url;
		}
	}
	if ( ! empty( $old_mods['custom_logo'] ) && ! get_theme_mod( 'custom_logo' ) ) {
		set_theme_mod( 'custom_logo', absint( $old_mods['custom_logo'] ) );
	}

	// Plans and fee rates edited in the previous theme's Site Editor.
	$old = get_option( 'a2e_site_v5' );
	if ( is_array( $old ) ) {
		for ( $i = 1; $i <= 3; $i++ ) {
			foreach ( array( 'name', 'price', 'period', 'note', 'desc', 'features', 'button', 'url' ) as $part ) {
				$from = "pricing_{$i}_{$part}";
				$to   = "plan_{$i}_{$part}";
				if ( isset( $old[ $from ] ) && '' !== trim( (string) $old[ $from ] ) && empty( $settings[ $to ] ) ) {
					$settings[ $to ] = $old[ $from ];
				}
			}
			if ( isset( $old[ "pricing_{$i}_label" ] ) && '' !== trim( (string) $old[ "pricing_{$i}_label" ] ) && empty( $settings[ "plan_{$i}_badge" ] ) ) {
				$settings[ "plan_{$i}_badge" ] = $old[ "pricing_{$i}_label" ];
			}
		}
		$fees = array(
			'fee_rate_default'   => 'fee_rate',
			'fee_rate_media'     => 'fee_rate_media',
			'fee_rate_guitars'   => 'fee_rate_guitars',
			'fee_rate_sneakers'  => 'fee_rate_sneakers',
			'fee_threshold'      => 'fee_threshold',
			'fee_rate_above'     => 'fee_rate_above',
			'fee_order_small'    => 'fee_order_small',
			'fee_order_large'    => 'fee_order_large',
			'fee_order_limit'    => 'fee_order_limit',
			'fee_international'  => 'fee_international',
			'fee_below_standard' => 'fee_below',
			'fee_top_rated'      => 'fee_top_rated',
		);
		foreach ( $fees as $from => $to ) {
			if ( isset( $old[ $from ] ) && '' !== trim( (string) $old[ $from ] ) && empty( $settings[ $to ] ) ) {
				$settings[ $to ] = $old[ $from ];
			}
		}
	}

	update_option( A2E_OPTION, $settings );
}
add_action( 'after_switch_theme', 'a2e_migrate_previous_theme', 5 );
add_action( 'admin_init', 'a2e_migrate_previous_theme', 5 );
