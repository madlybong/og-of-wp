<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Spam {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Comment spam
		add_filter( 'preprocess_comment', array( $this, 'filter_comment_spam' ) );

		// Registration email domain block
		add_filter( 'registration_errors', array( $this, 'block_disposable_emails' ), 10, 3 );

		// Add honeypot to comments
		add_action( 'comment_form_after_fields', array( $this, 'add_honeypot_field' ) );
		add_filter( 'preprocess_comment', array( $this, 'check_honeypot_field' ) );
	}

	public function filter_comment_spam( $commentdata ) {
		// If user is logged in and has roles, skip
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			return $commentdata;
		}

		$content = $commentdata['comment_content'];

		// Block comments with more than 2 links
		$link_count = substr_count( $content, 'http://' ) + substr_count( $content, 'https://' );
		if ( $link_count > 2 ) {
			wp_die( 'Spam Protection: Too many links in comment.' );
		}

		// Basic spam keywords
		$spam_keywords = [ 'viagra', 'cialis', 'casino', 'payday loan', 'seo services', 'buy cheap' ];
		$content_lower = strtolower( $content );
		foreach ( $spam_keywords as $keyword ) {
			if ( strpos( $content_lower, $keyword ) !== false ) {
				wp_die( 'Spam Protection: Comment contains blocked keywords.' );
			}
		}

		return $commentdata;
	}

	public function block_disposable_emails( $errors, $sanitized_user_login, $user_email ) {
		$disposable_domains = [
			'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'temp-mail.org', 
			'throwawaymail.com', 'yopmail.com'
		];

		$email_parts = explode( '@', $user_email );
		if ( count( $email_parts ) === 2 ) {
			$domain = strtolower( $email_parts[1] );
			if ( in_array( $domain, $disposable_domains ) ) {
				$errors->add( 'disposable_email', '<strong>ERROR</strong>: Please use a valid email address (disposable emails are blocked).' );
			}
		}

		return $errors;
	}

	public function add_honeypot_field() {
		// A hidden field bots will fill but humans won't see
		echo '<p style="display:none !important;" class="og-wp-secure-hp-field">';
		echo '<label for="og_wp_hp_email">Leave this field empty</label>';
		echo '<input type="text" name="og_wp_hp_email" id="og_wp_hp_email" value="" autocomplete="off" tabindex="-1" />';
		echo '</p>';
	}

	public function check_honeypot_field( $commentdata ) {
		// If honeypot is filled, it's a bot
		if ( ! empty( $_POST['og_wp_hp_email'] ) ) {
			wp_die( 'Spam Protection: Bot behavior detected.' );
		}
		return $commentdata;
	}
}
