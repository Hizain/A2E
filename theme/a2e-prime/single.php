<?php
/**
 * Blog post: reading progress, sticky table of contents, sidebar CTA, author box, related posts.
 *
 * @package A2E_Prime
 */

get_header();
while ( have_posts() ) :
	the_post();
	$a2e_post    = get_post();
	$a2e_toc     = a2e_toc( $a2e_post );
	$a2e_cats    = get_the_category();
	$a2e_author  = (int) $a2e_post->post_author;
	$a2e_updated = get_the_modified_date( 'U' ) - get_the_date( 'U' ) > DAY_IN_SECONDS;
	?>
	<div class="read-progress" aria-hidden="true"></div>
	<article <?php post_class(); ?>>
		<header class="phero article-head">
			<div class="phero__bg" aria-hidden="true"><span class="aurora aurora--a"></span><span class="aurora aurora--b"></span><span class="grid-fade"></span></div>
			<div class="container phero__inner">
				<div class="phero__copy">
					<?php a2e_breadcrumbs(); ?>
					<?php if ( $a2e_cats && 'uncategorized' !== $a2e_cats[0]->slug ) : ?><a class="tag" href="<?php echo esc_url( get_category_link( $a2e_cats[0] ) ); ?>"><?php echo esc_html( $a2e_cats[0]->name ); ?></a><?php endif; ?>
					<h1 class="phero__title"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?><p class="phero__intro"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
					<div class="phero__meta">
						<span class="byline"><?php echo get_avatar( $a2e_author, 34, '', '' ); ?><?php the_author(); ?></span>
						<span><?php a2e_the_icon( 'calendar' ); ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
						<?php if ( $a2e_updated ) : ?><span><?php a2e_the_icon( 'refresh' ); ?><?php /* translators: %s: date */ printf( esc_html__( 'Updated %s', 'a2e' ), esc_html( get_the_modified_date() ) ); ?></span><?php endif; ?>
						<span><?php a2e_the_icon( 'clock' ); ?><?php /* translators: %d: minutes */ printf( esc_html__( '%d min read', 'a2e' ), (int) a2e_reading_time() ); ?></span>
					</div>
				</div>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="article-cover"><?php the_post_thumbnail( 'a2e-hero', array( 'fetchpriority' => 'high' ) ); ?></figure>
		<?php endif; ?>

		<div class="article-layout">
			<?php if ( count( $a2e_toc ) > 1 ) : ?>
				<nav class="toc" aria-label="<?php esc_attr_e( 'On this page', 'a2e' ); ?>" data-toc>
					<p class="toc__title"><?php esc_html_e( 'On this page', 'a2e' ); ?></p>
					<ol>
						<?php foreach ( $a2e_toc as $a2e_h ) : ?><li class="lvl-<?php echo (int) $a2e_h[0]; ?>"><a href="#<?php echo esc_attr( $a2e_h[1] ); ?>"><?php echo esc_html( $a2e_h[2] ); ?></a></li><?php endforeach; ?>
					</ol>
				</nav>
			<?php else : ?>
				<div class="toc" aria-hidden="true"></div>
			<?php endif; ?>

			<div class="article-main" data-article>
				<?php if ( count( $a2e_toc ) > 1 ) : ?>
					<details class="toc-mobile"><summary><?php esc_html_e( 'On this page', 'a2e' ); ?></summary><ol><?php foreach ( $a2e_toc as $a2e_h ) : ?><li><a href="#<?php echo esc_attr( $a2e_h[1] ); ?>"><?php echo esc_html( $a2e_h[2] ); ?></a></li><?php endforeach; ?></ol></details>
				<?php endif; ?>
				<div class="prose"><?php the_content(); ?></div>
				<?php
				wp_link_pages();
				$a2e_tags = get_the_tags();
				if ( $a2e_tags ) :
					?>
					<div class="article-tags"><?php foreach ( $a2e_tags as $a2e_tag ) : ?><a class="tag" href="<?php echo esc_url( get_tag_link( $a2e_tag ) ); ?>">#<?php echo esc_html( $a2e_tag->name ); ?></a><?php endforeach; ?></div>
				<?php endif; ?>
				<?php echo a2e_inline_cta( __( 'Want this done for your store?', 'a2e' ), __( 'Get a free audit: we will review your listings and send a fixed-price plan.', 'a2e' ), __( 'Get my free audit', 'a2e' ), a2e_url( 'quote-request' ) ); // phpcs:ignore ?>
				<div class="author-box">
					<?php echo get_option( 'show_avatars' ) ? get_avatar( $a2e_author, 64 ) : '<span class="author-box__initial">' . esc_html( strtoupper( substr( get_the_author(), 0, 1 ) ) ) . '</span>'; // phpcs:ignore ?>
					<div>
						<p class="author-box__name"><?php the_author(); ?></p>
						<p><?php echo esc_html( get_the_author_meta( 'description' ) ? get_the_author_meta( 'description' ) : sprintf( /* translators: %s: year */ __( 'Part of the A2E Dropshipping team — helping eBay sellers research, list and grow since %s.', 'a2e' ), a2e_opt( 'since' ) ) ); ?></p>
					</div>
				</div>
			</div>

			<aside class="article-aside">
				<div class="aside-card aside-card--dark">
					<p class="aside-card__title"><?php esc_html_e( 'Let us run your eBay store', 'a2e' ); ?></p>
					<p><?php esc_html_e( 'Profit-checked research, SEO listings and daily management on one monthly plan.', 'a2e' ); ?></p>
					<a class="btn btn--light btn--sm btn--block" href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>"><span><?php esc_html_e( 'See plans', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
				</div>
				<div class="aside-card">
					<p class="aside-card__title"><?php esc_html_e( 'Free tools', 'a2e' ); ?></p>
					<ul>
						<?php foreach ( array( 'ebay-fee-calculator', 'amazon-to-ebay-profit-calculator', 'ebay-title-generator', 'ebay-product-research-scorecard' ) as $a2e_slug ) : $a2e_t = a2e_tools()[ $a2e_slug ]; ?>
							<li><a href="<?php echo esc_url( a2e_tool_url( $a2e_slug ) ); ?>"><?php a2e_the_icon( $a2e_t['icon'] ); ?><?php echo esc_html( $a2e_t['name'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="share">
					<a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><?php a2e_the_icon( 'linkedin' ); ?></a>
					<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php a2e_the_icon( 'facebook' ); ?></a>
					<a href="<?php echo esc_url( 'https://x.com/intent/post?url=' . rawurlencode( get_permalink() ) . '&text=' . rawurlencode( get_the_title() ) ); ?>" target="_blank" rel="noopener" aria-label="X"><?php a2e_the_icon( 'x' ); ?></a>
					<a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( get_the_title() . ' ' . get_permalink() ) ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?php a2e_the_icon( 'whatsapp' ); ?></a>
					<button type="button" data-copy-link title="<?php esc_attr_e( 'Copy link', 'a2e' ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'a2e' ); ?>"><?php a2e_the_icon( 'link' ); ?></button>
				</div>
			</aside>
		</div>
	</article>

	<?php if ( comments_open() || get_comments_number() ) : ?>
		<div class="comments"><?php comments_template(); ?></div>
	<?php endif; ?>

	<?php
	$a2e_related = new WP_Query(
		array(
			'post__not_in'        => array( get_the_ID() ),
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'category__in'        => $a2e_cats ? wp_list_pluck( $a2e_cats, 'term_id' ) : array(),
		)
	);
	if ( $a2e_related->have_posts() ) :
		?>
		<section class="related section--alt">
			<div class="container">
				<div class="sec-row"><?php a2e_section_head( array( 'eyebrow' => __( 'Keep reading', 'a2e' ), 'title' => __( 'Related guides', 'a2e' ), 'align' => 'left' ) ); ?></div>
				<div class="post-grid">
					<?php
					while ( $a2e_related->have_posts() ) {
						$a2e_related->the_post();
						get_template_part( 'template-parts/card-post' );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;
a2e_render_cta();
get_footer();
