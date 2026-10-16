<?php
/**
 * SEO: titles, meta descriptions, canonical, robots, Open Graph / Twitter cards, structured
 * data (JSON-LD) and sitemap tweaks.
 *
 * When Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework is active, the theme steps
 * back from titles/meta/social/site schema and only adds what those plugins do not:
 * tool (WebApplication), service and FAQ structured data.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default SEO copy for designed pages (used when the page has no custom SEO fields).
 *
 * @return array slug => [ title, description ]
 */
function a2e_seo_defaults() {
	return array(
		'home'                                  => array( 'Done-For-You Amazon to eBay Dropshipping Service', 'Done-for-you Amazon to eBay dropshipping: profit-checked product research, SEO eBay listings and daily store management on simple monthly plans. Cancel anytime.' ),
		'pricing'                               => array( 'Amazon to eBay Dropshipping Pricing & Plans', 'Simple, transparent plans for done-for-you eBay dropshipping: a one-time listing package or monthly store management. Profit-checked research, SEO listings, cancel anytime.' ),
		'subscription-plans'                    => array( 'Monthly eBay Store Management Plans', 'Monthly Amazon to eBay dropshipping plans: new researched listings every month, daily store management, monthly reports. Secure billing through Whop, cancel anytime.' ),
		'amazon-to-ebay-dropshipping-service'   => array( 'Amazon to eBay Dropshipping Services', 'Product research, SEO eBay listings, store setup, inventory monitoring, order fulfillment and account management. Every eBay dropshipping service from one team.' ),
		'free-tools'                            => array( 'Free eBay Seller Tools & Calculators', '14 free eBay seller tools: fee calculator, Amazon to eBay profit calculator, title checker, sell-through rate, bulk profit, product scorecard and more. No sign-up.' ),
		'shop'                                  => array( 'Amazon to eBay Dropshipping Services Shop', 'Order done-for-you eBay dropshipping services: monthly store management, researched listing packages, store setup and audits. Secure Whop checkout, cancel monthly plans anytime.' ),
		'contact'                               => array( 'Contact A2E Dropshipping', 'Questions about eBay dropshipping, plans or your store? Message A2E on WhatsApp or send us a note. A real person replies within one business day.' ),
		'quote-request'                         => array( 'Free eBay Store Audit & Quote', 'Get a free eBay store audit and a fixed-price plan for product research, listings and store management. Takes two minutes, no obligation.' ),
		'consultation'                          => array( 'Book an eBay Dropshipping Consultation', 'Book a consultation with A2E: a store review, product research direction, listing SEO and a prioritised growth plan before you spend money.' ),
		'portfolio'                             => array( 'Portfolio: eBay Research, Listing & Management Projects', 'Recent A2E Dropshipping projects: eBay product research sprints, listing SEO clean-ups and account management, plus verified client reviews.' ),
		'amazon-to-ebay-dropshipping-checklist' => array( 'Free Amazon to eBay Dropshipping Checklist', 'The interactive checklist we use to set up and run eBay dropshipping stores: store setup, product research, listings, inventory, orders and weekly account health.' ),
		'about'                                 => array( 'About A2E Dropshipping', 'A2E Dropshipping is an eBay dropshipping team helping sellers since 2018 with profit-checked research, SEO listings and honest, hands-on store management.' ),
		'account'                               => array( 'Client Area', 'Manage your A2E Dropshipping subscription, billing and support.' ),
	);
}

/**
 * SEO title and description for the current request.
 *
 * @return array [ title, description ]
 */
function a2e_seo_for_request() {
	static $seo = null;
	if ( null !== $seo ) {
		return $seo;
	}
	$defaults = a2e_seo_defaults();
	$title    = '';
	$desc     = '';
	$tool     = a2e_current_tool();

	if ( a2e_is_shop_request() ) {
		$title  = isset( $defaults['shop'] ) ? $defaults['shop'][0] : '';
		$desc   = isset( $defaults['shop'] ) ? $defaults['shop'][1] : '';
		$shop   = a2e_shop_page_id();
		$custom = $shop ? get_post_meta( $shop, '_a2e_seo_title', true ) : '';
		$title  = $custom ? $custom : $title;
		$custom = $shop ? get_post_meta( $shop, '_a2e_seo_desc', true ) : '';
		$desc   = $custom ? $custom : $desc;
	} elseif ( $tool ) {
		$t     = a2e_tools()[ $tool ];
		$title = $t['seo_title'];
		$desc  = $t['meta'];
	} elseif ( is_front_page() ) {
		$title = $defaults['home'][0];
		$desc  = $defaults['home'][1];
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( 'a2e_service' === $post->post_type && isset( a2e_services()[ $post->post_name ] ) ) {
			$s     = a2e_services()[ $post->post_name ];
			$title = $s['seo'];
			$desc  = $s['meta'];
		} elseif ( 'page' === $post->post_type && isset( $defaults[ $post->post_name ] ) ) {
			$title = $defaults[ $post->post_name ][0];
			$desc  = $defaults[ $post->post_name ][1];
		}
	} elseif ( is_home() ) {
		$title = __( 'Amazon to eBay Dropshipping Blog: Guides & Tips', 'a2e' );
		$desc  = __( 'Practical guides on eBay dropshipping: product research, listing SEO, fees and pricing, account health and scaling a store that lasts.', 'a2e' );
	} elseif ( is_post_type_archive( 'a2e_case_study' ) ) {
		$title = __( 'eBay Dropshipping Case Studies', 'a2e' );
		$desc  = __( 'How A2E fixes eBay stores: the problem, what we did and what changed. Store foundations, research systems and listing SEO clean-ups.', 'a2e' );
	} elseif ( is_post_type_archive( 'a2e_testimonial' ) ) {
		/* translators: 1: rating 2: reviews */
		$title = sprintf( __( 'Client Reviews: %1$s/5 from %2$s Reviews', 'a2e' ), a2e_opt( 'rating' ), a2e_opt( 'reviews' ) );
		$desc  = __( 'What eBay sellers say about A2E Dropshipping’s product research, listings and account management.', 'a2e' );
	} elseif ( is_category() || is_tag() ) {
		$title = single_term_title( '', false );
		$desc  = wp_strip_all_tags( term_description() );
	}

	if ( is_singular() && ! a2e_is_shop_request() ) {
		$id     = get_queried_object_id();
		$custom = get_post_meta( $id, '_a2e_seo_title', true );
		$custom = $custom ? $custom : get_post_meta( $id, '_a2e_meta_title', true );
		if ( $custom ) {
			$title = $custom;
		}
		$custom = get_post_meta( $id, '_a2e_seo_desc', true );
		$custom = $custom ? $custom : get_post_meta( $id, '_a2e_meta_description', true );
		if ( $custom ) {
			$desc = $custom;
		}
		if ( ! $desc ) {
			$post = get_queried_object();
			$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28, '…' );
		}
	}
	if ( ! $desc ) {
		$desc = get_bloginfo( 'description' );
	}
	$seo = array(
		trim( a2e_plain_dashes_title( (string) $title ) ),
		trim( a2e_plain_dashes( wp_strip_all_tags( (string) $desc ) ) ),
	);
	return $seo;
}

/**
 * Document title.
 *
 * @param string $title Title.
 * @return string
 */
function a2e_document_title( $title ) {
	if ( a2e_seo_plugin_active() && ! a2e_current_tool() ) {
		return $title;
	}
	list( $seo_title ) = a2e_seo_for_request();
	if ( ! $seo_title ) {
		return $title;
	}
	$site = get_bloginfo( 'name' );
	return false === stripos( $seo_title, 'A2E' ) ? $seo_title . ' | ' . $site : $seo_title;
}
add_filter( 'pre_get_document_title', 'a2e_document_title', 20 );

/**
 * Use a pipe between page and site name in WordPress's own titles, matching the theme's titles.
 *
 * @return string
 */
function a2e_title_separator() {
	return '|';
}
add_filter( 'document_title_separator', 'a2e_title_separator' );

/**
 * Canonical URL for the request.
 *
 * @return string
 */
function a2e_canonical_url() {
	if ( a2e_is_shop_request() ) {
		return a2e_shop_url();
	}
	if ( a2e_current_tool() ) {
		return a2e_tool_url( a2e_current_tool() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return wp_get_canonical_url();
	}
	if ( is_home() ) {
		$url = a2e_blog_url();
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
	} else {
		return '';
	}
	$paged = (int) get_query_var( 'paged' );
	if ( $paged > 1 && ! is_wp_error( $url ) ) {
		$url = trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' );
	}
	return is_wp_error( $url ) ? '' : $url;
}

/**
 * Social image for the request.
 *
 * @return string
 */
function a2e_social_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$img = wp_get_attachment_image_url( get_post_thumbnail_id(), 'a2e-hero' );
		if ( $img ) {
			return $img;
		}
	}
	$custom = a2e_opt( 'og_image' );
	return $custom ? $custom : a2e_img( 'og-image.png' );
}

/**
 * Meta tags in <head>.
 */
function a2e_meta_tags() {
	$gsc  = preg_replace( '/[^A-Za-z0-9_-]/', '', a2e_opt( 'gsc_verify' ) );
	$bing = preg_replace( '/[^A-Za-z0-9_-]/', '', a2e_opt( 'bing_verify' ) );
	if ( $gsc ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
	}
	if ( $bing ) {
		echo '<meta name="msvalidate.01" content="' . esc_attr( $bing ) . '">' . "\n";
	}
	if ( a2e_seo_plugin_active() ) {
		return;
	}

	list( $title, $desc ) = a2e_seo_for_request();
	$title     = $title ? $title : wp_get_document_title();
	$canonical = a2e_canonical_url();
	$noindex   = is_search() || is_404() || ( is_singular() && get_post_meta( get_queried_object_id(), '_a2e_noindex', true ) ) || is_author() || is_date();

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_html_excerpt( $desc, 300, '…' ) ) . '">' . "\n";
	}
	echo '<meta name="robots" content="' . ( $noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' ) . '">' . "\n";
	if ( $canonical && ! is_singular() ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
	}

	$type = is_singular( 'post' ) ? 'article' : 'website';
	$img  = a2e_social_image();
	$tags = array(
		'og:locale'      => get_locale(),
		'og:type'        => $type,
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $canonical ? $canonical : home_url( add_query_arg( array() ) ),
		'og:image'       => $img,
	);
	foreach ( $tags as $prop => $content ) {
		if ( $content ) {
			echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $content ) . '">' . "\n";
		}
	}
	if ( is_singular( 'post' ) ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
}
add_action( 'wp_head', 'a2e_meta_tags', 3 );

/**
 * The virtual tool route has no post: give it a canonical tag (singular pages get core's).
 */
function a2e_virtual_tool_canonical() {
	if ( ! empty( $GLOBALS['a2e_virtual_tool'] ) && a2e_seo_plugin_active() ) {
		echo '<link rel="canonical" href="' . esc_url( a2e_canonical_url() ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'a2e_virtual_tool_canonical', 4 );

/**
 * Organisation node (shared).
 *
 * @return array
 */
function a2e_schema_org() {
	$home   = home_url( '/' );
	$same   = array_values( array_filter( array( a2e_opt( 'fiverr_url' ), a2e_opt( 'social_linkedin' ), a2e_opt( 'social_facebook' ), a2e_opt( 'social_instagram' ), a2e_opt( 'social_youtube' ), a2e_opt( 'social_x' ) ) ) );
	$logo   = has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : a2e_img( 'brand/a2e-logo-light.png' );
	$org    = array(
		'@type'        => array( 'Organization', 'ProfessionalService' ),
		'@id'          => $home . '#organization',
		'name'         => get_bloginfo( 'name' ),
		'url'          => $home,
		'logo'         => array( '@type' => 'ImageObject', 'url' => $logo ),
		'image'        => a2e_img( 'brand/a2e-icon-512.png' ),
		'description'  => __( 'Done-for-you Amazon to eBay dropshipping services: product research, SEO eBay listings, store setup and monthly account management.', 'a2e' ),
		'foundingDate' => a2e_opt( 'since' ),
		'areaServed'   => 'Worldwide',
		'knowsAbout'   => array( 'Amazon to eBay dropshipping', 'eBay listing optimization', 'eBay product research', 'eBay store management', 'eBay fees' ),
		'sameAs'       => $same,
		'priceRange'   => '$$',
	);
	if ( a2e_opt( 'founder_name' ) ) {
		$org['founder'] = array( '@type' => 'Person', 'name' => a2e_opt( 'founder_name' ) );
	}
	$contact = array( '@type' => 'ContactPoint', 'contactType' => 'customer support', 'availableLanguage' => array( 'English' ) );
	if ( a2e_opt( 'whatsapp' ) ) {
		$contact['telephone'] = a2e_opt( 'whatsapp' );
	}
	if ( a2e_opt( 'email' ) ) {
		$contact['email'] = a2e_opt( 'email' );
		$org['email']     = a2e_opt( 'email' );
	}
	$org['contactPoint'] = $contact;
	return $org;
}

/**
 * Structured data graph for the request.
 *
 * @return array
 */
function a2e_schema_graph() {
	$home   = home_url( '/' );
	$graph  = array();
	$plugin = a2e_seo_plugin_active();
	$url    = a2e_canonical_url() ? a2e_canonical_url() : $home;
	list( $title, $desc ) = a2e_seo_for_request();

	if ( ! $plugin ) {
		$graph[] = a2e_schema_org();
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => $home . '#website',
			'url'             => $home,
			'name'            => get_bloginfo( 'name' ),
			'publisher'       => array( '@id' => $home . '#organization' ),
			'inLanguage'      => get_bloginfo( 'language' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => home_url( '/?s={search_term_string}' ) ),
				'query-input' => 'required name=search_term_string',
			),
		);
		$crumbs = a2e_breadcrumb_items();
		if ( count( $crumbs ) > 1 ) {
			$list = array();
			foreach ( $crumbs as $i => $c ) {
				$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( $c[0] ), 'item' => $c[1] );
			}
			$graph[] = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $list );
		}
		if ( is_singular( 'post' ) ) {
			$post    = get_queried_object();
			$article = array(
				'@type'            => 'BlogPosting',
				'@id'              => $url . '#article',
				'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
				'description'      => $desc,
				'datePublished'    => get_the_date( 'c', $post ),
				'dateModified'     => get_the_modified_date( 'c', $post ),
				'author'           => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ), 'url' => get_author_posts_url( $post->post_author ) ),
				'publisher'        => array( '@id' => $home . '#organization' ),
				'mainEntityOfPage' => $url,
				'image'            => a2e_social_image(),
				'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
				'inLanguage'       => get_bloginfo( 'language' ),
			);
			$graph[] = $article;
		}
	}

	$tool = a2e_current_tool();
	if ( $tool ) {
		$t       = a2e_tools()[ $tool ];
		$graph[] = array(
			'@type'               => 'WebApplication',
			'@id'                 => $url . '#app',
			'name'                => $t['name'],
			'url'                 => $url,
			'description'         => $t['meta'],
			'applicationCategory' => 'BusinessApplication',
			'operatingSystem'     => 'Any (web browser)',
			'isAccessibleForFree' => true,
			'offers'              => array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ),
			'provider'            => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => $home ),
		);
	}

	if ( is_singular( 'a2e_service' ) && isset( a2e_services()[ get_queried_object()->post_name ] ) ) {
		$s       = a2e_services()[ get_queried_object()->post_name ];
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => $url . '#service',
			'name'        => $s['title'],
			'serviceType' => $s['title'],
			'description' => $s['meta'],
			'url'         => $url,
			'areaServed'  => 'Worldwide',
			'provider'    => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => $home ),
		);
	}

	if ( is_front_page() || is_page( array( 'pricing', 'subscription-plans' ) ) ) {
		$offers = array();
		foreach ( a2e_get_plans() as $plan ) {
			if ( null === $plan['amount'] ) {
				continue;
			}
			$offer = array(
				'@type'         => 'Offer',
				'name'          => $plan['name'],
				'price'         => number_format( (float) $plan['amount'], 2, '.', '' ),
				'priceCurrency' => strtoupper( $plan['currency'] ? $plan['currency'] : 'USD' ),
				'url'           => $plan['url'],
				'availability'  => 'https://schema.org/InStock',
			);
			if ( in_array( $plan['interval'], array( 'month', 'year' ), true ) ) {
				$offer['priceSpecification'] = array(
					'@type'         => 'UnitPriceSpecification',
					'price'         => $offer['price'],
					'priceCurrency' => $offer['priceCurrency'],
					'unitCode'      => 'month' === $plan['interval'] ? 'MON' : 'ANN',
				);
			}
			$offers[] = $offer;
		}
		if ( $offers ) {
			$graph[] = array(
				'@type'      => 'Service',
				'@id'        => $home . '#dropshipping-service',
				'name'       => __( 'Amazon to eBay Dropshipping Management', 'a2e' ),
				'provider'   => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => $home ),
				'areaServed' => 'Worldwide',
				'offers'     => $offers,
			);
		}
	}

	if ( ! empty( $GLOBALS['a2e_faq_schema'] ) ) {
		$seen  = array();
		$items = array();
		foreach ( $GLOBALS['a2e_faq_schema'] as $faq ) {
			$key = md5( $faq[0] );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$items[]      = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $faq[0] ),
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $faq[1] ) ),
			);
		}
		$graph[] = array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => $items );
	}

	return apply_filters( 'a2e_schema_graph', $graph );
}

/**
 * Print structured data in the footer (FAQ items are collected while the page renders).
 */
function a2e_print_schema() {
	$graph = a2e_schema_graph();
	if ( ! $graph ) {
		return;
	}
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'a2e_print_schema', 50 );

/**
 * Keep pages that should not rank out of the XML sitemap.
 *
 * @param array $post_types Post types.
 * @return array
 */
function a2e_sitemap_post_types( $post_types ) {
	unset( $post_types['a2e_testimonial'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'a2e_sitemap_post_types' );

/**
 * Exclude noindexed posts and the client area from the sitemap.
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type.
 * @return array
 */
function a2e_sitemap_exclude( $args, $post_type ) {
	$exclude = get_posts(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_key'       => '_a2e_noindex', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( 'page' === $post_type ) {
		$account = get_page_by_path( 'account' );
		if ( $account ) {
			$exclude[] = $account->ID;
		}
		$overview = get_page_by_path( 'case-studies-overview' );
		if ( $overview ) {
			$exclude[] = $overview->ID; // Redirects to /case-studies/.
		}
	}
	if ( $exclude ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $exclude );
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'a2e_sitemap_exclude', 10, 2 );

/**
 * Drop the users sitemap (author archives are noindexed).
 *
 * @param WP_Sitemaps_Provider $provider Provider.
 * @param string               $name     Name.
 * @return WP_Sitemaps_Provider|false
 */
function a2e_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'a2e_sitemap_providers', 10, 2 );
