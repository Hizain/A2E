<?php
/**
 * Creates any page, tool page or service guide the site needs that does not exist yet.
 *
 * Safe to run repeatedly: existing content is never changed or deleted, so everything on
 * the live site (pages, posts, reviews, portfolio) stays exactly as it is.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pages to make sure exist: slug => [ title, content ].
 *
 * @return array
 */
function a2e_seed_pages() {
	$p = function ( $text ) {
		return '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
	};
	$h = function ( $text ) {
		return '<!-- wp:heading --><h2 class="wp-block-heading">' . $text . '</h2><!-- /wp:heading -->';
	};
	return array(
		'home'                                  => array( 'Home', '' ),
		'pricing'                               => array( 'Amazon to eBay Dropshipping Pricing', '' ),
		'subscription-plans'                    => array( 'Amazon to eBay Dropshipping Subscription Plans', '' ),
		'amazon-to-ebay-dropshipping-service'   => array( 'Amazon to eBay Dropshipping Service', '' ),
		'free-tools'                            => array( 'Free eBay Seller Tools', '' ),
		'contact'                               => array( 'Contact A2E Dropshipping', '' ),
		'quote-request'                         => array( 'Request a Free Store Audit', '' ),
		'consultation'                          => array( 'Book an Amazon to eBay Dropshipping Consultation', '' ),
		'portfolio'                             => array( 'Portfolio', '' ),
		'amazon-to-ebay-dropshipping-checklist' => array( 'Free Amazon to eBay Dropshipping Checklist', '' ),
		'about'                                 => array( 'About A2E Dropshipping', '' ),
		'account'                               => array( 'Client Area', '' ),
		'blog'                                  => array( 'Blog', '' ),
		'privacy-policy'                        => array(
			'Privacy Policy',
			implode(
				"\n\n",
				array(
					$p( 'A2E Dropshipping (“we”, “us”) respects your privacy. This policy explains what information we collect when you use this website, request a quote, subscribe to our newsletter or buy a service, and how we use it.' ),
					$h( 'Information we collect' ),
					$p( 'We collect the details you give us (such as your name, email address, phone or WhatsApp number, store details and messages) when you fill in a form, subscribe or contact us. When you buy a plan, our payment partner collects the billing information needed to process your payment.' ),
					$h( 'How we use it' ),
					$p( 'We use your information to reply to you, deliver and manage the services you buy, send service updates and, if you subscribed, send our newsletter. We do not sell your personal information.' ),
					$h( 'Payments' ),
					$p( 'Payments and subscriptions are processed by Whop. Card details are handled by Whop and its payment processors and are never stored on this website. Whop’s own privacy policy applies to the information it processes.' ),
					$h( 'Cookies and similar technologies' ),
					$p( 'This website stores small preferences in your browser, such as your light or dark mode choice and checklist progress. If analytics are enabled we use them to understand how the site is used and to improve it. You can clear or block cookies and site data in your browser settings.' ),
					$h( 'How long we keep information' ),
					$p( 'We keep enquiries and client records only for as long as needed to provide our services and meet legal and accounting requirements.' ),
					$h( 'Your choices' ),
					$p( 'You can unsubscribe from emails at any time and ask us to access, correct or delete your personal information by contacting us through the <a href="/contact/">contact page</a>.' ),
				)
			),
		),
		'terms-of-service'                      => array(
			'Terms of Service',
			implode(
				"\n\n",
				array(
					$p( 'These terms apply when you buy a service, subscribe to a plan, book a consultation or request a quote from A2E Dropshipping. By doing so you agree to them.' ),
					$h( 'Our services' ),
					$p( 'We provide marketplace services such as product research, eBay listing creation and optimisation, store setup, operational workflows, account management and consultation. The exact scope is defined by the plan you choose or the quote we agree in writing.' ),
					$h( 'Subscriptions and billing' ),
					$p( 'Monthly plans are billed in advance and renew automatically each billing period until cancelled. Payments are processed by Whop. You can cancel future renewals at any time from your Whop account; cancellation stops the next renewal and does not refund the current period unless we agree otherwise in writing. One-time packages are charged once.' ),
					$h( 'Refunds' ),
					$p( 'Because our work is a service that starts soon after purchase, payments for work already started are generally non-refundable. If something is not right, contact us first and we will always try to fix it.' ),
					$h( 'Your responsibilities' ),
					$p( 'You remain responsible for your marketplace accounts and their compliance with marketplace rules (including eBay’s dropshipping and selling policies), your suppliers, your customers, taxes and the final approval of listings and business decisions.' ),
					$h( 'No guaranteed results' ),
					$p( 'Sales, rankings and profit depend on many factors outside our control, including marketplace rules, competition, pricing, suppliers and account history. We do not guarantee specific results.' ),
				)
			),
		),
	);
}

/**
 * Create a page/post when its path does not exist yet.
 *
 * @param array $args Post args (post_name, post_title, post_type, post_parent…).
 * @return int Post ID (existing or new).
 */
function a2e_ensure_post( $args ) {
	$type   = isset( $args['post_type'] ) ? $args['post_type'] : 'page';
	$path   = $args['post_name'];
	if ( ! empty( $args['post_parent'] ) ) {
		$parent = get_post( $args['post_parent'] );
		$path   = $parent ? $parent->post_name . '/' . $path : $path;
	}
	$existing = get_page_by_path( $path, OBJECT, $type );
	if ( $existing ) {
		if ( 'trash' === $existing->post_status ) {
			return 0;
		}
		// WordPress creates /privacy-policy/ as an unpublished draft full of “Suggested text:”.
		if ( 'privacy-policy' === $path && 'draft' === $existing->post_status && false !== strpos( $existing->post_content, 'Suggested text:' ) && ! empty( $args['post_content'] ) ) {
			wp_update_post( wp_slash( array( 'ID' => $existing->ID, 'post_content' => $args['post_content'], 'post_status' => 'publish' ) ) );
		}
		return (int) $existing->ID;
	}
	$args['post_type']   = $type;
	$args['post_status'] = 'publish';
	$id                  = wp_insert_post( wp_slash( $args ), true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Seed everything.
 */
function a2e_seed() {
	a2e_register_post_types();
	$ids = array();
	foreach ( a2e_seed_pages() as $slug => $page ) {
		$ids[ $slug ] = a2e_ensure_post(
			array(
				'post_name'    => $slug,
				'post_title'   => $page[0],
				'post_content' => str_replace( 'href="/', 'href="' . home_url( '/' ), $page[1] ),
			)
		);
	}

	if ( ! a2e_shop_page_id() ) {
		a2e_ensure_post(
			array(
				'post_name'  => 'shop',
				'post_title' => 'Services Shop',
			)
		);
	}

	if ( ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) && ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	if ( ! get_option( 'page_for_posts' ) && ! empty( $ids['blog'] ) ) {
		update_option( 'page_for_posts', $ids['blog'] );
	}
	if ( ! empty( $ids['privacy-policy'] ) && ! get_option( 'wp_page_for_privacy_policy' ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
	}

	if ( ! empty( $ids['free-tools'] ) ) {
		$order = 1;
		foreach ( a2e_tools() as $slug => $tool ) {
			a2e_ensure_post(
				array(
					'post_name'   => $slug,
					'post_title'  => $tool['name'],
					'post_parent' => $ids['free-tools'],
					'menu_order'  => $order++,
				)
			);
		}
	}

	$order = 1;
	foreach ( a2e_services() as $slug => $service ) {
		a2e_ensure_post(
			array(
				'post_type'    => 'a2e_service',
				'post_name'    => $slug,
				'post_title'   => $service['title'],
				'post_excerpt' => $service['short'],
				'menu_order'   => $order++,
			)
		);
	}

	a2e_seed_examples();
	update_option( 'a2e_prime_seeded', A2E_VERSION, false );
	flush_rewrite_rules();
}

/**
 * Example reviews, portfolio items and case studies: only when a type has none at all.
 */
function a2e_seed_examples() {
	if ( ! wp_count_posts( 'a2e_testimonial' )->publish ) {
		$reviews = array(
			array( 'abdullahtari831', 'Impressed with the product research. After listing the researched items, the store received early orders and the delivery was on time.', 'Pakistan', 'Product Research' ),
			array( 'saracaroline', 'Account health improved and consistent sales started coming in through ongoing support.', 'Mauritius', 'Account Management' ),
			array( 'azizafba', 'Professional, reliable, clear with instructions and committed to high-quality work.', 'United States', 'Product Research' ),
		);
		foreach ( $reviews as $r ) {
			$id = wp_insert_post( wp_slash( array( 'post_type' => 'a2e_testimonial', 'post_status' => 'publish', 'post_title' => $r[0], 'post_content' => $r[1] ) ) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_a2e_rating', '5' );
				update_post_meta( $id, '_a2e_country', $r[2] );
				update_post_meta( $id, '_a2e_source', $r[3] );
			}
		}
	}
	if ( ! wp_count_posts( 'a2e_case_study' )->publish ) {
		$cases = array(
			array( 'Store Foundation Cleanup', 'store-foundation-cleanup', 'The seller had no clear store structure, weak policies and inconsistent listing presentation.', 'We built a store setup roadmap, a category structure, a listing framework and a weekly task list.', 'The store became easier to manage and ready for product research and listing optimisation.' ),
			array( 'Listing SEO Cleanup', 'listing-seo-cleanup', 'Listings had weak titles, missing item specifics and hard-to-read descriptions.', 'We rewrote listings around eBay search and buyer intent: titles, item specifics and clean descriptions.', 'The seller has a clear listing framework for every future upload.' ),
		);
		foreach ( $cases as $c ) {
			$content = '<!-- wp:heading --><h2 class="wp-block-heading">The problem</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . $c[2] . '</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">What we did</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . $c[3] . '</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">The result</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . $c[4] . '</p><!-- /wp:paragraph -->';
			wp_insert_post( wp_slash( array( 'post_type' => 'a2e_case_study', 'post_status' => 'publish', 'post_title' => $c[0], 'post_name' => $c[1], 'post_content' => $content, 'post_excerpt' => $c[2] ) ) );
		}
	}
}

/**
 * Run on activation.
 */
function a2e_after_switch_theme() {
	a2e_seed();
	set_transient( 'a2e_activated', 1, 300 );
}
add_action( 'after_switch_theme', 'a2e_after_switch_theme' );

/**
 * Run once after the theme is updated in place.
 */
function a2e_maybe_seed() {
	if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_option( 'a2e_prime_seeded' ) !== A2E_VERSION ) {
		a2e_seed();
	}
}
add_action( 'admin_init', 'a2e_maybe_seed' );
