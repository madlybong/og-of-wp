<?php
/**
 * OG Starter Theme functions and definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function og_starter_setup() {
	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Let WordPress manage the document title natively.
	add_theme_support( 'title-tag' );

	// Enable support for Post Thumbnails on posts and pages.
	add_theme_support( 'post-thumbnails' );

	// Switch default core markup to output valid HTML5.
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Add support for core custom logo.
	add_theme_support( 'custom-logo', array(
		'height'      => 250,
		'width'       => 250,
		'flex-width'  => true,
		'flex-height' => true,
	) );

	// Gutenberg support
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'og_starter_setup' );

/**
 * Enqueue scripts and styles.
 */
function og_starter_scripts() {
	wp_enqueue_style( 'og-starter-style', get_stylesheet_uri(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'og_starter_scripts' );

/**
 * NATIVE META BOXES (The Anti-Bloat ACF Alternative)
 */
function og_starter_add_meta_boxes() {
	add_meta_box( 'og_starter_page_options', 'Page Configuration', 'og_starter_render_page_options', 'page', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'og_starter_add_meta_boxes' );

function og_starter_render_page_options( $post ) {
	wp_nonce_field( 'og_starter_save_page_options', 'og_starter_page_options_nonce' );

	$hero_title = get_post_meta( $post->ID, '_og_hero_title', true );
	$hero_subtitle = get_post_meta( $post->ID, '_og_hero_subtitle', true );
	$cta_text = get_post_meta( $post->ID, '_og_cta_text', true );
	$cta_url = get_post_meta( $post->ID, '_og_cta_url', true );

	echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
	
	echo '<div><label style="font-weight:bold;">Hero Title:</label><br>';
	echo '<input type="text" name="og_hero_title" value="' . esc_attr( $hero_title ) . '" style="width:100%; max-width:600px;" /></div>';
	
	echo '<div><label style="font-weight:bold;">Hero Subtitle:</label><br>';
	echo '<input type="text" name="og_hero_subtitle" value="' . esc_attr( $hero_subtitle ) . '" style="width:100%; max-width:600px;" /></div>';
	
	echo '<div><label style="font-weight:bold;">CTA Text:</label><br>';
	echo '<input type="text" name="og_cta_text" value="' . esc_attr( $cta_text ) . '" style="width:100%; max-width:600px;" /></div>';
	
	echo '<div><label style="font-weight:bold;">CTA URL:</label><br>';
	echo '<input type="text" name="og_cta_url" value="' . esc_attr( $cta_url ) . '" style="width:100%; max-width:600px;" /></div>';
	
	echo '</div>';
}

function og_starter_save_page_options( $post_id ) {
	if ( ! isset( $_POST['og_starter_page_options_nonce'] ) || ! wp_verify_nonce( $_POST['og_starter_page_options_nonce'], 'og_starter_save_page_options' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	$fields = [ 'og_hero_title', 'og_hero_subtitle', 'og_cta_text', 'og_cta_url' ];
	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
}
add_action( 'save_post', 'og_starter_save_page_options' );
