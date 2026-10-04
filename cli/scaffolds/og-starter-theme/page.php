<?php
get_header();

$hero_title = get_post_meta( get_the_ID(), '_og_hero_title', true );
$hero_subtitle = get_post_meta( get_the_ID(), '_og_hero_subtitle', true );
$cta_text = get_post_meta( get_the_ID(), '_og_cta_text', true );
$cta_url = get_post_meta( get_the_ID(), '_og_cta_url', true );
?>

<?php if ( ! empty( $hero_title ) ) : ?>
	<section class="og-hero" style="background: #f7f9fc; padding: 4rem 1rem; text-align: center; border-bottom: 1px solid #eaeaea;">
		<h1 style="margin-top: 0; font-size: 3rem;"><?php echo esc_html( $hero_title ); ?></h1>
		<?php if ( ! empty( $hero_subtitle ) ) : ?>
			<p style="font-size: 1.25rem; color: #555; max-width: 800px; margin: 0 auto;"><?php echo esc_html( $hero_subtitle ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $cta_text ) && ! empty( $cta_url ) ) : ?>
			<div style="margin-top: 2rem;">
				<a href="<?php echo esc_url( $cta_url ); ?>" style="display: inline-block; background: #0073aa; color: #fff; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold;">
					<?php echo esc_html( $cta_text ); ?>
				</a>
			</div>
		<?php endif; ?>
	</section>
<?php endif; ?>

<main id="primary" class="site-main" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
<?php
while ( have_posts() ) :
	the_post();
	
	// Only show the default title if we don't have a custom hero title
	if ( empty( $hero_title ) ) {
		the_title( '<h1 class="entry-title">', '</h1>' );
	}
	
	the_content();
endwhile;
?>
</main>
<?php
get_footer();
