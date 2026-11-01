<?php
/**
 * Settings screen: one card per blog section (where it shows, where on the page, which
 * categories, how many posts, heading and button), plus the general options.
 *
 * @package A2E_Blog_Posts
 */

namespace A2E\BlogPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin.
 */
class Admin {

	const SLUG = 'a2e-blog-posts';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_a2ebp_save', array( __CLASS__, 'save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( A2EBP_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Under the theme's A2E menu when it is there, otherwise under Settings.
	 */
	public static function menu() {
		global $admin_page_hooks;
		$parent = isset( $admin_page_hooks['a2e-settings'] ) ? 'a2e-settings' : 'options-general.php';
		add_submenu_page( $parent, __( 'Blog posts on your pages', 'a2e-blog-posts' ), __( 'Blog Posts', 'a2e-blog-posts' ), 'manage_options', self::SLUG, array( __CLASS__, 'render' ) );
	}

	/**
	 * The settings page URL.
	 *
	 * @return string
	 */
	public static function url() {
		return admin_url( 'admin.php?page=' . self::SLUG );
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'a2e-blog-posts' ) . '</a>' );
		return $links;
	}

	/**
	 * Assets on our screen.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'a2ebp-admin', A2EBP_URL . 'assets/admin.css', array(), VERSION );
		wp_enqueue_script( 'a2ebp-admin', A2EBP_URL . 'assets/admin.js', array(), VERSION, true );
	}

	/**
	 * Save handler.
	 */
	public static function save() {
		check_admin_referer( 'a2ebp_save' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-blog-posts' ) );
		}
		$in       = isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pages    = wp_list_pluck( get_pages( array( 'post_status' => 'publish' ) ), 'ID' );
		$cats     = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'ids' ) );
		$cats     = is_wp_error( $cats ) ? array() : array_map( 'intval', $cats );
		$sections = array();
		foreach ( $in as $raw ) {
			$id = isset( $raw['id'] ) ? absint( $raw['id'] ) : 0;
			if ( ! $id || ! is_array( $raw ) ) {
				continue;
			}
			$text = function ( $key ) use ( $raw ) {
				return isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
			};
			$list = function ( $key ) use ( $raw ) {
				return isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ? $raw[ $key ] : array();
			};
			$sections[ $id ] = array(
				'id'        => $id,
				'enabled'   => empty( $raw['enabled'] ) ? '0' : '1',
				'name'      => $text( 'name' ) ? $text( 'name' ) : sprintf( __( 'Blog section %d', 'a2e-blog-posts' ), $id ),
				'where'     => array_values( array_intersect( array_keys( where_options() ), array_map( 'sanitize_key', $list( 'where' ) ) ) ),
				'pages'     => array_values( array_intersect( array_map( 'intval', $pages ), array_map( 'intval', $list( 'pages' ) ) ) ),
				'position'  => array_key_exists( $text( 'position' ), position_options() ) ? $text( 'position' ) : 'before_cta',
				'cats'      => array_values( array_intersect( $cats, array_map( 'intval', $list( 'cats' ) ) ) ),
				'match_cat' => empty( $raw['match_cat'] ) ? '0' : '1',
				'count'     => max( 1, min( 12, (int) $text( 'count' ) ) ),
				'order'     => array_key_exists( $text( 'order' ), order_options() ) ? $text( 'order' ) : 'newest',
				'columns'   => max( 2, min( 4, (int) $text( 'columns' ) ) ),
				'eyebrow'   => $text( 'eyebrow' ),
				'title'     => $text( 'title' ),
				'intro'     => isset( $raw['intro'] ) ? sanitize_textarea_field( $raw['intro'] ) : '',
				'button'    => $text( 'button' ),
				'link'      => isset( $raw['link'] ) ? esc_url_raw( trim( $raw['link'] ) ) : '',
				'style'     => 'plain' === $text( 'style' ) ? 'plain' : 'alt',
			);
		}
		update_settings(
			array(
				'fix_chips'        => empty( $_POST['fix_chips'] ) ? '0' : '1',
				'hide_theme_block' => empty( $_POST['hide_theme_block'] ) ? '0' : '1',
				'sections'         => $sections,
			)
		);
		set_transient( 'a2ebp_notice_' . get_current_user_id(), __( 'Saved. Your blog sections are live.', 'a2e-blog-posts' ), 60 );
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * A page where a section shows, for the "View it" link.
	 *
	 * @param array $section Section.
	 * @return string
	 */
	protected static function view_url( $section ) {
		$where = (array) $section['where'];
		if ( in_array( 'front', $where, true ) || in_array( 'all_pages', $where, true ) ) {
			return home_url( '/' );
		}
		if ( $section['pages'] ) {
			return get_permalink( (int) $section['pages'][0] );
		}
		if ( in_array( 'blog', $where, true ) ) {
			return blog_url();
		}
		if ( in_array( 'posts', $where, true ) ) {
			$p = get_posts( array( 'numberposts' => 1, 'fields' => 'ids' ) );
			return $p ? get_permalink( $p[0] ) : '';
		}
		if ( in_array( 'services', $where, true ) ) {
			$p = get_posts( array( 'post_type' => 'a2e_service', 'numberposts' => 1, 'fields' => 'ids' ) );
			return $p ? get_permalink( $p[0] ) : '';
		}
		if ( in_array( 'categories', $where, true ) ) {
			$c = get_categories( array( 'number' => 1, 'orderby' => 'count', 'order' => 'DESC' ) );
			return $c ? get_category_link( $c[0] ) : '';
		}
		return '';
	}

	/**
	 * One line on where a section shows.
	 *
	 * @param array $section Section.
	 * @return string
	 */
	protected static function where_summary( $section ) {
		$labels = array(
			'front'      => __( 'Homepage', 'a2e-blog-posts' ),
			'all_pages'  => __( 'every page', 'a2e-blog-posts' ),
			'services'   => __( 'service guides', 'a2e-blog-posts' ),
			'posts'      => __( 'blog posts', 'a2e-blog-posts' ),
			'categories' => __( 'category pages', 'a2e-blog-posts' ),
			'blog'       => __( 'blog page', 'a2e-blog-posts' ),
		);
		$parts = array();
		foreach ( (array) $section['where'] as $w ) {
			if ( isset( $labels[ $w ] ) ) {
				$parts[] = $labels[ $w ];
			}
		}
		if ( $section['pages'] ) {
			/* translators: %d: number of pages */
			$parts[] = sprintf( _n( '%d chosen page', '%d chosen pages', count( $section['pages'] ), 'a2e-blog-posts' ), count( $section['pages'] ) );
		}
		return $parts ? ucfirst( implode( ', ', $parts ) ) : __( 'Not placed on any page yet (shortcode only)', 'a2e-blog-posts' );
	}

	/**
	 * Render the settings screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = settings();
		$pages  = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'menu_order,post_title' ) );
		$cats   = get_categories( array( 'hide_empty' => false, 'orderby' => 'name' ) );
		$next   = $s['sections'] ? max( array_keys( $s['sections'] ) ) + 1 : 1;
		$notice = get_transient( 'a2ebp_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'a2ebp_notice_' . get_current_user_id() );
		}
		?>
		<div class="wrap a2ebp-admin">
			<h1><?php esc_html_e( 'Blog posts on your pages', 'a2e-blog-posts' ); ?></h1>
			<p class="a2ebp-lead"><?php esc_html_e( 'Show your latest guides as a designed "From the blog" section on the homepage and on any page you choose. Each section can show every category or only the ones you pick. Sections use your theme\'s own cards, so they match the site in light and dark mode.', 'a2e-blog-posts' ); ?></p>
			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! theme_active() ) : ?>
				<div class="notice notice-info"><p><?php esc_html_e( 'The A2E Prime theme is not active, so sections use a simple built-in card design.', 'a2e-blog-posts' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'a2ebp_save' ); ?>
				<input type="hidden" name="action" value="a2ebp_save">

				<div class="a2ebp-card">
					<h2><?php esc_html_e( 'General', 'a2e-blog-posts' ); ?></h2>
					<label class="a2ebp-check"><input type="checkbox" name="fix_chips" value="1" <?php checked( '1', $s['fix_chips'] ); ?>> <?php esc_html_e( 'Centre the text in the blog page\'s category buttons (All, eBay, Amazon to eBay Dropshipping...)', 'a2e-blog-posts' ); ?></label>
					<label class="a2ebp-check"><input type="checkbox" name="hide_theme_block" value="1" <?php checked( '1', $s['hide_theme_block'] ); ?>> <?php esc_html_e( 'While a section here is on the homepage, hide the theme\'s own homepage blog block so posts are not shown twice', 'a2e-blog-posts' ); ?></label>
				</div>

				<div id="a2ebp-sections" data-next="<?php echo (int) $next; ?>">
					<?php
					foreach ( $s['sections'] as $section ) {
						self::section_card( $section, $pages, $cats );
					}
					?>
				</div>
				<p class="a2ebp-empty"<?php echo $s['sections'] ? ' hidden' : ''; ?>><?php esc_html_e( 'No blog sections yet. Add one below.', 'a2e-blog-posts' ); ?></p>

				<div class="a2ebp-bar">
					<button type="button" class="button" id="a2ebp-add"><?php esc_html_e( 'Add another blog section', 'a2e-blog-posts' ); ?></button>
					<?php submit_button( __( 'Save changes', 'a2e-blog-posts' ), 'primary', 'submit', false ); ?>
				</div>
			</form>

			<div class="a2ebp-card a2ebp-shortcodes">
				<h2><?php esc_html_e( 'Place it inside any page or post (shortcode)', 'a2e-blog-posts' ); ?></h2>
				<p><?php esc_html_e( 'Add a Shortcode block in the editor and paste one of these:', 'a2e-blog-posts' ); ?></p>
				<ul>
					<li><code>[a2e_blog_posts]</code> <?php esc_html_e( 'your 3 newest posts', 'a2e-blog-posts' ); ?></li>
					<li><code>[a2e_blog_posts category="ebay" count="6"]</code> <?php esc_html_e( 'the 6 newest posts from one category (use the category slug; separate several with commas)', 'a2e-blog-posts' ); ?></li>
					<li><code>[a2e_blog_posts section="1"]</code> <?php esc_html_e( 'exactly like a section above (its number is shown on each card)', 'a2e-blog-posts' ); ?></li>
					<li><code>[a2e_blog_posts title="Read next" button="" heading="no"]</code> <?php esc_html_e( 'change the heading or button, or hide the heading', 'a2e-blog-posts' ); ?></li>
				</ul>
				<?php if ( $cats ) : ?>
					<p class="a2ebp-muted">
						<?php esc_html_e( 'Your category slugs:', 'a2e-blog-posts' ); ?>
						<?php echo esc_html( implode( ', ', wp_list_pluck( $cats, 'slug' ) ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<template id="a2ebp-template"><?php self::section_card( section_defaults( 0 ), $pages, $cats, true ); ?></template>
		</div>
		<?php
	}

	/**
	 * One section card.
	 *
	 * @param array $section  Section.
	 * @param array $pages    Published pages.
	 * @param array $cats     Categories.
	 * @param bool  $template True for the "add" template (id placeholder).
	 */
	protected static function section_card( $section, $pages, $cats, $template = false ) {
		$id   = $template ? '__ID__' : (int) $section['id'];
		$name = 'sections[' . $id . ']';
		$on   = '1' === $section['enabled'];
		$view = $template ? '' : self::view_url( $section );
		?>
		<details class="a2ebp-sec" open data-id="<?php echo esc_attr( $id ); ?>">
			<summary>
				<span class="a2ebp-sec__name"><?php echo esc_html( $template ? __( 'New blog section', 'a2e-blog-posts' ) : $section['name'] ); ?></span>
				<span class="a2ebp-pill <?php echo $on ? 'is-on' : ''; ?>"><?php echo $on ? esc_html__( 'On', 'a2e-blog-posts' ) : esc_html__( 'Off', 'a2e-blog-posts' ); ?></span>
				<span class="a2ebp-sec__where"><?php echo esc_html( $template ? '' : self::where_summary( $section ) ); ?></span>
			</summary>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $id ); ?>">

			<div class="a2ebp-grid">
				<div class="a2ebp-col">
					<p class="a2ebp-field a2ebp-row">
						<label class="a2ebp-check a2ebp-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $on ); ?>> <?php esc_html_e( 'Show this section', 'a2e-blog-posts' ); ?></label>
					</p>
					<p class="a2ebp-field">
						<label><?php esc_html_e( 'Name (only you see this)', 'a2e-blog-posts' ); ?></label>
						<input type="text" class="widefat" name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $section['name'] ); ?>">
					</p>

					<fieldset class="a2ebp-field">
						<legend><?php esc_html_e( 'Show it on', 'a2e-blog-posts' ); ?></legend>
						<?php foreach ( where_options() as $key => $label ) : ?>
							<label class="a2ebp-check"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[where][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, (array) $section['where'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</fieldset>

					<div class="a2ebp-field">
						<label><?php esc_html_e( 'And on these pages', 'a2e-blog-posts' ); ?></label>
						<input type="search" class="widefat a2ebp-filter" placeholder="<?php esc_attr_e( 'Type to find a page', 'a2e-blog-posts' ); ?>">
						<div class="a2ebp-list">
							<?php
							foreach ( $pages as $page ) :
								$depth = count( get_post_ancestors( $page ) );
								?>
								<label class="a2ebp-check" style="padding-left:<?php echo (int) $depth * 16; ?>px"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[pages][]" value="<?php echo (int) $page->ID; ?>" <?php checked( in_array( (int) $page->ID, array_map( 'intval', (array) $section['pages'] ), true ) ); ?>> <span><?php echo esc_html( $page->post_title ? $page->post_title : __( '(no title)', 'a2e-blog-posts' ) ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</div>

					<fieldset class="a2ebp-field">
						<legend><?php esc_html_e( 'Where on the page', 'a2e-blog-posts' ); ?></legend>
						<?php foreach ( position_options() as $key => $label ) : ?>
							<label class="a2ebp-check"><input type="radio" name="<?php echo esc_attr( $name ); ?>[position]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $section['position'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				</div>

				<div class="a2ebp-col">
					<div class="a2ebp-field">
						<label><?php esc_html_e( 'Posts from these categories', 'a2e-blog-posts' ); ?></label>
						<div class="a2ebp-list a2ebp-list--short">
							<?php foreach ( $cats as $cat ) : ?>
								<label class="a2ebp-check"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[cats][]" value="<?php echo (int) $cat->term_id; ?>" <?php checked( in_array( (int) $cat->term_id, array_map( 'intval', (array) $section['cats'] ), true ) ); ?>> <span><?php echo esc_html( $cat->name ); ?> <em>(<?php echo (int) $cat->count; ?>)</em></span></label>
							<?php endforeach; ?>
						</div>
						<span class="a2ebp-help"><?php esc_html_e( 'Leave them all unticked to show every category.', 'a2e-blog-posts' ); ?></span>
						<label class="a2ebp-check"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[match_cat]" value="1" <?php checked( '1', $section['match_cat'] ); ?>> <?php esc_html_e( 'On a blog post or category page, show posts from that same category', 'a2e-blog-posts' ); ?></label>
					</div>

					<div class="a2ebp-three">
						<p class="a2ebp-field">
							<label><?php esc_html_e( 'Posts', 'a2e-blog-posts' ); ?></label>
							<select name="<?php echo esc_attr( $name ); ?>[count]">
								<?php for ( $n = 1; $n <= 12; $n++ ) : ?>
									<option value="<?php echo (int) $n; ?>" <?php selected( (int) $section['count'], $n ); ?>><?php echo (int) $n; ?></option>
								<?php endfor; ?>
							</select>
						</p>
						<p class="a2ebp-field">
							<label><?php esc_html_e( 'Columns', 'a2e-blog-posts' ); ?></label>
							<select name="<?php echo esc_attr( $name ); ?>[columns]">
								<?php foreach ( array( 2, 3, 4 ) as $n ) : ?>
									<option value="<?php echo (int) $n; ?>" <?php selected( (int) $section['columns'], $n ); ?>><?php echo (int) $n; ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p class="a2ebp-field">
							<label><?php esc_html_e( 'Order', 'a2e-blog-posts' ); ?></label>
							<select name="<?php echo esc_attr( $name ); ?>[order]">
								<?php foreach ( order_options() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $section['order'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
					</div>

					<p class="a2ebp-field">
						<label><?php esc_html_e( 'Small label above the heading', 'a2e-blog-posts' ); ?></label>
						<input type="text" class="widefat" name="<?php echo esc_attr( $name ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>">
					</p>
					<p class="a2ebp-field">
						<label><?php esc_html_e( 'Heading', 'a2e-blog-posts' ); ?></label>
						<input type="text" class="widefat" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>">
						<span class="a2ebp-help"><?php esc_html_e( 'Wrap words in *asterisks* for the gradient highlight, like the rest of your site.', 'a2e-blog-posts' ); ?></span>
					</p>
					<p class="a2ebp-field">
						<label><?php esc_html_e( 'Intro (optional)', 'a2e-blog-posts' ); ?></label>
						<textarea class="widefat" rows="2" name="<?php echo esc_attr( $name ); ?>[intro]"><?php echo esc_textarea( $section['intro'] ); ?></textarea>
					</p>
					<div class="a2ebp-two">
						<p class="a2ebp-field">
							<label><?php esc_html_e( 'Button text (empty = no button)', 'a2e-blog-posts' ); ?></label>
							<input type="text" class="widefat" name="<?php echo esc_attr( $name ); ?>[button]" value="<?php echo esc_attr( $section['button'] ); ?>">
						</p>
						<p class="a2ebp-field">
							<label><?php esc_html_e( 'Button link (optional)', 'a2e-blog-posts' ); ?></label>
							<input type="url" class="widefat" name="<?php echo esc_attr( $name ); ?>[link]" value="<?php echo esc_attr( $section['link'] ); ?>" placeholder="<?php esc_attr_e( 'Blank = your blog page', 'a2e-blog-posts' ); ?>">
						</p>
					</div>
					<fieldset class="a2ebp-field">
						<legend><?php esc_html_e( 'Background', 'a2e-blog-posts' ); ?></legend>
						<label class="a2ebp-check a2ebp-inline"><input type="radio" name="<?php echo esc_attr( $name ); ?>[style]" value="alt" <?php checked( $section['style'], 'alt' ); ?>> <?php esc_html_e( 'Soft band (like the theme\'s own blog block)', 'a2e-blog-posts' ); ?></label>
						<label class="a2ebp-check a2ebp-inline"><input type="radio" name="<?php echo esc_attr( $name ); ?>[style]" value="plain" <?php checked( $section['style'], 'plain' ); ?>> <?php esc_html_e( 'Plain', 'a2e-blog-posts' ); ?></label>
					</fieldset>
				</div>
			</div>

			<div class="a2ebp-sec__foot">
				<span class="a2ebp-muted"><?php esc_html_e( 'Shortcode:', 'a2e-blog-posts' ); ?> <code>[a2e_blog_posts section="<?php echo esc_html( $id ); ?>"]</code></span>
				<?php if ( $view ) : ?>
					<a href="<?php echo esc_url( $view ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View it on the site', 'a2e-blog-posts' ); ?></a>
				<?php endif; ?>
				<button type="button" class="button-link a2ebp-remove"><?php esc_html_e( 'Remove this section', 'a2e-blog-posts' ); ?></button>
			</div>
		</details>
		<?php
	}
}
