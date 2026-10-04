<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_SEO {
	private $options;

	public function __construct($options = []) {
		$this->options = $options;
		
		add_action( 'add_meta_boxes', array( $this, 'add_seo_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_seo_meta_box' ) );
		
		// Inject into head
		add_action( 'wp_head', array( $this, 'inject_seo_tags' ), 2 );
		
		// Override title
		add_filter( 'pre_get_document_title', array( $this, 'override_document_title' ), 999 );
	}

	public function add_seo_meta_box() {
		$screens = [ 'post', 'page' ];
		foreach ( $screens as $screen ) {
			add_meta_box(
				'og_wp_seo_box',
				'SEO Settings (OG of WP)',
				array( $this, 'render_seo_meta_box' ),
				$screen,
				'normal',
				'high'
			);
		}
	}

	public function render_seo_meta_box( $post ) {
		wp_nonce_field( 'og_wp_seo_save', 'og_wp_seo_nonce' );
		
		$seo_title = get_post_meta( $post->ID, '_og_wp_seo_title', true );
		$seo_desc  = get_post_meta( $post->ID, '_og_wp_seo_desc', true );
		$noindex   = get_post_meta( $post->ID, '_og_wp_seo_noindex', true );

		echo '<div style="display: flex; flex-direction: column; gap: 15px;">';
		
		echo '<div>';
		echo '<label for="og_wp_seo_title" style="font-weight:bold;display:block;margin-bottom:5px;">SEO Title Override</label>';
		echo '<input type="text" id="og_wp_seo_title" name="og_wp_seo_title" value="' . esc_attr( $seo_title ) . '" style="width:100%; max-width:600px;" placeholder="Leave blank to use default WordPress title" />';
		echo '</div>';

		echo '<div>';
		echo '<label for="og_wp_seo_desc" style="font-weight:bold;display:block;margin-bottom:5px;">Meta Description</label>';
		echo '<textarea id="og_wp_seo_desc" name="og_wp_seo_desc" rows="3" style="width:100%; max-width:600px;">' . esc_textarea( $seo_desc ) . '</textarea>';
		echo '</div>';

		echo '<div>';
		echo '<label style="font-weight:bold;display:flex;align-items:center;gap:10px;">';
		echo '<input type="checkbox" name="og_wp_seo_noindex" value="1" ' . checked( 1, $noindex, false ) . ' />';
		echo 'Hide this page from search engines (noindex)';
		echo '</label>';
		echo '</div>';

		echo '</div>';
	}

	public function save_seo_meta_box( $post_id ) {
		if ( ! isset( $_POST['og_wp_seo_nonce'] ) || ! wp_verify_nonce( $_POST['og_wp_seo_nonce'], 'og_wp_seo_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['og_wp_seo_title'] ) ) {
			update_post_meta( $post_id, '_og_wp_seo_title', sanitize_text_field( wp_unslash( $_POST['og_wp_seo_title'] ) ) );
		}

		if ( isset( $_POST['og_wp_seo_desc'] ) ) {
			update_post_meta( $post_id, '_og_wp_seo_desc', sanitize_textarea_field( wp_unslash( $_POST['og_wp_seo_desc'] ) ) );
		}

		if ( isset( $_POST['og_wp_seo_noindex'] ) && $_POST['og_wp_seo_noindex'] === '1' ) {
			update_post_meta( $post_id, '_og_wp_seo_noindex', '1' );
		} else {
			delete_post_meta( $post_id, '_og_wp_seo_noindex' );
		}
	}

	public function override_document_title( $title ) {
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$custom_title = get_post_meta( $post_id, '_og_wp_seo_title', true );
			if ( ! empty( $custom_title ) ) {
				return $custom_title;
			}
		}
		return $title;
	}

	public function inject_seo_tags() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		
		$noindex = get_post_meta( $post_id, '_og_wp_seo_noindex', true );
		if ( $noindex === '1' ) {
			echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
		}

		$desc = get_post_meta( $post_id, '_og_wp_seo_desc', true );
		if ( ! empty( $desc ) ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
		}

		$title = get_post_meta( $post_id, '_og_wp_seo_title', true );
		if ( empty( $title ) ) {
			$title = wp_get_document_title();
		}

		// OpenGraph
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( ! empty( $desc ) ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
		}
		echo '<meta property="og:url" content="' . esc_url( get_permalink( $post_id ) ) . '" />' . "\n";
		echo '<meta property="og:type" content="article" />' . "\n";
		
		if ( has_post_thumbnail( $post_id ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
			if ( $image ) {
				echo '<meta property="og:image" content="' . esc_url( $image[0] ) . '" />' . "\n";
				echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
			}
		} else {
			echo '<meta name="twitter:card" content="summary" />' . "\n";
		}
		
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( ! empty( $desc ) ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
		}
	}
}
