<?php
get_header();
?>
<main id="primary" class="site-main" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;
?>
</main>
<?php
get_footer();
