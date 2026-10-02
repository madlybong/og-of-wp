<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_SSL {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		add_action( 'template_redirect', array( $this, 'force_https' ), 1 );
		add_action( 'send_headers', array( $this, 'add_hsts_header' ) );

		// Mixed content fixer via output buffering
		add_action( 'template_redirect', array( $this, 'start_mixed_content_fixer' ), 999 );
		
		// Run SSL monitor daily if alerts are enabled
		if ( ! empty( $this->options['ssl_cert_alerts'] ) ) {
			add_action( 'og_wp_daily_cron', array( $this, 'check_ssl_expiry' ) );
		}
	}

	public function check_ssl_expiry() {
		$url = get_site_url();
		$parsed = parse_url($url);
		
		if ( empty($parsed['host']) ) {
			return;
		}

		$host = $parsed['host'];
		$get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
		$read = @stream_socket_client("ssl://".$host.":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
		$cert = @stream_context_get_params($read);
		
		if ( $cert && isset( $cert["options"]["ssl"]["peer_certificate"] ) ) {
			$certinfo = openssl_x509_parse( $cert["options"]["ssl"]["peer_certificate"] );
			$valid_to = $certinfo['validTo_time_t'];
			
			$days_left = floor( ( $valid_to - time() ) / DAY_IN_SECONDS );
			$alert_days = isset( $this->options['ssl_alert_days'] ) ? intval( $this->options['ssl_alert_days'] ) : 14;

			if ( $days_left <= $alert_days ) {
				$admin_email = get_option( 'admin_email' );
				$subject = 'Action Required: SSL Certificate Expiring Soon on ' . $host;
				$message = sprintf( "The SSL certificate for %s will expire in %d days.\n\nPlease renew it to maintain security and avoid browser warnings.", $host, $days_left );
				wp_mail( $admin_email, $subject, $message );
				
				do_action( 'og_wp_log_event', 'ssl_alert_sent', "SSL certificate expires in $days_left days." );
			}
		}
	}

	public function force_https() {
		if ( ! is_ssl() ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
				wp_redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ), 301 );
				exit();
			} else {
				wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
				exit();
			}
		}
	}

	public function add_hsts_header() {
		if ( is_ssl() && ! headers_sent() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
		}
	}

	public function start_mixed_content_fixer() {
		if ( ! is_admin() ) {
			ob_start( array( $this, 'replace_mixed_content' ) );
		}
	}

	public function replace_mixed_content( $buffer ) {
		$site_url = home_url();
		$site_url_http = str_replace( 'https://', 'http://', $site_url );
		
		if ( $site_url_http !== $site_url ) {
			$buffer = str_replace( $site_url_http, $site_url, $buffer );
		}

		// Replace other common http:// resources like wp-content/uploads
		$buffer = preg_replace( '/http:\/\/(www\.)?([a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+)\/wp-content\//i', 'https://$1$2/wp-content/', $buffer );
		$buffer = preg_replace( '/http:\/\/(www\.)?([a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+)\/wp-includes\//i', 'https://$1$2/wp-includes/', $buffer );

		return $buffer;
	}
}
