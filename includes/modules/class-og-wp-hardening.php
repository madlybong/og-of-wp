<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Hardening {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Hide WP Version
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'style_loader_src', array( $this, 'remove_version_scripts_styles' ), 9999 );
		add_filter( 'script_loader_src', array( $this, 'remove_version_scripts_styles' ), 9999 );

		// Disable Pingback/Trackback
		add_filter( 'pings_open', '__return_false', 9999 );
		add_filter( 'pre_option_default_ping_status', '__return_false' );
		add_action( 'pre_ping', array( $this, 'disable_pingbacks' ) );

		// Comment Author URL Stripping
		add_filter( 'get_comment_author_url', '__return_empty_string' );

		// Remove WP Readme & License (via request interception)
		add_action( 'init', array( $this, 'block_sensitive_files' ) );

		// Disable WP Cron
		if ( ! defined( 'DISABLE_WP_CRON' ) ) {
			// We can't define it if already defined, and doing it in a plugin is late,
			// but we can hook into cron schedule and prevent execution via HTTP
			// A better approach is to just remove the cron action if it's an HTTP request
			if ( isset( $_GET['doing_wp_cron'] ) ) {
				// We don't block it here if we want server cron to work, but if we want to disable HTTP cron:
				add_filter( 'schedule_event', '__return_false' ); 
			}
		}
	}

	public function remove_version_scripts_styles( $src ) {
		if ( strpos( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	public function disable_pingbacks( &$links ) {
		$home = get_option( 'home' );
		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[$l] );
			}
		}
	}

	public function block_sensitive_files() {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		$uri = strtolower( $uri );
		
		$blocked_files = array(
			'readme.html',
			'license.txt',
			'wp-config-sample.php'
		);

		foreach ( $blocked_files as $file ) {
			if ( strpos( $uri, $file ) !== false ) {
				status_header( 404 );
				nocache_headers();
				die( '404 Not Found' );
			}
		}
	}
}
