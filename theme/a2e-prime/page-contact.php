<?php
/**
 * /contact/
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Contact', 'a2e' ),
		'title'   => __( 'Talk to a real person, <em>not a ticket queue.</em>', 'a2e' ),
		'intro'   => __( 'Questions about a plan, your store or a subscription? WhatsApp is fastest; the form works just as well.', 'a2e' ),
	)
);
?>
<section class="section section--tight" id="contact">
	<div class="container"><?php a2e_render_contact(); ?></div>
</section>
<section class="section section--alt">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Before you write', 'a2e' ), 'title' => __( 'Quick <em>answers</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_faq( array_slice( a2e_site_faq(), 0, 5 ) ); ?>
	</div>
</section>
<?php
a2e_designed_extra_content();
get_footer();
