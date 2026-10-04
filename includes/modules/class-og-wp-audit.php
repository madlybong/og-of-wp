<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Audit {
	private $options;
	private $table_name;

	public function __construct( $options ) {
		$this->options = $options;
		
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'og_wp_audit_log';

		// Hook into core actions
		add_action( 'wp_login', array( $this, 'log_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'log_logout' ) );
		add_action( 'user_register', array( $this, 'log_user_register' ) );
		add_action( 'delete_user', array( $this, 'log_user_delete' ) );
		add_action( 'activated_plugin', array( $this, 'log_plugin_activation' ) );
		add_action( 'deactivated_plugin', array( $this, 'log_plugin_deactivation' ) );

		// Custom log event hook for other modules to use
		add_action( 'og_wp_log_event', array( $this, 'log_custom_event' ), 10, 2 );

		// CSV Export handler
		add_action( 'admin_post_og_wp_export_logs', array( $this, 'export_logs_csv' ) );

		// Log retention cron
		add_action( 'og_wp_daily_cron', array( $this, 'cleanup_old_logs' ) );
	}

	public function cleanup_old_logs() {
		global $wpdb;
		$retention_days = isset( $this->options['audit_retention_days'] ) ? intval( $this->options['audit_retention_days'] ) : 30;
		$retention_days = max( 1, $retention_days ); // Minimum 1 day

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $this->table_name WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$retention_days
		) );
	}

	public function export_logs_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		
		global $wpdb;
		$logs = $wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY time DESC", ARRAY_A );
		
		if ( empty( $logs ) ) {
			wp_die( 'No logs to export.' );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=og-wp-audit-logs-' . date('Y-m-d') . '.csv' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Time', 'User ID', 'IP Address', 'Action', 'Details' ) );

		foreach ( $logs as $log ) {
			fputcsv( $output, $log );
		}
		fclose( $output );
		exit;
	}

	public function create_table() {
		

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			user_id bigint(20) DEFAULT 0 NOT NULL,
			ip_address varchar(45) NOT NULL,
			action varchar(255) NOT NULL,
			details text,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		
	}

	private function get_ip() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
		}
		return trim( $ip );
	}

	private function insert_log( $action, $details = '', $user_id = 0 ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$wpdb->insert(
			$this->table_name,
			array(
				'time'       => current_time( 'mysql' ),
				'user_id'    => $user_id,
				'ip_address' => $this->get_ip(),
				'action'     => $action,
				'details'    => $details,
			)
		);
	}

	public function log_login( $user_login, $user ) {
		$this->insert_log( 'login', "User logged in: {$user_login}", $user->ID );
	}

	public function log_logout() {
		$this->insert_log( 'logout', "User logged out" );
	}

	public function log_user_register( $user_id ) {
		$this->insert_log( 'user_registered', "New user registered", $user_id );
	}

	public function log_user_delete( $user_id ) {
		$this->insert_log( 'user_deleted', "User ID {$user_id} deleted" );
	}

	public function log_plugin_activation( $plugin ) {
		$this->insert_log( 'plugin_activated', "Plugin activated: {$plugin}" );
	}

	public function log_plugin_deactivation( $plugin ) {
		$this->insert_log( 'plugin_deactivated', "Plugin deactivated: {$plugin}" );
	}

	public function log_custom_event( $action, $details ) {
		$this->insert_log( $action, $details );
	}
}


