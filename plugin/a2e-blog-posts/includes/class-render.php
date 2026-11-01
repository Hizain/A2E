<?php
/**
 * Builds a blog section: the posts query and the section markup. With A2E Prime it uses the
 * theme's own section heading, button and post cards, so it looks exactly like the rest of the
 * site in light and dark mode. Also provides the [a2e_blog_posts] shortcode.
 *
 * @package A2E_Blog_Posts
 */

namespace A2E\BlogPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render.
 */
class Render {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_shortcode( 'a2e_blog_posts', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * The category the visitor is looking at (a blog post's first category or a category page).
	 *
	 * @return int Term id, or 0.
	 */
	protected static function context_category() {
		if ( is_category() ) {
			return (int) get_queried_object_id();
		}
		if ( is_singular( 'post' ) ) {
			foreach ( get_the_category( get_queried_object_id() ) as $cat ) {
				if ( 'uncategorized' !== $cat->slug ) {
					return (int) $cat->term_id;
				}
			}
		}
		return 0;
	}

	/**
	 * Query the posts for a section.
	 *
	 * @param array $section Section settings.
	 * @param int   $cat     Category to use instead of the section's own (0 = the section's).
	 * @param array $skip    Post ids to leave out.
	 * @param int   $limit   How many (0 = the section's number).
	 * @return \WP_Query
	 */
	public static function query( $section, $cat = 0, $skip = array(), $limit = 0 ) {
		$orders = array(
			'newest'  => array( 'date', 'DESC' ),
			'oldest'  => array( 'date', 'ASC' ),
			'popular' => array( 'comment_count', 'DESC' ),
			'random'  => array( 'rand', 'DESC' ),
		);
		$order  = isset( $orders[ $section['order'] ] ) ? $orders[ $section['order'] ] : $orders['newest'];
		$args   = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit ? $limit : max( 1, min( 12, (int) $section['count'] ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => $order[0],
			'order'               => $order[1],
		);
		$cats = $cat ? array( (int) $cat ) : array_filter( array_map( 'intval', (array) $section['cats'] ) );
		if ( $cats ) {
			$args['category__in'] = $cats;
		}
		if ( is_singular( 'post' ) ) {
			$skip[] = (int) get_queried_object_id();
		} elseif ( ( is_category() || is_home() ) && ! empty( $GLOBALS['wp_query']->posts ) ) {
			// Do not repeat the posts already listed on this blog or category page.
			$skip = array_merge( $skip, wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
		}
		if ( $skip ) {
			$args['post__not_in'] = array_values( array_unique( array_map( 'intval', $skip ) ) );
		}
		return new \WP_Query( $args );
	}

	/**
	 * The posts for a section. When it follows the visitor's category and that category has
	 * fewer posts than wanted, the rest is filled from the section's own choice.
	 *
	 * @param array $section Section settings.
	 * @return array posts (WP_Post[]), cat (term id used, or 0).
	 */
	public static function posts( $section ) {
		$want  = max( 1, min( 12, (int) $section['count'] ) );
		$cat   = '1' === $section['match_cat'] ? self::context_category() : 0;
		$posts = self::query( $section, $cat )->posts;
		if ( $cat && count( $posts ) < $want ) {
			if ( ! $posts ) {
				$cat = 0; // Nothing else in that category: the button goes to the blog.
			}
			$more  = self::query( $section, 0, wp_list_pluck( $posts, 'ID' ), $want - count( $posts ) )->posts;
			$posts = array_merge( $posts, $more );
		}
		return array( 'posts' => $posts, 'cat' => $cat );
	}

	/**
	 * The HTML of a section (empty when there are no posts to show).
	 *
	 * @param array $section Section settings.
	 * @param bool  $inline  True inside page content (no full-width band).
	 * @return string
	 */
	public static function section_html( $section, $inline = false ) {
		$section = wp_parse_args( $section, section_defaults( isset( $section['id'] ) ? (int) $section['id'] : 0 ) );
		$found   = self::posts( $section );
		$posts   = $found['posts'];
		$cat     = $found['cat'];
		if ( ! $posts ) {
			return '';
		}

		// Where the button goes: your link, the category being shown, or the blog page.
		$cats = array_filter( array_map( 'intval', (array) $section['cats'] ) );
		$link = trim( (string) $section['link'] );
		if ( '' === $link ) {
			if ( $cat ) {
				$link = get_category_link( $cat );
			} elseif ( 1 === count( $cats ) ) {
				$link = get_category_link( reset( $cats ) );
			} else {
				$link = blog_url();
			}
		}

		$cols    = max( 2, min( 4, (int) $section['columns'] ) );
		$classes = 'a2ebp a2ebp--cols-' . $cols . ( $inline ? ' a2ebp--inline' : '' ) . ( theme_active() ? '' : ' a2ebp--own' );

		ob_start();
		if ( $inline ) {
			echo '<div class="' . esc_attr( $classes ) . '">';
		} else {
			echo '<section class="section' . ( 'alt' === $section['style'] ? ' section--alt' : '' ) . ' ' . esc_attr( $classes ) . '" id="' . esc_attr( 'a2e-blog-' . (int) $section['id'] ) . '">';
			echo '<div class="container">';
		}

		if ( theme_active() ) {
			self::theme_markup( $section, $posts, $link );
		} else {
			self::own_markup( $section, $posts, $link );
		}

		echo $inline ? '</div>' : '</div></section>';
		wp_reset_postdata();
		return trim( ob_get_clean() );
	}

	/**
	 * Heading row and cards using A2E Prime's own components.
	 *
	 * @param array  $section Section.
	 * @param array  $posts   Posts.
	 * @param string $link    Button link.
	 */
	protected static function theme_markup( $section, $posts, $link ) {
		global $post;
		$has_head = '' !== trim( $section['title'] ) || '' !== trim( $section['eyebrow'] );
		if ( $has_head || '' !== trim( $section['button'] ) ) {
			echo '<div class="sec-row">';
			if ( $has_head ) {
				a2e_section_head(
					array(
						'eyebrow' => $section['eyebrow'],
						'title'   => function_exists( 'a2e_format_headline' ) ? a2e_format_headline( $section['title'] ) : esc_html( $section['title'] ),
						'intro'   => esc_html( $section['intro'] ),
						'align'   => 'left',
					)
				);
			}
			if ( '' !== trim( $section['button'] ) ) {
				echo '<a class="btn btn--ghost" href="' . esc_url( $link ) . '" data-reveal><span>' . esc_html( $section['button'] ) . '</span>';
				if ( function_exists( 'a2e_the_icon' ) ) {
					a2e_the_icon( 'arrow-right', 'btn__icon' );
				}
				echo '</a>';
			}
			echo '</div>';
		}
		echo '<div class="post-grid a2ebp-grid">';
		foreach ( $posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			get_template_part( 'template-parts/card-post' );
		}
		echo '</div>';
	}

	/**
	 * Self-contained markup for other themes.
	 *
	 * @param array  $section Section.
	 * @param array  $posts   Posts.
	 * @param string $link    Button link.
	 */
	protected static function own_markup( $section, $posts, $link ) {
		global $post;
		echo '<div class="a2ebp-head">';
		echo '<div>';
		if ( '' !== trim( $section['eyebrow'] ) ) {
			echo '<p class="a2ebp-eyebrow">' . esc_html( $section['eyebrow'] ) . '</p>';
		}
		if ( '' !== trim( $section['title'] ) ) {
			echo '<h2 class="a2ebp-title">' . preg_replace( '/\*(.+?)\*/', '<em>$1</em>', esc_html( $section['title'] ) ) . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( '' !== trim( $section['intro'] ) ) {
			echo '<p class="a2ebp-intro">' . esc_html( $section['intro'] ) . '</p>';
		}
		echo '</div>';
		if ( '' !== trim( $section['button'] ) ) {
			echo '<a class="a2ebp-btn" href="' . esc_url( $link ) . '">' . esc_html( $section['button'] ) . ' &rarr;</a>';
		}
		echo '</div><div class="a2ebp-grid">';
		foreach ( $posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			$cats = get_the_category();
			echo '<article class="a2ebp-card">';
			echo '<a class="a2ebp-card__img" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">';
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) );
			}
			echo '</a><div class="a2ebp-card__body">';
			if ( $cats && 'uncategorized' !== $cats[0]->slug ) {
				echo '<a class="a2ebp-card__cat" href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
			}
			echo '<h3 class="a2ebp-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
			echo '<p class="a2ebp-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), 22 ) ) . '</p>';
			echo '<time class="a2ebp-card__date" datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>';
			echo '</div></article>';
		}
		echo '</div>';
	}

	/**
	 * [a2e_blog_posts] shortcode. Use a saved section (section="2") and/or set anything directly:
	 * category="ebay,shopify" count="6" columns="3" order="newest|popular|random|oldest"
	 * eyebrow="" title="" intro="" button="" link="" heading="no".
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts    = is_array( $atts ) ? $atts : array();
		$all     = settings()['sections'];
		$base_id = isset( $atts['section'] ) ? (int) $atts['section'] : 0;
		$section = $base_id && isset( $all[ $base_id ] ) ? $all[ $base_id ] : section_defaults( 0 );
		// A shortcode shows what it is told to; it does not follow the visitor's category.
		$section['match_cat'] = '0';
		$section['intro']     = $base_id ? $section['intro'] : '';
		$section['id']        = $base_id ? $base_id : 0;

		if ( isset( $atts['category'] ) ) {
			$ids = array();
			foreach ( array_filter( array_map( 'trim', explode( ',', (string) $atts['category'] ) ) ) as $c ) {
				$term = is_numeric( $c ) ? get_term( (int) $c, 'category' ) : get_term_by( 'slug', sanitize_title( $c ), 'category' );
				if ( $term && ! is_wp_error( $term ) ) {
					$ids[] = (int) $term->term_id;
				}
			}
			$section['cats'] = $ids;
		}
		foreach ( array( 'count', 'columns' ) as $key ) {
			if ( isset( $atts[ $key ] ) ) {
				$section[ $key ] = (int) $atts[ $key ];
			}
		}
		if ( isset( $atts['order'] ) && array_key_exists( $atts['order'], order_options() ) ) {
			$section['order'] = $atts['order'];
		}
		foreach ( array( 'eyebrow', 'title', 'intro', 'button' ) as $key ) {
			if ( isset( $atts[ $key ] ) ) {
				$section[ $key ] = sanitize_text_field( $atts[ $key ] );
			}
		}
		if ( isset( $atts['link'] ) ) {
			$section['link'] = esc_url_raw( $atts['link'] );
		}
		if ( isset( $atts['heading'] ) && in_array( strtolower( $atts['heading'] ), array( 'no', '0', 'false', 'off' ), true ) ) {
			$section['eyebrow'] = '';
			$section['title']   = '';
			$section['intro']   = '';
		}
		return self::section_html( $section, true );
	}
}
