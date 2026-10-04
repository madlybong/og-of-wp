<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<header id="masthead" class="site-header" style="display:flex; justify-content:space-between; padding: 1rem; border-bottom: 1px solid #ddd;">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php class_exists( 'OG_WP' ) ? the_custom_logo() : ''; ?>
			<?php else : ?>
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<?php endif; ?>
		</div>
		<nav id="site-navigation" class="main-navigation">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'menu-1',
				'menu_id'        => 'primary-menu',
				'fallback_cb'    => false,
			) );
			?>
		</nav>
	</header>
