<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Headers {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		add_action( 'send_headers', array( $this, 'add_security_headers' ) );
	}

	public function add_security_headers() {
		if ( headers_sent() ) {
			return;
		}

		// X-Frame-Options to prevent clickjacking
		header( 'X-Frame-Options: SAMEORIGIN' );

		// X-Content-Type-Options to prevent MIME sniffing
		header( 'X-Content-Type-Options: nosniff' );

		// X-XSS-Protection (legacy but sometimes useful)
		header( 'X-XSS-Protection: 1; mode=block' );

		// Referrer-Policy
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );

		// Permissions-Policy (formerly Feature-Policy)
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

		// Content-Security-Policy
		header( 'Content-Security-Policy: upgrade-insecure-requests;' );

		// CORS Management
		header( 'Access-Control-Allow-Origin: ' . home_url() );
	}
}
