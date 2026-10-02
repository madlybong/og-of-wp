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
		// Only skip WAF if the user is authenticated as an admin (which is hard to check at plugins_loaded reliably without breaking login)
		// Since we are at plugins_loaded (priority 1), wp_get_current_user is NOT available yet.
		// So we cannot easily skip for admins here unless we check cookies manually, which is brittle.
		// For a secure WAF, we DO NOT skip checks just because it's an admin dashboard request.
		// However, we must ensure we don't break expected admin functionality like saving HTML.
		// We will rely on our check methods to avoid blocking legitimate admin traffic (e.g. SQLi check should exclude known safe admin POST fields if needed, or we just rely on robust patterns).

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
		if ( ! empty( $this->options['admin_ip_allowlist'] ) ) {
			$allowed_ips = array_map( 'trim', explode( ',', $this->options['admin_ip_allowlist'] ) );
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
		die( 'Forbidden: Request blocked by Astrake WP Secure WAF. Reason: ' . esc_html( $reason ) );
	}

	private function check_directory_traversal() {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if ( strpos( $uri, '../' ) !== false || strpos( $uri, '..%2F' ) !== false || strpos( $uri, '..%2f' ) !== false ) {
			$this->block_request( 'Directory Traversal Attempt' );
		}
	}

	private function check_sql_injection() {
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
