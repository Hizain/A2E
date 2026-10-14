<?php
/**
 * Admin: setup guide, activation notice, editor notices.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup guide page.
 */
function a2e_setup_menu() {
	add_submenu_page( 'a2e-settings', __( 'Setup guide', 'a2e' ), __( 'Setup guide', 'a2e' ), 'manage_options', 'a2e-setup', 'a2e_render_setup_page' );
}
add_action( 'admin_menu', 'a2e_setup_menu', 30 );

/**
 * Checklist items: [ done (bool), title, text, link, link label ].
 *
 * @return array
 */
function a2e_setup_items() {
	$whop_active = (bool) apply_filters( 'a2e_whop_connected', false );
	return array(
		array( '/%postname%/' === get_option( 'permalink_structure' ), __( 'Pretty permalinks', 'a2e' ), __( 'Settings → Permalinks must be “Post name” so every existing URL keeps working.', 'a2e' ), admin_url( 'options-permalink.php' ), __( 'Check permalinks', 'a2e' ) ),
		array( (bool) a2e_opt( 'whatsapp' ), __( 'WhatsApp & contact details', 'a2e' ), __( 'Used on every “Chat on WhatsApp” button and in Google’s business data.', 'a2e' ), admin_url( 'admin.php?page=a2e-settings&tab=business' ), __( 'Edit business details', 'a2e' ) ),
		array( true, __( 'Logos (light & dark)', 'a2e' ), __( 'The A2E logos are bundled. Upload different ones in Customize → Site Identity and Site Settings → Business (dark mode logo).', 'a2e' ), admin_url( 'customize.php?autofocus[section]=title_tagline' ), __( 'Open Customizer', 'a2e' ) ),
		array( $whop_active, __( 'Connect Whop for subscriptions', 'a2e' ), __( 'Install the A2E Whop Sync plugin, paste your Whop API key once, then create plans in WordPress. They are created on Whop and appear on the pricing pages automatically.', 'a2e' ), function_exists( 'A2E\WhopSync\settings_url' ) ? call_user_func( 'A2E\WhopSync\settings_url' ) : admin_url( 'plugins.php' ), __( 'Set up Whop', 'a2e' ) ),
		array( (bool) a2e_opt( 'gsc_verify' ) || a2e_seo_plugin_active(), __( 'Google Search Console', 'a2e' ), __( 'Verify the site and submit /wp-sitemap.xml so Google indexes the new tools and pages quickly.', 'a2e' ), admin_url( 'admin.php?page=a2e-settings&tab=tracking' ), __( 'Add verification code', 'a2e' ) ),
		array( (bool) a2e_opt( 'ga4_id' ), __( 'Google Analytics 4 (optional)', 'a2e' ), __( 'See which tools and pages bring visitors and leads.', 'a2e' ), admin_url( 'admin.php?page=a2e-settings&tab=tracking' ), __( 'Add Measurement ID', 'a2e' ) ),
		array( (bool) a2e_opt( 'founder_photo' ), __( 'Founder photo (recommended)', 'a2e' ), __( 'A real face next to the founder note builds trust and helps Google’s “experience” signals.', 'a2e' ), admin_url( 'admin.php?page=a2e-settings&tab=business' ), __( 'Upload photo', 'a2e' ) ),
	);
}

/**
 * Render setup page.
 */
function a2e_render_setup_page() {
	$items = a2e_setup_items();
	$done  = count( array_filter( wp_list_pluck( $items, 0 ) ) );
	?>
	<div class="wrap a2e-admin">
		<h1 class="a2e-admin__title"><?php esc_html_e( 'A2E Prime setup guide', 'a2e' ); ?></h1>
		<p class="a2e-admin__lead"><?php /* translators: 1: done 2: total */ printf( esc_html__( '%1$d of %2$d done. Everything else (pages, tools, service guides and redirects) is already in place.', 'a2e' ), (int) $done, count( $items ) ); ?></p>
		<ol class="a2e-setup">
			<?php foreach ( $items as $item ) : ?>
				<li class="a2e-setup__item<?php echo $item[0] ? ' is-done' : ''; ?>">
					<span class="a2e-setup__check" aria-hidden="true"><?php echo $item[0] ? '✓' : ''; ?></span>
					<div><strong><?php echo esc_html( $item[1] ); ?></strong><p><?php echo esc_html( $item[2] ); ?></p></div>
					<a class="button" href="<?php echo esc_url( $item[3] ); ?>"><?php echo esc_html( $item[4] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ol>
		<h2><?php esc_html_e( 'Writing blog posts that rank', 'a2e' ); ?></h2>
		<ul class="ul-disc">
			<li><?php esc_html_e( 'Use one clear H1 (the post title) and H2/H3 headings. The theme builds a table of contents from them automatically.', 'a2e' ); ?></li>
			<li><?php esc_html_e( 'Fill in “A2E page header & SEO” below the editor: a ~60 character SEO title and a ~155 character description.', 'a2e' ); ?></li>
			<li><?php esc_html_e( 'Link to a free tool in every post (e.g. the fee or profit calculator). It keeps readers on the site and turns them into leads.', 'a2e' ); ?></li>
			<li><?php esc_html_e( 'Add a featured image (1600×840). It is used for the card, the article header and social sharing.', 'a2e' ); ?></li>
		</ul>
	</div>
	<?php
}

/**
 * Notice after activation.
 */
function a2e_activation_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'a2e_activated' ) ) {
		return;
	}
	delete_transient( 'a2e_activated' );
	echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'A2E Prime is active.', 'a2e' ) . '</strong> ' . esc_html__( 'All pages, 13 free tools and service guides are ready, and every existing URL still works.', 'a2e' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=a2e-setup' ) ) . '">' . esc_html__( 'Finish setup →', 'a2e' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'a2e_activation_notice' );

/**
 * Remind editors that designed pages and service guides take their sections from the theme.
 */
function a2e_designed_editor_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}
	$post = get_post();
	if ( ! $post || ! ( a2e_is_designed_page( $post ) || 'a2e_service' === $post->post_type ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p>' . esc_html__( 'This page uses an A2E designed layout. Change its headline and SEO in the “A2E page header & SEO” box below; site-wide texts live in A2E → Site Settings. Content you add here appears under the layout when “also show the editor content” is ticked.', 'a2e' ) . '</p></div>';
}
add_action( 'admin_notices', 'a2e_designed_editor_notice' );

/**
 * Shortcut links on the dashboard "At a glance" widget.
 *
 * @param array $items Items.
 * @return array
 */
function a2e_glance_items( $items ) {
	$leads   = wp_count_posts( 'a2e_lead' );
	$count   = isset( $leads->private ) ? (int) $leads->private : 0;
	/* translators: %d: number of leads */
	$items[] = '<a class="a2e-glance-leads" href="' . esc_url( admin_url( 'edit.php?post_type=a2e_lead' ) ) . '">' . esc_html( sprintf( _n( '%d lead', '%d leads', $count, 'a2e' ), $count ) ) . '</a>';
	return $items;
}
add_filter( 'dashboard_glance_items', 'a2e_glance_items' );
