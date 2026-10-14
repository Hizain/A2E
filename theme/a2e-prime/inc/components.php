<?php
/**
 * Reusable page components shared by templates and shortcodes.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slugs of pages rendered by an A2E designed template (page-{slug}.php).
 *
 * @return array
 */
function a2e_designed_page_slugs() {
	return array( 'pricing', 'subscription-plans', 'amazon-to-ebay-dropshipping-service', 'free-tools', 'contact', 'quote-request', 'consultation', 'portfolio', 'case-studies-overview', 'amazon-to-ebay-dropshipping-checklist', 'about', 'account' );
}

/**
 * Whether a page uses a designed template.
 *
 * @param WP_Post|int|null $post Post (defaults to the queried object).
 * @return bool
 */
function a2e_is_designed_page( $post = null ) {
	$post = $post ? get_post( $post ) : ( is_singular( 'page' ) ? get_queried_object() : null );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return false;
	}
	if ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
		return true;
	}
	return in_array( $post->post_name, a2e_designed_page_slugs(), true ) || a2e_tool_slug_for_post( $post );
}

/**
 * Allow a small set of inline tags in headlines.
 *
 * @param string $html HTML.
 * @return string
 */
function a2e_kses_inline( $html ) {
	return wp_kses(
		$html,
		array(
			'em'     => array(),
			'strong' => array(),
			'br'     => array(),
			'span'   => array( 'class' => array() ),
			'a'      => array( 'href' => array(), 'class' => array() ),
		)
	);
}

/**
 * Plain-text headline from a setting/meta: *words* become the highlighted serif style.
 *
 * @param string $text Text.
 * @return string Safe HTML.
 */
function a2e_format_headline( $text ) {
	$html = esc_html( $text );
	return preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $html );
}

/**
 * Section heading block.
 *
 * @param array $args eyebrow, title (HTML allowed: em/strong/br), intro, align (center|left), tag.
 */
function a2e_section_head( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow' => '',
			'title'   => '',
			'intro'   => '',
			'align'   => 'center',
			'tag'     => 'h2',
			'class'   => '',
		)
	);
	$tag = in_array( $args['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
	?>
	<header class="sec-head sec-head--<?php echo esc_attr( $args['align'] ); ?> <?php echo esc_attr( $args['class'] ); ?>" data-reveal>
		<?php if ( $args['eyebrow'] ) : ?><p class="eyebrow"><span class="eyebrow__dot" aria-hidden="true"></span><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
		<<?php echo esc_html( $tag ); ?> class="sec-head__title"><?php echo a2e_kses_inline( $args['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></<?php echo esc_html( $tag ); ?>>
		<?php if ( $args['intro'] ) : ?><p class="sec-head__intro"><?php echo a2e_kses_inline( $args['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><?php endif; ?>
	</header>
	<?php
}

/**
 * Inner-page hero.
 *
 * @param array $args eyebrow, title (HTML), intro, actions (array of [label,url,style,icon]), visual (HTML), crumbs (bool), class, meta (HTML).
 */
function a2e_page_hero( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow' => '',
			'title'   => get_the_title(),
			'intro'   => '',
			'actions' => array(),
			'visual'  => '',
			'crumbs'  => true,
			'class'   => '',
			'meta'    => '',
		)
	);
	$post_id = get_queried_object_id();
	$eyebrow = a2e_page_text( 'eyebrow', $args['eyebrow'], $post_id );
	$custom  = a2e_page_text( 'title', '', $post_id );
	$title   = $custom ? a2e_format_headline( $custom ) : a2e_kses_inline( $args['title'] );
	$intro   = a2e_page_text( 'intro', '', $post_id );
	$intro   = $intro ? esc_html( $intro ) : a2e_kses_inline( $args['intro'] );
	?>
	<section class="phero <?php echo $args['visual'] ? 'phero--split' : ''; ?> <?php echo esc_attr( $args['class'] ); ?>">
		<div class="phero__bg" aria-hidden="true"><span class="aurora aurora--a"></span><span class="aurora aurora--b"></span><span class="grid-fade"></span></div>
		<div class="container phero__inner">
			<div class="phero__copy">
				<?php if ( $args['crumbs'] ) { a2e_breadcrumbs(); } ?>
				<?php if ( $eyebrow ) : ?><p class="eyebrow"><span class="eyebrow__dot" aria-hidden="true"></span><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<h1 class="phero__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
				<?php if ( $intro ) : ?><p class="phero__intro"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><?php endif; ?>
				<?php if ( $args['meta'] ) : ?><div class="phero__meta"><?php echo $args['meta']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
				<?php if ( $args['actions'] ) : ?>
					<div class="btn-row"><?php a2e_buttons( $args['actions'] ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $args['visual'] ) : ?><div class="phero__visual"><?php echo $args['visual']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Buttons.
 *
 * @param array $buttons List of [ label, url, style (primary|ghost|wa|light), icon ].
 */
function a2e_buttons( $buttons ) {
	foreach ( $buttons as $b ) {
		$label = $b[0];
		$url   = $b[1];
		$style = isset( $b[2] ) ? $b[2] : 'primary';
		$icon  = isset( $b[3] ) ? $b[3] : ( 'primary' === $style ? 'arrow-right' : '' );
		$ext   = 0 === strpos( $url, 'https://wa.me' ) || false !== strpos( $url, 'fiverr.com' );
		printf(
			'<a class="btn btn--%1$s" href="%2$s"%3$s>%4$s<span>%5$s</span>%6$s</a>',
			esc_attr( $style ),
			esc_url( $url ),
			$ext ? ' target="_blank" rel="noopener"' : '',
			'wa' === $style ? a2e_icon( 'whatsapp' ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $label ),
			( $icon && 'wa' !== $style ) ? a2e_icon( $icon, 'btn__icon' ) : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}

/**
 * Breadcrumb trail as a list of [ name, url ].
 *
 * @return array
 */
function a2e_breadcrumb_items() {
	$items = array( array( __( 'Home', 'a2e' ), home_url( '/' ) ) );
	if ( is_front_page() ) {
		return array();
	}
	if ( is_singular( 'post' ) ) {
		$items[] = array( __( 'Blog', 'a2e' ), a2e_blog_url() );
		$cats    = get_the_category();
		if ( $cats ) {
			$items[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$items[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular( 'a2e_service' ) ) {
		$items[] = array( __( 'Services', 'a2e' ), a2e_url( 'amazon-to-ebay-dropshipping-service' ) );
		$items[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular( 'a2e_case_study' ) ) {
		$items[] = array( __( 'Case studies', 'a2e' ), get_post_type_archive_link( 'a2e_case_study' ) );
		$items[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular( 'a2e_portfolio' ) ) {
		$items[] = array( __( 'Portfolio', 'a2e' ), a2e_url( 'portfolio' ) );
		$items[] = array( get_the_title(), get_permalink() );
	} elseif ( is_page() ) {
		$post = get_queried_object();
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
			$items[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
		$items[] = array( get_the_title( $post ), get_permalink( $post ) );
	} elseif ( is_home() ) {
		$items[] = array( __( 'Blog', 'a2e' ), a2e_blog_url() );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = array( __( 'Blog', 'a2e' ), a2e_blog_url() );
		$items[] = array( single_term_title( '', false ), get_term_link( get_queried_object() ) );
	} elseif ( is_post_type_archive( 'a2e_case_study' ) ) {
		$items[] = array( __( 'Case studies', 'a2e' ), get_post_type_archive_link( 'a2e_case_study' ) );
	} elseif ( is_post_type_archive( 'a2e_testimonial' ) ) {
		$items[] = array( __( 'Reviews', 'a2e' ), get_post_type_archive_link( 'a2e_testimonial' ) );
	} elseif ( is_author() ) {
		$items[] = array( get_the_author(), get_author_posts_url( get_queried_object_id() ) );
	} elseif ( is_search() ) {
		$items[] = array( __( 'Search', 'a2e' ), get_search_link() );
	}
	return $items;
}

/**
 * Visible breadcrumbs.
 */
function a2e_breadcrumbs() {
	$items = a2e_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	echo '<nav class="crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'a2e' ) . '"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		if ( $i === $last ) {
			echo '<li aria-current="page"><span>' . esc_html( wp_html_excerpt( $item[0], 48, '…' ) ) . '</span></li>';
		} else {
			echo '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>' . a2e_icon( 'chevron-right' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	echo '</ol></nav>';
}

/**
 * Star rating row.
 *
 * @param float $rating Rating.
 * @return string
 */
function a2e_stars( $rating = 5 ) {
	$out = '<span class="stars" role="img" aria-label="' . esc_attr( sprintf( /* translators: %s: rating */ __( '%s out of 5 stars', 'a2e' ), $rating ) ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<svg viewBox="0 0 24 24" aria-hidden="true" class="' . ( $i <= round( (float) $rating ) ? 'on' : '' ) . '"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>';
	}
	return $out . '</span>';
}

/**
 * Trust row: avatars + rating.
 *
 * @param string $class Extra class.
 */
function a2e_trust_row( $class = '' ) {
	$initials = array( 'SA', 'AZ', 'MK', 'JR', 'AT' );
	?>
	<div class="trust <?php echo esc_attr( $class ); ?>">
		<span class="avatars" aria-hidden="true"><?php foreach ( $initials as $i => $ini ) : ?><span class="avatar avatar--<?php echo (int) $i; ?>"><?php echo esc_html( $ini ); ?></span><?php endforeach; ?></span>
		<span class="trust__text">
			<?php echo a2e_stars( (float) a2e_opt( 'rating' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><strong><?php echo esc_html( a2e_opt( 'rating' ) ); ?>/5</strong> <?php
			/* translators: %s: number of reviews */
			printf( esc_html__( 'from %s reviews on', 'a2e' ), esc_html( a2e_opt( 'reviews' ) ) );
			?> <a href="<?php echo esc_url( a2e_opt( 'fiverr_url' ) ); ?>" target="_blank" rel="noopener"><?php a2e_the_icon( 'fiverr', 'trust__fiverr' ); ?><span class="screen-reader-text">Fiverr</span></a></span>
		</span>
	</div>
	<?php
}

/**
 * Pricing cards.
 *
 * @param array $args custom (bool), compact (bool), only (month|once|''), heading (bool).
 */
function a2e_render_pricing( $args = array() ) {
	$args  = wp_parse_args( $args, array( 'custom' => true, 'only' => '' ) );
	$plans = a2e_get_plans();
	if ( $args['only'] ) {
		$plans = array_values(
			array_filter(
				$plans,
				function ( $p ) use ( $args ) {
					return 'month' === $args['only'] ? in_array( $p['interval'], array( 'month', 'year' ), true ) : 'once' === $p['interval'];
				}
			)
		);
	}
	if ( ! $plans ) {
		return;
	}
	$count = count( $plans );
	?>
	<div class="pricing">
		<div class="pricing__grid pricing__grid--<?php echo (int) min( 4, $count ); ?>">
			<?php foreach ( $plans as $i => $plan ) : ?>
				<article class="plan<?php echo ! empty( $plan['featured'] ) ? ' plan--featured' : ''; ?>" data-reveal style="--d:<?php echo (int) $i; ?>">
					<?php if ( ! empty( $plan['featured'] ) ) : ?><div class="plan__glow" aria-hidden="true"></div><?php endif; ?>
					<header class="plan__head">
						<?php if ( ! empty( $plan['badge'] ) ) : ?><span class="plan__badge"><?php echo ! empty( $plan['featured'] ) ? a2e_icon( 'star' ) : ''; // phpcs:ignore ?><?php echo esc_html( $plan['badge'] ); ?></span><?php endif; ?>
						<h3 class="plan__name"><?php echo esc_html( $plan['name'] ); ?></h3>
						<?php if ( ! empty( $plan['desc'] ) ) : ?><p class="plan__desc"><?php echo esc_html( $plan['desc'] ); ?></p><?php endif; ?>
					</header>
					<div class="plan__price">
						<span class="plan__amount"><?php echo esc_html( $plan['price'] ); ?></span>
						<?php if ( ! empty( $plan['period'] ) ) : ?><span class="plan__period"><?php echo esc_html( $plan['period'] ); ?></span><?php endif; ?>
					</div>
					<?php if ( ! empty( $plan['note'] ) ) : ?><p class="plan__note"><?php echo esc_html( $plan['note'] ); ?></p><?php endif; ?>
					<a class="btn <?php echo ! empty( $plan['featured'] ) ? 'btn--primary' : 'btn--ghost'; ?> btn--block plan__cta" href="<?php echo esc_url( $plan['url'] ); ?>" data-plan="<?php echo esc_attr( $plan['name'] ); ?>"><span><?php echo esc_html( $plan['button'] ? $plan['button'] : __( 'Get started', 'a2e' ) ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
					<?php if ( ! empty( $plan['features'] ) ) : ?>
						<ul class="plan__features">
							<?php foreach ( $plan['features'] as $feature ) : ?><li><?php a2e_the_icon( 'check' ); ?><span><?php echo esc_html( $feature ); ?></span></li><?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<a class="plan__ask" href="<?php echo esc_url( a2e_wa_link( sprintf( 'Hi A2E, I have a question about the %s plan.', $plan['name'] ) ) ); ?>" target="_blank" rel="noopener"><?php a2e_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'Ask a question first', 'a2e' ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $args['custom'] ) : ?>
			<aside class="custom-plan" data-reveal>
				<div class="custom-plan__icon"><?php a2e_the_icon( 'sparkle' ); ?></div>
				<div class="custom-plan__copy">
					<h3><?php echo esc_html( a2e_opt( 'custom_title' ) ); ?></h3>
					<p><?php echo esc_html( a2e_opt( 'custom_text' ) ); ?></p>
				</div>
				<div class="custom-plan__actions">
					<?php a2e_buttons( array( array( __( 'Request a custom plan', 'a2e' ), add_query_arg( 'plan', 'custom', a2e_url( 'quote-request' ) ), 'light' ) ) ); ?>
				</div>
			</aside>
		<?php endif; ?>
		<p class="pricing__trust"><?php a2e_the_icon( 'lock' ); ?><?php echo esc_html( a2e_plans_from_whop() ? __( 'Secure checkout by Whop · Cancel future renewals anytime from your account', 'a2e' ) : __( 'Clear scope before you pay · No long-term contract · Cancel anytime', 'a2e' ) ); ?></p>
	</div>
	<?php
}

/**
 * FAQ items from Site Settings.
 *
 * @return array list of [ q, a ]
 */
function a2e_site_faq() {
	$items = array();
	for ( $i = 1; $i <= 8; $i++ ) {
		$q = a2e_opt( "faq_{$i}_q" );
		$a = a2e_opt( "faq_{$i}_a" );
		if ( $q && $a ) {
			$items[] = array( $q, $a );
		}
	}
	return $items;
}

/**
 * Accordion of questions; also queues them for FAQPage structured data.
 *
 * @param array $items List of [ q, a ].
 * @param bool  $open_first Open the first item.
 */
function a2e_render_faq( $items, $open_first = true ) {
	if ( ! $items ) {
		return;
	}
	$GLOBALS['a2e_faq_schema'] = array_merge( isset( $GLOBALS['a2e_faq_schema'] ) ? $GLOBALS['a2e_faq_schema'] : array(), $items );
	echo '<div class="faq">';
	foreach ( $items as $i => $item ) {
		printf(
			'<details class="faq__item" data-reveal%1$s><summary><span>%2$s</span><span class="faq__icon" aria-hidden="true">%3$s</span></summary><div class="faq__a"><p>%4$s</p></div></details>',
			( $open_first && 0 === $i ) ? ' open' : '',
			esc_html( $item[0] ),
			a2e_icon( 'plus' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_kses( $item[1], array( 'a' => array( 'href' => array() ), 'strong' => array(), 'em' => array() ) )
		);
	}
	echo '</div>';
}

/**
 * Stats band.
 *
 * @param string $class Extra class.
 */
function a2e_render_stats( $class = '' ) {
	$icons = array( 'star', 'box', 'store', 'award' );
	echo '<div class="stats ' . esc_attr( $class ) . '">';
	for ( $i = 1; $i <= 4; $i++ ) {
		$number = a2e_opt( "stat_{$i}_number" );
		if ( '' === $number ) {
			continue;
		}
		preg_match( '/^([^0-9]*)([0-9][0-9.,]*)(.*)$/', $number, $m );
		echo '<div class="stat" data-reveal style="--d:' . (int) $i . '">';
		echo '<span class="stat__icon">' . a2e_icon( $icons[ $i - 1 ] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $m ) {
			printf( '<strong class="stat__num">%1$s<span data-count="%2$s">%2$s</span>%3$s</strong>', esc_html( $m[1] ), esc_attr( $m[2] ), esc_html( $m[3] ) );
		} else {
			printf( '<strong class="stat__num">%s</strong>', esc_html( $number ) );
		}
		echo '<span class="stat__label">' . esc_html( a2e_opt( "stat_{$i}_label" ) ) . '</span></div>';
	}
	echo '</div>';
}

/**
 * Infinite supplier logo marquee.
 *
 * @param string $class Extra class.
 */
function a2e_render_marquee( $class = '' ) {
	$keys = a2e_supplier_keys();
	$half = (int) ceil( count( $keys ) / 2 );
	$rows = array( array_slice( $keys, 0, $half ), array_slice( $keys, $half ) );
	echo '<div class="marquee ' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'Suppliers we source from', 'a2e' ) . '">';
	foreach ( $rows as $r => $row ) {
		echo '<div class="marquee__row' . ( $r ? ' marquee__row--rev' : '' ) . '"><div class="marquee__track">';
		for ( $copy = 0; $copy < 2; $copy++ ) {
			foreach ( $row as $key ) {
				echo '<span class="marquee__item"' . ( $copy ? ' aria-hidden="true"' : '' ) . '>' . a2e_brand_mark( $key ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '</div></div>';
	}
	echo '</div>';
}

/**
 * Services grid (cards).
 *
 * @param array  $slugs   Service slugs.
 * @param string $variant bento|cards.
 */
function a2e_render_services( $slugs = array(), $variant = 'cards' ) {
	$services = a2e_services();
	$slugs    = $slugs ? $slugs : a2e_featured_service_slugs();
	echo '<div class="svc-grid svc-grid--' . esc_attr( $variant ) . '">';
	foreach ( $slugs as $i => $slug ) {
		if ( ! isset( $services[ $slug ] ) ) {
			continue;
		}
		$s = $services[ $slug ];
		?>
		<a class="svc-card svc-card--<?php echo esc_attr( $slug ); ?>" href="<?php echo esc_url( a2e_service_url( $slug ) ); ?>" data-reveal style="--d:<?php echo (int) ( $i % 4 ); ?>">
			<span class="svc-card__icon"><?php a2e_the_icon( $s['icon'] ); ?></span>
			<?php if ( 'bento' === $variant && 0 === $i ) : ?>
				<span class="svc-flow" aria-hidden="true">
					<span class="svc-flow__col"><?php foreach ( array( 'amazon', 'walmart', 'aliexpress' ) as $b ) { echo '<span class="svc-flow__chip">' . a2e_brand_mark( $b, 'sm' ) . '</span>'; } // phpcs:ignore ?></span>
					<span class="svc-flow__line"><i></i></span>
					<span class="svc-flow__core"><img src="<?php echo esc_url( a2e_img( 'brand/a2e-icon-64.png' ) ); ?>" width="40" height="40" alt=""></span>
					<span class="svc-flow__line"><i></i></span>
					<span class="svc-flow__chip svc-flow__chip--ebay"><?php echo a2e_brand_mark( 'ebay' ); // phpcs:ignore ?></span>
				</span>
			<?php endif; ?>
			<h3 class="svc-card__title"><?php echo esc_html( $s['title'] ); ?></h3>
			<p class="svc-card__text"><?php echo esc_html( $s['short'] ); ?></p>
			<ul class="svc-card__points">
				<?php foreach ( array_slice( $s['outcomes'], 0, 3 ) as $o ) : ?><li><?php a2e_the_icon( 'check' ); ?><?php echo esc_html( $o ); ?></li><?php endforeach; ?>
			</ul>
			<span class="svc-card__more"><?php esc_html_e( 'Explore', 'a2e' ); ?><?php a2e_the_icon( 'arrow-up-right' ); ?></span>
		</a>
		<?php
	}
	echo '</div>';
}

/**
 * Process steps.
 */
function a2e_render_process() {
	echo '<ol class="steps">';
	foreach ( a2e_process_steps() as $i => $step ) {
		printf(
			'<li class="step" data-reveal style="--d:%1$d"><span class="step__num">%2$s</span><span class="step__icon">%3$s</span><h3 class="step__title">%4$s</h3><p class="step__text">%5$s</p></li>',
			(int) $i,
			esc_html( sprintf( '%02d', $i + 1 ) ),
			a2e_icon( $step[0] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $step[1] ),
			esc_html( $step[2] )
		);
	}
	echo '</ol>';
}

/**
 * Reviews from the Testimonials post type.
 *
 * @param int    $limit   How many.
 * @param string $variant wall|grid.
 */
function a2e_render_reviews( $limit = 12, $variant = 'wall' ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'a2e_testimonial',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
			'post_status'    => 'publish',
		)
	);
	if ( ! $query->have_posts() ) {
		return;
	}
	$cards = array();
	while ( $query->have_posts() ) {
		$query->the_post();
		$name    = preg_replace( '/\s+[-–—]\s+.*$/u', '', get_post_field( 'post_title', get_the_ID() ) );
		$cards[] = array(
			'name'    => $name,
			'text'    => wp_strip_all_tags( get_the_content() ),
			'rating'  => (float) ( get_post_meta( get_the_ID(), '_a2e_rating', true ) ? get_post_meta( get_the_ID(), '_a2e_rating', true ) : 5 ),
			'country' => (string) get_post_meta( get_the_ID(), '_a2e_country', true ),
			'service' => (string) get_post_meta( get_the_ID(), '_a2e_source', true ),
		);
	}
	wp_reset_postdata();

	$render = function ( $c, $hidden = false ) {
		$initial = strtoupper( substr( $c['name'], 0, 1 ) );
		return sprintf(
			'<figure class="review"%1$s><div class="review__top">%2$s<span class="review__src">%3$s Fiverr</span></div><blockquote class="review__text">“%4$s”</blockquote><figcaption class="review__by"><span class="review__avatar" aria-hidden="true">%5$s</span><span><strong>%6$s</strong><small>%7$s</small></span></figcaption></figure>',
			$hidden ? ' aria-hidden="true"' : '',
			a2e_stars( $c['rating'] ),
			a2e_icon( 'badge' ),
			esc_html( $c['text'] ),
			esc_html( $initial ),
			esc_html( $c['name'] ),
			esc_html( trim( $c['service'] . ( $c['service'] && $c['country'] ? ' · ' : '' ) . $c['country'] ) )
		);
	};

	if ( 'grid' === $variant ) {
		echo '<div class="reviews-grid">';
		foreach ( $cards as $c ) {
			echo $render( $c ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		return;
	}
	echo '<div class="reviews-wall"><div class="reviews-wall__track">';
	foreach ( array( false, true ) as $hidden ) {
		foreach ( $cards as $c ) {
			echo $render( $c, $hidden ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	echo '</div></div>';
}

/**
 * Closing call-to-action band.
 *
 * @param array $args title, text.
 */
function a2e_render_cta( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title' => a2e_opt( 'final_title' ),
			'text'  => a2e_opt( 'final_text' ),
		)
	);
	?>
	<section class="cta-band">
		<div class="container">
			<div class="cta-band__panel" data-reveal>
				<div class="cta-band__bg" aria-hidden="true"><span class="aurora aurora--a"></span><span class="aurora aurora--b"></span><span class="cta-band__rings"></span></div>
				<div class="cta-band__copy">
					<p class="eyebrow eyebrow--light"><span class="eyebrow__dot" aria-hidden="true"></span><?php esc_html_e( 'Free store audit', 'a2e' ); ?></p>
					<h2 class="cta-band__title"><?php echo a2e_format_headline( $args['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<p class="cta-band__text"><?php echo esc_html( $args['text'] ); ?></p>
					<div class="btn-row">
						<?php
						a2e_buttons(
							array(
								array( __( 'Get my free audit', 'a2e' ), a2e_url( 'quote-request' ), 'light' ),
								array( __( 'Chat on WhatsApp', 'a2e' ), a2e_wa_link( 'Hi A2E! I would like a free audit of my eBay store.' ), 'wa' ),
							)
						);
						?>
					</div>
					<ul class="cta-band__points">
						<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Reply within one business day', 'a2e' ); ?></li>
						<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Fixed price before any work', 'a2e' ); ?></li>
						<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Cancel anytime', 'a2e' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Founder note (E-E-A-T).
 */
function a2e_render_founder() {
	$name  = a2e_opt( 'founder_name' );
	$photo = a2e_opt( 'founder_photo' );
	$parts = preg_split( '/\s+/', trim( $name ) );
	$ini   = strtoupper( substr( $parts[0], 0, 1 ) . ( isset( $parts[1] ) ? substr( $parts[1], 0, 1 ) : '' ) );
	?>
	<figure class="founder" data-reveal>
		<div class="founder__media">
			<?php if ( $photo ) : ?>
				<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="160" height="160" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="founder__initials" aria-hidden="true"><?php echo esc_html( $ini ); ?></span>
			<?php endif; ?>
			<span class="founder__since"><?php /* translators: %s: year */ printf( esc_html__( 'Selling on eBay since %s', 'a2e' ), esc_html( a2e_opt( 'since' ) ) ); ?></span>
		</div>
		<div class="founder__body">
			<?php a2e_the_icon( 'quote', 'founder__quote-icon' ); ?>
			<blockquote class="founder__quote"><?php echo esc_html( a2e_opt( 'founder_quote' ) ); ?></blockquote>
			<figcaption class="founder__by"><strong><?php echo esc_html( $name ); ?></strong><span><?php echo esc_html( a2e_opt( 'founder_role' ) ); ?></span></figcaption>
		</div>
	</figure>
	<?php
}

/**
 * Show the page's editor content below a designed layout when the editor asked for it.
 */
function a2e_designed_extra_content() {
	$id = get_queried_object_id();
	if ( ! $id || ! get_post_meta( $id, '_a2e_show_content', true ) ) {
		return;
	}
	$post = get_post( $id );
	if ( ! $post || '' === trim( $post->post_content ) ) {
		return;
	}
	echo '<section class="section section--tight"><div class="container container--narrow prose">';
	echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div></section>';
}
