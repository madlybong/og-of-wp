<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_DB {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Suppress database errors
		add_action( 'plugins_loaded', array( $this, 'suppress_db_errors' ) );

		// Check DB prefix
		add_action( 'admin_init', array( $this, 'check_db_prefix' ) );
	}

	public function suppress_db_errors() {
		global $wpdb;
		if ( ! empty( $wpdb ) ) {
			$wpdb->hide_errors();
		}
	}

	public function check_db_prefix() {
		$transient_key = 'og_wp_db_prefix_check';
		if ( get_transient( $transient_key ) ) {
			return;
		}

		global $wpdb;
		if ( $wpdb->prefix === 'wp_' ) {
			// Trigger an alert/log about the default prefix
			do_action( 'og_wp_log_event', 'security_warning', 'Database is using the default "wp_" prefix.' );
		}

		set_transient( $transient_key, true, WEEK_IN_SECONDS );
	}
}
