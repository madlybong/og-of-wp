<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_WAF {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Run WAF checks very early
		add_action( 'plugins_loaded', array( $this, 'run_waf_checks' ), 1 );
	}

	public function run_waf_checks() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$blocklist = array_map('trim', explode(',', $this->options['waf_block_ip_list'] ?? ''));
		if (in_array($ip, $blocklist) && !empty($ip)) {
			$this->block_request('IP in Blocklist');
		}

		$this->check_directory_traversal();
		$this->check_sql_injection();
		$this->check_bad_bots();
		$this->check_malformed_requests();
		$this->check_admin_ip_allowlist();
	}

	private function check_admin_ip_allowlist() {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		
		// If option is set for admin allowlist
		if ( ! empty( $this->options['waf_admin_ip_allowlist'] ) ) {
			$allowed_ips = array_map( 'trim', explode( ',', $this->options['waf_admin_ip_allowlist'] ) );
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
			if ( ! in_array( $ip, $allowed_ips ) ) {
				$this->block_request( 'Admin IP Not Allowlisted' );
			}
		}
	}

	private function block_request( $reason ) {
		// Log the block here if audit module is active
		do_action( 'og_wp_log_event', 'waf_block', $reason );
		
		status_header( 403 );
		$msg = $this->options['waf_block_message'] ?? 'Forbidden';
		die( esc_html( $msg ) . ' (' . esc_html( $reason ) . ')' );
	}

	private function check_directory_traversal() {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if ( strpos( $uri, '../' ) !== false || strpos( $uri, '..%2F' ) !== false || strpos( $uri, '..%2f' ) !== false ) {
			$this->block_request( 'Directory Traversal Attempt' );
		}
	}

	private function check_sql_injection() {
		if ( empty( $this->options['waf_enable_sqli'] ) ) return;
		$payloads = [
			'UNION SELECT', 'UNION ALL SELECT', 'CONCAT(', 'CHR(',
			'BASE64_DECODE(', 'INFORMATION_SCHEMA', 'DROP TABLE',
			'-- ', 'WAITFOR DELAY', '\'1\'=\'1\'', 'OR 1=1'
		];

		$check_vars = array_merge( $_GET, $_POST, $_COOKIE );
		
		array_walk_recursive( $check_vars, function( $val ) use ( $payloads ) {
			$val_upper = strtoupper( $val );
			foreach ( $payloads as $payload ) {
				if ( strpos( $val_upper, $payload ) !== false ) {
					$this->block_request( 'SQL Injection Attempt' );
				}
			}
		});
	}

	private function check_bad_bots() {
		if ( empty( $this->options['waf_enable_bots'] ) ) return;
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$bad_bots = [
			'sqlmap', 'nmap', 'nikto', 'wpscan', 'dirbuster', 'zgrab',
			'netsparker', 'acunetix', 'python-requests', 'java/', 'wget'
		];

		$ua_lower = strtolower( $user_agent );
		foreach ( $bad_bots as $bot ) {
			if ( strpos( $ua_lower, $bot ) !== false ) {
				$this->block_request( 'Malicious Bot Detected' );
			}
		}
	}

	private function check_malformed_requests() {
		$method = $_SERVER['REQUEST_METHOD'] ?? '';
		$disallowed_methods = [ 'TRACE', 'TRACK', 'DEBUG' ];
		
		if ( in_array( strtoupper( $method ), $disallowed_methods ) ) {
			$this->block_request( 'Disallowed HTTP Method' );
		}
	}
}
