<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Email {
	private $options;
	private $table_name;
	private $last_smtp_debug = '';
	private $current_send_log_id = null;

	public function __construct( $options ) {
		$this->options = $options;

		global $wpdb;
		$this->table_name = $wpdb->prefix . 'og_wp_email_logs';

		$this->ensure_table_exists();

		// Configure From Email & From Name filters
		add_filter( 'wp_mail_from', array( $this, 'filter_from_email' ), 999 );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_from_name' ), 999 );

		// Pre-flight interception for API mailers and Async Queue
		add_filter( 'pre_wp_mail', array( $this, 'handle_pre_wp_mail' ), 10, 2 );

		// PHPMailer configuration for SMTP
		add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ), 999 );

		// Success / Failure logging
		add_action( 'wp_mail_succeeded', array( $this, 'on_mail_succeeded' ) );
		add_action( 'wp_mail_failed', array( $this, 'on_mail_failed' ) );

		// Asynchronous Queue processor & Background runner
		add_action( 'og_wp_process_email_queue', array( $this, 'process_queued_email' ), 10, 1 );
		add_action( 'og_wp_email_queue_batch_worker', array( $this, 'process_batch_queue' ) );
		add_action( 'wp_ajax_og_wp_process_queue_background', array( $this, 'ajax_process_queue_background' ) );
		add_action( 'wp_ajax_nopriv_og_wp_process_queue_background', array( $this, 'ajax_process_queue_background' ) );
		add_action( 'wp_ajax_og_wp_flush_email_queue', array( $this, 'ajax_flush_email_queue' ) );

		// Open Tracking Pixel endpoint
		add_action( 'init', array( $this, 'handle_open_tracking_pixel' ) );

		// Provider Delivery Webhook route
		add_action( 'rest_api_init', array( $this, 'register_webhook_routes' ) );

		// Retention cleanup cron
		add_action( 'og_wp_daily_cron', array( $this, 'cleanup_old_logs' ) );

		// AJAX endpoints
		add_action( 'wp_ajax_og_wp_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_og_wp_check_domain_dns', array( $this, 'ajax_check_domain_dns' ) );
		add_action( 'wp_ajax_og_wp_resend_email', array( $this, 'ajax_resend_email' ) );
		add_action( 'wp_ajax_og_wp_view_email', array( $this, 'ajax_view_email' ) );
		add_action( 'wp_ajax_og_wp_clear_email_logs', array( $this, 'ajax_clear_email_logs' ) );
		add_action( 'wp_ajax_og_wp_bulk_delete_email_logs', array( $this, 'ajax_bulk_delete_email_logs' ) );
		add_action( 'wp_ajax_og_wp_bulk_resend_email_logs', array( $this, 'ajax_bulk_resend_email_logs' ) );

		// CSV Export handler
		add_action( 'admin_post_og_wp_export_email_logs', array( $this, 'export_logs_csv' ) );
	}

	public function ensure_table_exists() {
		

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			to_email text NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			headers text,
				source_plugin varchar(50) DEFAULT '',
				source_ref varchar(100) DEFAULT '',
			attachments text,
			status varchar(50) NOT NULL DEFAULT 'queued',
			provider varchar(100) NOT NULL DEFAULT 'default',
			error_details text,
			retry_count int(11) NOT NULL DEFAULT 0,
			opened_at datetime DEFAULT NULL,
			open_count int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Ensure columns exist on pre-existing tables
		$col_check = $wpdb->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'opened_at'" );
		if ( empty( $col_check ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD COLUMN opened_at datetime DEFAULT NULL" );
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD COLUMN open_count int(11) NOT NULL DEFAULT 0" );
		}

		
	}

	public function filter_from_email( $original_email ) {
		if ( empty( $this->options['enable_module_email'] ) && empty( $GLOBALS['og_wp_is_test_email'] ) ) {
			return $original_email;
		}

		$force = ! empty( $this->options['email_force_from_email'] );
		$configured_email = ! empty( $this->options['email_from_email'] ) ? sanitize_email( $this->options['email_from_email'] ) : '';

		if ( $force && is_email( $configured_email ) ) {
			return $configured_email;
		}

		// If default wordpress@sitename is provided and we have configured email, use configured email
		if ( is_email( $configured_email ) && ( empty( $original_email ) || strpos( $original_email, 'wordpress@' ) === 0 ) ) {
			return $configured_email;
		}

		return $original_email;
	}

	public function filter_from_name( $original_name ) {
		if ( empty( $this->options['enable_module_email'] ) && empty( $GLOBALS['og_wp_is_test_email'] ) ) {
			return $original_name;
		}

		$force = ! empty( $this->options['email_force_from_name'] );
		$configured_name = ! empty( $this->options['email_from_name'] ) ? sanitize_text_field( $this->options['email_from_name'] ) : '';

		if ( $force && ! empty( $configured_name ) ) {
			return $configured_name;
		}

		if ( ! empty( $configured_name ) && ( empty( $original_name ) || $original_name === 'WordPress' ) ) {
			return $configured_name;
		}

		return $original_name;
	}

	public function resolve_provider_for_email( $atts ) {
		$default_provider = $this->options['email_provider'] ?? 'smtp';
		$rules = isset( $this->options['email_routing_rules'] ) && is_array( $this->options['email_routing_rules'] ) ? $this->options['email_routing_rules'] : array();

		if ( empty( $rules ) ) {
			return $default_provider;
		}

		$to_raw      = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];
		$subject     = (string) ( $atts['subject'] ?? '' );
		$headers_raw = isset( $atts['headers'] ) ? ( is_array( $atts['headers'] ) ? implode( "\n", $atts['headers'] ) : (string) $atts['headers'] ) : '';
		$from_email  = $this->filter_from_email( '' );

		foreach ( $rules as $rule ) {
			$type   = $rule['type'] ?? '';
			$match  = trim( (string) ( $rule['value'] ?? '' ) );
			$target = $rule['provider'] ?? '';

			if ( empty( $match ) || empty( $target ) ) {
				continue;
			}

			switch ( $type ) {
				case 'subject_contains':
					if ( stripos( $subject, $match ) !== false ) {
						return $target;
					}
					break;
				case 'to_domain':
					if ( stripos( $to_raw, $match ) !== false ) {
						return $target;
					}
					break;
				case 'from_email':
					if ( stripos( $from_email, $match ) !== false ) {
						return $target;
					}
					break;
				case 'header_contains':
					if ( stripos( $headers_raw, $match ) !== false ) {
						return $target;
					}
					break;
			}
		}

		return $default_provider;
	}

	public function handle_pre_wp_mail( $null, $atts ) {
		$atts = apply_filters('og_wp_pre_mail_args', $atts);
		if ( empty( $this->options['enable_module_email'] ) && empty( $GLOBALS['og_wp_is_test_email'] ) ) {
			return null;
		}

		// Resolve provider according to smart conditional rules
		$provider = $this->resolve_provider_for_email( $atts );
		$is_async = ! empty( $this->options['email_async_queue'] );

		// If this is already an execution of an async queued item, proceed without re-queueing
		if ( ! empty( $GLOBALS['og_wp_is_processing_queue'] ) ) {
			return $this->dispatch_by_provider( $provider, $atts );
		}

		// Handle Async Queue mode
		if ( $is_async && empty( $GLOBALS['og_wp_is_test_email'] ) ) {
			$log_id = $this->log_email( $atts, 'queued', $provider, '' );
			if ( $log_id ) {
				// Spawn non-blocking background queue runner immediately for 0ms user wait
				$this->spawn_async_queue_runner();
				// Also schedule single event as fallback
				wp_schedule_single_event( time(), 'og_wp_process_email_queue', array( $log_id ) );
				// Return true to tell WP that wp_mail succeeded without waiting for network IO
				return true;
			}
		}

		// If provider is API-based (not standard SMTP), bypass PHPMailer completely
		if ( in_array( $provider, array( 'resend', 'sendgrid', 'mailgun', 'postmark', 'brevo', 'ses' ), true ) ) {
			return $this->dispatch_by_provider( $provider, $atts );
		}

		// If provider is SMTP, pre-log email in pending status and let PHPMailer handle it
		$this->current_send_log_id = $this->log_email( $atts, 'pending', 'smtp', '' );
		return null; // Let standard wp_mail / PHPMailer continue
	}

	private function dispatch_by_provider( $provider, $atts, $is_retry = false ) {
		$to          = $atts['to'];
		$subject     = $atts['subject'];
		$message     = $atts['message'];
		$headers     = $atts['headers'];
		$attachments = $atts['attachments'];

		$from_email  = $this->filter_from_email( '' );
		$from_name   = $this->filter_from_name( '' );

		$recipients = is_array( $to ) ? $to : explode( ',', $to );
		$recipients = array_filter( array_map( 'trim', $recipients ) );

		$log_id = $this->current_send_log_id ? $this->current_send_log_id : $this->log_email( $atts, 'sending', $provider, '' );
		$message = $this->inject_tracking_pixel( $message, $log_id );
		$result = false;
		$error_message = '';

		switch ( $provider ) {
			case 'smtp':
				$result = $this->send_smtp_direct( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			case 'resend':
				$result = $this->send_resend( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			case 'sendgrid':
				$result = $this->send_sendgrid( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			case 'mailgun':
				$result = $this->send_mailgun( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			case 'postmark':
				$result = $this->send_postmark( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			case 'brevo':
				$result = $this->send_brevo( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			case 'ses':
				$result = $this->send_ses( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments );
				break;
			default:
				$result = new WP_Error( 'invalid_provider', 'Unsupported provider specified: ' . $provider );
				break;
		}

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();

			// Fallback mechanism: If primary failed and fallback is enabled and not already retrying
			$fallback_provider = $this->options['email_fallback_provider'] ?? 'none';
			if ( ! $is_retry && ! empty( $fallback_provider ) && $fallback_provider !== 'none' && $fallback_provider !== $provider ) {
				$this->update_log( $log_id, 'retrying', "Primary [{$provider}] failed: {$error_message}. Initiating auto-failover to [{$fallback_provider}]..." );
				return $this->dispatch_by_provider( $fallback_provider, $atts, true );
			}

			$this->update_log( $log_id, 'failed', $error_message );
			do_action( 'wp_mail_failed', $result );
			return false;
		}

		$engine_label = strtoupper( $provider );
		if ( $is_retry ) {
			$engine_label .= ' (Fallback Failover)';
		}
		$this->update_log( $log_id, 'sent', 'Delivered via ' . $engine_label );
		return true;
	}

	private function send_smtp_direct( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		global $phpmailer;
		if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			$phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
		}

		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();

		$this->configure_phpmailer( $phpmailer );

		try {
			foreach ( $recipients as $to ) {
				$phpmailer->addAddress( $to );
			}
			$phpmailer->Subject = $subject;
			$is_html = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );
			if ( $is_html ) {
				$phpmailer->isHTML( true );
				$phpmailer->Body    = $message;
				$phpmailer->AltBody = wp_strip_all_tags( $message );
			} else {
				$phpmailer->isHTML( false );
				$phpmailer->Body = $message;
			}

			$parsed_headers = $this->parse_headers( $headers );
			foreach ( $parsed_headers['reply_to'] as $rt ) {
				$phpmailer->addReplyTo( $rt );
			}
			foreach ( $parsed_headers['cc'] as $c ) {
				$phpmailer->addCC( $c );
			}
			foreach ( $parsed_headers['bcc'] as $b ) {
				$phpmailer->addBCC( $b );
			}
			foreach ( $parsed_headers['custom'] as $hn => $hv ) {
				$phpmailer->addCustomHeader( $hn, $hv );
			}

			$parsed_attachments = $this->parse_attachments( $attachments );
			foreach ( $parsed_attachments as $att ) {
				$phpmailer->addAttachment( $att['path'], $att['name'] );
			}

			$sent = $phpmailer->send();
			return $sent ? true : new WP_Error( 'smtp_error', $phpmailer->ErrorInfo ?: 'PHPMailer failed to send' );
		} catch ( Exception $e ) {
			return new WP_Error( 'smtp_error', $phpmailer->ErrorInfo ?: $e->getMessage() );
		}
	}

	public function configure_phpmailer( $phpmailer ) {
		if ( empty( $this->options['enable_module_email'] ) && empty( $GLOBALS['og_wp_is_test_email'] ) ) {
			return;
		}

		$provider = $this->options['email_provider'] ?? 'smtp';

		if ( $provider !== 'smtp' ) {
			return;
		}

		$host       = $this->options['smtp_host'] ?? '';
		$port       = intval( $this->options['smtp_port'] ?? 587 );
		$encryption = $this->options['smtp_encryption'] ?? 'tls';
		$auth       = ! empty( $this->options['smtp_auth'] );
		$user       = $this->options['smtp_user'] ?? '';
		$pass       = $this->options['smtp_pass'] ?? '';

		if ( empty( $host ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = $port;
		$phpmailer->SMTPAuth   = $auth;

		if ( $auth ) {
			$phpmailer->Username = $user;
			$phpmailer->Password = $pass;
		}

		if ( $encryption === 'ssl' ) {
			$phpmailer->SMTPSecure = 'ssl';
		} elseif ( $encryption === 'tls' ) {
			$phpmailer->SMTPSecure = 'tls';
			$phpmailer->SMTPAutoTLS = true;
		} else {
			$phpmailer->SMTPSecure = '';
			$phpmailer->SMTPAutoTLS = false;
		}

		// Apply From and Return Path
		$from_email = $this->filter_from_email( $phpmailer->From );
		$from_name  = $this->filter_from_name( $phpmailer->FromName );
		$phpmailer->setFrom( $from_email, $from_name, false );
		$phpmailer->Sender = $from_email;

		// Inject open tracking pixel into HTML email body
		if ( ! empty( $this->current_send_log_id ) ) {
			$phpmailer->Body = $this->inject_tracking_pixel( $phpmailer->Body, $this->current_send_log_id );
		}

		// Debug capture for live test suite
		if ( ! empty( $GLOBALS['og_wp_is_test_email'] ) ) {
			$phpmailer->SMTPDebug = 3;
			$phpmailer->Debugoutput = function( $str, $level ) {
				$this->last_smtp_debug .= "[" . date( 'H:i:s' ) . " L{$level}] " . trim( $str ) . "\n";
			};
		}
	}

	public function on_mail_succeeded( $mail_data ) {
		if ( $this->current_send_log_id ) {
			$this->update_log( $this->current_send_log_id, 'sent', 'Delivered via SMTP' );
			$this->current_send_log_id = null;
		}
	}

	public function on_mail_failed( $wp_error ) {
		$error_message = is_wp_error( $wp_error ) ? $wp_error->get_error_message() : 'Unknown error sending mail';
		
		if ( $this->current_send_log_id ) {
			$this->update_log( $this->current_send_log_id, 'failed', $error_message );
			$this->current_send_log_id = null;
		}
	}

	// -------------------------------------------------------------
	// Asynchronous Queue Engine & Background Runners
	// -------------------------------------------------------------

	public function spawn_async_queue_runner() {
		$ajax_url = admin_url( 'admin-ajax.php' );
		$token    = wp_hash( 'og_wp_async_queue_token' );

		wp_remote_post( $ajax_url, array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body'      => array(
				'action' => 'og_wp_process_queue_background',
				'token'  => $token,
			),
		) );
	}

	public function ajax_process_queue_background() {
		$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		if ( ! hash_equals( wp_hash( 'og_wp_async_queue_token' ), $token ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		$this->process_batch_queue( 15 );
		wp_die();
	}

	public function ajax_flush_email_queue() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$processed = $this->process_batch_queue( 50 );
		wp_send_json_success( array(
			'message'   => "Successfully processed {$processed} queued email(s).",
			'processed' => $processed,
		) );
	}

	public function process_batch_queue( $batch_size = 15 ) {
		$lock = get_transient( 'og_wp_queue_processing_lock' );
		if ( $lock ) {
			return 0;
		}
		set_transient( 'og_wp_queue_processing_lock', time(), 45 );

		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			 WHERE status = 'queued' OR (status = 'retrying' AND retry_count < 3)
			 ORDER BY id ASC 
			 LIMIT %d",
			$batch_size
		), ARRAY_A );

		if ( empty( $rows ) ) {
			delete_transient( 'og_wp_queue_processing_lock' );
			return 0;
		}

		$ids = wp_list_pluck( $rows, 'id' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'sending' WHERE id IN ($placeholders)", $ids ) );

		$processed_count = 0;

		foreach ( $rows as $row ) {
			$GLOBALS['og_wp_is_processing_queue'] = true;
			$this->current_send_log_id = $row['id'];

			$to          = explode( ',', $row['to_email'] );
			$subject     = $row['subject'];
			$message     = $row['message'];
			$headers     = ! empty( $row['headers'] ) ? maybe_unserialize( $row['headers'] ) : array();
			$attachments = ! empty( $row['attachments'] ) ? maybe_unserialize( $row['attachments'] ) : array();

			$atts = array(
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
			);

			$provider = $row['provider'];
			if ( empty( $provider ) || $provider === 'default' ) {
				$provider = $this->options['email_provider'] ?? 'smtp';
			}

			if ( in_array( $provider, array( 'resend', 'sendgrid', 'mailgun', 'postmark', 'brevo', 'ses' ), true ) ) {
				$sent = $this->dispatch_by_provider( $provider, $atts );
			} else {
				$sent = wp_mail( $to, $subject, $message, $headers, $attachments );
			}

			if ( $sent ) {
				$processed_count++;
			}

			unset( $GLOBALS['og_wp_is_processing_queue'] );
			$this->current_send_log_id = null;
		}

		delete_transient( 'og_wp_queue_processing_lock' );
		return $processed_count;
	}

	public function process_queued_email( $log_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d AND status IN ('queued', 'retrying')", $log_id ), ARRAY_A );
		if ( ! $row ) {
			return;
		}

		$wpdb->update( $this->table_name, array( 'status' => 'sending' ), array( 'id' => intval( $log_id ) ) );

		$GLOBALS['og_wp_is_processing_queue'] = true;
		$this->current_send_log_id = $log_id;

		$to          = explode( ',', $row['to_email'] );
		$subject     = $row['subject'];
		$message     = $row['message'];
		$headers     = ! empty( $row['headers'] ) ? maybe_unserialize( $row['headers'] ) : array();
		$attachments = ! empty( $row['attachments'] ) ? maybe_unserialize( $row['attachments'] ) : array();

		$atts = array(
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $headers,
			'attachments' => $attachments,
		);

		$provider = $row['provider'];
		if ( empty( $provider ) || $provider === 'default' ) {
			$provider = $this->options['email_provider'] ?? 'smtp';
		}

		if ( in_array( $provider, array( 'resend', 'sendgrid', 'mailgun', 'postmark', 'brevo', 'ses' ), true ) ) {
			$this->dispatch_by_provider( $provider, $atts );
		} else {
			wp_mail( $to, $subject, $message, $headers, $attachments );
		}

		unset( $GLOBALS['og_wp_is_processing_queue'] );
		$this->current_send_log_id = null;
	}

	// -------------------------------------------------------------
	// Header & Attachment Parsing Utilities
	// -------------------------------------------------------------

	private function parse_headers( $headers ) {
		$parsed = array(
			'reply_to'     => array(),
			'cc'           => array(),
			'bcc'          => array(),
			'content_type' => '',
			'custom'       => array(),
		);

		if ( empty( $headers ) ) {
			return $parsed;
		}

		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}

		foreach ( $headers as $header ) {
			$header = trim( $header );
			if ( empty( $header ) || strpos( $header, ':' ) === false ) {
				continue;
			}

			list( $name, $value ) = explode( ':', $header, 2 );
			$name  = trim( strtolower( $name ) );
			$value = trim( $value );

			if ( $name === 'reply-to' ) {
				$parsed['reply_to'] = array_merge( $parsed['reply_to'], array_map( 'trim', explode( ',', $value ) ) );
			} elseif ( $name === 'cc' ) {
				$parsed['cc'] = array_merge( $parsed['cc'], array_map( 'trim', explode( ',', $value ) ) );
			} elseif ( $name === 'bcc' ) {
				$parsed['bcc'] = array_merge( $parsed['bcc'], array_map( 'trim', explode( ',', $value ) ) );
			} elseif ( $name === 'content-type' ) {
				$parsed['content_type'] = $value;
			} else {
				$parsed['custom'][ $name ] = $value;
			}
		}

		$parsed['reply_to'] = array_values( array_filter( $parsed['reply_to'] ) );
		$parsed['cc']       = array_values( array_filter( $parsed['cc'] ) );
		$parsed['bcc']      = array_values( array_filter( $parsed['bcc'] ) );

		return $parsed;
	}

	private function parse_attachments( $attachments ) {
		$parsed = array();

		if ( empty( $attachments ) ) {
			return $parsed;
		}

		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
		}

		foreach ( $attachments as $file_path ) {
			$file_path = trim( $file_path );
			if ( empty( $file_path ) || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
				continue;
			}

			// Limit attachment size to 10MB to avoid PHP OOM
			$size = filesize( $file_path );
			if ( $size > 10 * 1024 * 1024 ) {
				continue;
			}

			$filename = basename( $file_path );
			$mime = 'application/octet-stream';
			if ( function_exists( 'mime_content_type' ) ) {
				$detected_mime = @mime_content_type( $file_path );
				if ( $detected_mime ) {
					$mime = $detected_mime;
				}
			}

			$content = file_get_contents( $file_path );
			if ( $content === false ) {
				continue;
			}

			$parsed[] = array(
				'name'   => $filename,
				'path'   => $file_path,
				'mime'   => $mime,
				'base64' => base64_encode( $content ),
			);
		}

		return $parsed;
	}

	// -------------------------------------------------------------
	// Provider Implementations (Direct API / REST)
	// -------------------------------------------------------------

	private function send_resend( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		$api_key = $this->options['resend_api_key'] ?? '';
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', 'Resend API Key is not configured.' );
		}

		$from_string        = ! empty( $from_name ) ? "{$from_name} <{$from_email}>" : $from_email;
		$is_html            = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );
		$parsed_headers     = $this->parse_headers( $headers );
		$parsed_attachments = $this->parse_attachments( $attachments );

		$payload = array(
			'from'    => $from_string,
			'to'      => $recipients,
			'subject' => $subject,
		);

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$payload['reply_to'] = count( $parsed_headers['reply_to'] ) === 1 ? $parsed_headers['reply_to'][0] : $parsed_headers['reply_to'];
		}
		if ( ! empty( $parsed_headers['cc'] ) ) {
			$payload['cc'] = $parsed_headers['cc'];
		}
		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$payload['bcc'] = $parsed_headers['bcc'];
		}

		if ( $is_html ) {
			$payload['html'] = $message;
		} else {
			$payload['text'] = $message;
		}

		if ( ! empty( $parsed_attachments ) ) {
			$payload['attachments'] = array();
			foreach ( $parsed_attachments as $att ) {
				$payload['attachments'][] = array(
					'filename' => $att['name'],
					'content'  => $att['base64'],
				);
			}
		}

		$response = wp_remote_post( 'https://api.resend.com/emails', array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$err = $body['message'] ?? ( 'HTTP ' . $code . ' from Resend API' );
		return new WP_Error( 'resend_error', $err );
	}

	private function send_sendgrid( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		$api_key = $this->options['sendgrid_api_key'] ?? '';
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', 'SendGrid API Key is not configured.' );
		}

		$parsed_headers     = $this->parse_headers( $headers );
		$parsed_attachments = $this->parse_attachments( $attachments );

		$to_array = array();
		foreach ( $recipients as $email ) {
			$to_array[] = array( 'email' => $email );
		}

		$personalization = array( 'to' => $to_array );
		if ( ! empty( $parsed_headers['cc'] ) ) {
			$cc_arr = array();
			foreach ( $parsed_headers['cc'] as $c ) {
				$cc_arr[] = array( 'email' => $c );
			}
			$personalization['cc'] = $cc_arr;
		}
		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$bcc_arr = array();
			foreach ( $parsed_headers['bcc'] as $b ) {
				$bcc_arr[] = array( 'email' => $b );
			}
			$personalization['bcc'] = $bcc_arr;
		}

		$is_html = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );

		$payload = array(
			'personalizations' => array( $personalization ),
			'from'             => array(
				'email' => $from_email,
				'name'  => $from_name,
			),
			'subject'          => $subject,
			'content'          => array(
				array(
					'type'  => $is_html ? 'text/html' : 'text/plain',
					'value' => $message,
				),
			),
		);

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$payload['reply_to'] = array( 'email' => $parsed_headers['reply_to'][0] );
		}

		if ( ! empty( $parsed_attachments ) ) {
			$payload['attachments'] = array();
			foreach ( $parsed_attachments as $att ) {
				$payload['attachments'][] = array(
					'content'     => $att['base64'],
					'filename'    => $att['name'],
					'type'        => $att['mime'],
					'disposition' => 'attachment',
				);
			}
		}

		$response = wp_remote_post( 'https://api.sendgrid.com/v3/mail/send', array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$err  = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : ( 'HTTP ' . $code . ' from SendGrid API' );
		return new WP_Error( 'sendgrid_error', $err );
	}

	private function send_mailgun( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		$api_key = $this->options['mailgun_api_key'] ?? '';
		$domain  = $this->options['mailgun_domain'] ?? '';
		$region  = $this->options['mailgun_region'] ?? 'us';

		if ( empty( $api_key ) || empty( $domain ) ) {
			return new WP_Error( 'missing_key', 'Mailgun API Key or Domain is not configured.' );
		}

		$endpoint = ( $region === 'eu' ) ? "https://api.eu.mailgun.net/v3/{$domain}/messages" : "https://api.mailgun.net/v3/{$domain}/messages";

		$from_string        = ! empty( $from_name ) ? "{$from_name} <{$from_email}>" : $from_email;
		$is_html            = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );
		$parsed_headers     = $this->parse_headers( $headers );

		$body_fields = array(
			'from'    => $from_string,
			'to'      => implode( ',', $recipients ),
			'subject' => $subject,
		);

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$body_fields['h:Reply-To'] = implode( ',', $parsed_headers['reply_to'] );
		}
		if ( ! empty( $parsed_headers['cc'] ) ) {
			$body_fields['cc'] = implode( ',', $parsed_headers['cc'] );
		}
		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$body_fields['bcc'] = implode( ',', $parsed_headers['bcc'] );
		}

		if ( $is_html ) {
			$body_fields['html'] = $message;
		} else {
			$body_fields['text'] = $message;
		}

		$response = wp_remote_post( $endpoint, array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
			),
			'body'    => $body_fields,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$err  = $body['message'] ?? ( 'HTTP ' . $code . ' from Mailgun API' );
		return new WP_Error( 'mailgun_error', $err );
	}

	private function send_postmark( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		$server_token = $this->options['postmark_token'] ?? '';
		if ( empty( $server_token ) ) {
			return new WP_Error( 'missing_key', 'Postmark Server Token is not configured.' );
		}

		$from_string        = ! empty( $from_name ) ? "{$from_name} <{$from_email}>" : $from_email;
		$is_html            = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );
		$parsed_headers     = $this->parse_headers( $headers );
		$parsed_attachments = $this->parse_attachments( $attachments );

		$payload = array(
			'From'    => $from_string,
			'To'      => implode( ',', $recipients ),
			'Subject' => $subject,
		);

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$payload['ReplyTo'] = implode( ',', $parsed_headers['reply_to'] );
		}
		if ( ! empty( $parsed_headers['cc'] ) ) {
			$payload['Cc'] = implode( ',', $parsed_headers['cc'] );
		}
		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$payload['Bcc'] = implode( ',', $parsed_headers['bcc'] );
		}

		if ( $is_html ) {
			$payload['HtmlBody'] = $message;
		} else {
			$payload['TextBody'] = $message;
		}

		if ( ! empty( $parsed_attachments ) ) {
			$payload['Attachments'] = array();
			foreach ( $parsed_attachments as $att ) {
				$payload['Attachments'][] = array(
					'Name'        => $att['name'],
					'Content'     => $att['base64'],
					'ContentType' => $att['mime'],
				);
			}
		}

		$response = wp_remote_post( 'https://api.postmarkapp.com/email', array(
			'timeout' => 15,
			'headers' => array(
				'X-Postmark-Server-Token' => $server_token,
				'Content-Type'            => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$err = $body['Message'] ?? ( 'HTTP ' . $code . ' from Postmark API' );
		return new WP_Error( 'postmark_error', $err );
	}

	private function send_brevo( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		$api_key = $this->options['brevo_api_key'] ?? '';
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', 'Brevo API Key is not configured.' );
		}

		$to_array = array();
		foreach ( $recipients as $email ) {
			$to_array[] = array( 'email' => $email );
		}

		$is_html            = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );
		$parsed_headers     = $this->parse_headers( $headers );
		$parsed_attachments = $this->parse_attachments( $attachments );

		$payload = array(
			'sender'  => array(
				'email' => $from_email,
				'name'  => $from_name,
			),
			'to'      => $to_array,
			'subject' => $subject,
		);

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$payload['replyTo'] = array( 'email' => $parsed_headers['reply_to'][0] );
		}
		if ( ! empty( $parsed_headers['cc'] ) ) {
			$payload['cc'] = array();
			foreach ( $parsed_headers['cc'] as $c ) {
				$payload['cc'][] = array( 'email' => $c );
			}
		}
		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$payload['bcc'] = array();
			foreach ( $parsed_headers['bcc'] as $b ) {
				$payload['bcc'][] = array( 'email' => $b );
			}
		}

		if ( $is_html ) {
			$payload['htmlContent'] = $message;
		} else {
			$payload['textContent'] = $message;
		}

		if ( ! empty( $parsed_attachments ) ) {
			$payload['attachment'] = array();
			foreach ( $parsed_attachments as $att ) {
				$payload['attachment'][] = array(
					'name'    => $att['name'],
					'content' => $att['base64'],
				);
			}
		}

		$response = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', array(
			'timeout' => 15,
			'headers' => array(
				'api-key'      => $api_key,
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$err = $body['message'] ?? ( 'HTTP ' . $code . ' from Brevo API' );
		return new WP_Error( 'brevo_error', $err );
	}

	private function send_ses( $recipients, $subject, $message, $headers, $from_email, $from_name, $attachments ) {
		$access_key = $this->options['ses_access_key'] ?? '';
		$secret_key = $this->options['ses_secret_key'] ?? '';
		$region     = $this->options['ses_region'] ?? 'us-east-1';

		if ( empty( $access_key ) || empty( $secret_key ) ) {
			return new WP_Error( 'missing_key', 'Amazon SES Access Key or Secret Key is not configured.' );
		}

		$from_string        = ! empty( $from_name ) ? "{$from_name} <{$from_email}>" : $from_email;
		$parsed_headers     = $this->parse_headers( $headers );
		$parsed_attachments = $this->parse_attachments( $attachments );
		$is_html            = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );

		$destination = array(
			'ToAddresses' => $recipients,
		);
		if ( ! empty( $parsed_headers['cc'] ) ) {
			$destination['CcAddresses'] = $parsed_headers['cc'];
		}
		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$destination['BccAddresses'] = $parsed_headers['bcc'];
		}

		if ( ! empty( $parsed_attachments ) ) {
			$boundary = '=_' . md5( microtime( true ) );
			$raw_mime = "From: {$from_string}\r\n";
			$raw_mime .= "To: " . implode( ', ', $recipients ) . "\r\n";
			if ( ! empty( $parsed_headers['cc'] ) ) {
				$raw_mime .= "Cc: " . implode( ', ', $parsed_headers['cc'] ) . "\r\n";
			}
			if ( ! empty( $parsed_headers['reply_to'] ) ) {
				$raw_mime .= "Reply-To: " . implode( ', ', $parsed_headers['reply_to'] ) . "\r\n";
			}
			$raw_mime .= "Subject: =?UTF-8?B?" . base64_encode( $subject ) . "?=\r\n";
			$raw_mime .= "MIME-Version: 1.0\r\n";
			$raw_mime .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

			// Body part
			$raw_mime .= "--{$boundary}\r\n";
			$content_type = $is_html ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';
			$raw_mime .= "Content-Type: {$content_type}\r\n";
			$raw_mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
			$raw_mime .= chunk_split( base64_encode( $message ) ) . "\r\n";

			// Attachment parts
			foreach ( $parsed_attachments as $att ) {
				$raw_mime .= "--{$boundary}\r\n";
				$raw_mime .= "Content-Type: {$att['mime']}; name=\"{$att['name']}\"\r\n";
				$raw_mime .= "Content-Disposition: attachment; filename=\"{$att['name']}\"\r\n";
				$raw_mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
				$raw_mime .= chunk_split( $att['base64'] ) . "\r\n";
			}
			$raw_mime .= "--{$boundary}--\r\n";

			$payload = array(
				'FromEmailAddress' => $from_string,
				'Destination'      => $destination,
				'Content'          => array(
					'Raw' => array(
						'Data' => base64_encode( $raw_mime ),
					),
				),
			);
		} else {
			$body_content = array();
			if ( $is_html ) {
				$body_content['Html'] = array( 'Data' => $message, 'Charset' => 'UTF-8' );
				$body_content['Text'] = array( 'Data' => wp_strip_all_tags( $message ), 'Charset' => 'UTF-8' );
			} else {
				$body_content['Text'] = array( 'Data' => $message, 'Charset' => 'UTF-8' );
			}

			$payload = array(
				'FromEmailAddress' => $from_string,
				'Destination'      => $destination,
				'Content'          => array(
					'Simple' => array(
						'Subject' => array( 'Data' => $subject, 'Charset' => 'UTF-8' ),
						'Body'    => $body_content,
					),
				),
			);
		}

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$payload['ReplyToAddresses'] = $parsed_headers['reply_to'];
		}

		$payload_json = wp_json_encode( $payload );
		$host         = "email.{$region}.amazonaws.com";
		$path         = "/v2/email/outbound-emails";

		$sig_headers = $this->get_aws_sigv4_headers(
			'POST',
			$host,
			$path,
			'',
			$payload_json,
			$access_key,
			$secret_key,
			$region,
			'email'
		);

		$endpoint = "https://{$host}{$path}";
		$response = wp_remote_post( $endpoint, array(
			'timeout' => 15,
			'headers' => $sig_headers,
			'body'    => $payload_json,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$err = $body['message'] ?? ( $body['Message'] ?? ( 'HTTP ' . $code . ' from Amazon SES API' ) );
		return new WP_Error( 'ses_error', $err );
	}

	private function get_aws_sigv4_headers( $method, $host, $path, $query, $payload_json, $access_key, $secret_key, $region, $service = 'email' ) {
		$amz_date   = gmdate( 'Ymd\THis\Z' );
		$date_stamp = gmdate( 'Ymd' );

		$canonical_uri         = $path;
		$canonical_querystring = $query;
		$payload_hash          = hash( 'sha256', $payload_json );

		$canonical_headers = "content-type:application/json\n" .
		                     "host:{$host}\n" .
		                     "x-amz-date:{$amz_date}\n";

		$signed_headers = "content-type;host;x-amz-date";

		$canonical_request = $method . "\n" .
		                     $canonical_uri . "\n" .
		                     $canonical_querystring . "\n" .
		                     $canonical_headers . "\n" .
		                     $signed_headers . "\n" .
		                     $payload_hash;

		$algorithm        = 'AWS4-HMAC-SHA256';
		$credential_scope = "{$date_stamp}/{$region}/{$service}/aws4_request";
		$string_to_sign   = $algorithm . "\n" .
		                    $amz_date . "\n" .
		                    $credential_scope . "\n" .
		                    hash( 'sha256', $canonical_request );

		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $secret_key, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

		$signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

		$authorization = "{$algorithm} Credential={$access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

		return array(
			'Content-Type'  => 'application/json',
			'Host'          => $host,
			'X-Amz-Date'    => $amz_date,
			'Authorization' => $authorization,
		);
	}

	// -------------------------------------------------------------
	// Logging & Storage
	// -------------------------------------------------------------

	private function log_email( $atts, $status, $provider, $error_details ) {
		global $wpdb;

		$to = $atts['to'];
		if ( is_array( $to ) ) {
			$to = implode( ', ', $to );
		}

		$subject     = $atts['subject'] ?? '';
		$message     = $atts['message'] ?? '';
		$headers     = isset( $atts['headers'] ) ? ( is_array( $atts['headers'] ) ? maybe_serialize( $atts['headers'] ) : (string) $atts['headers'] ) : '';
		$attachments = isset( $atts['attachments'] ) ? ( is_array( $atts['attachments'] ) ? maybe_serialize( $atts['attachments'] ) : (string) $atts['attachments'] ) : '';

		$inserted = $wpdb->insert(
			$this->table_name,
			array(
				'created_at'    => current_time( 'mysql' ),
				'to_email'      => sanitize_text_field( $to ),
				'subject'       => sanitize_text_field( $subject ),
				'message'       => $message,
				'headers'       => $headers,
				'attachments'   => $attachments,
				'status'        => sanitize_key( $status ),
				'provider'      => sanitize_text_field( $provider ),
				'error_details' => $error_details,
				'retry_count'   => 0,
			)
		);

		return $inserted ? $wpdb->insert_id : null;
	}

	private function update_log( $log_id, $status, $error_details = '' ) {
		if ( ! $log_id ) {
			return;
		}
		global $wpdb;
		$wpdb->update(
			$this->table_name,
			array(
				'status'        => sanitize_key( $status ),
				'error_details' => $error_details,
			),
			array( 'id' => intval( $log_id ) )
		);
	}

	public function cleanup_old_logs() {
		global $wpdb;
		$days = isset( $this->options['email_log_retention'] ) ? intval( $this->options['email_log_retention'] ) : 30;
		$days = max( 1, $days );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		) );
	}

	// -------------------------------------------------------------
	// AJAX Endpoints
	// -------------------------------------------------------------

	public function ajax_send_test_email() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$to_email = isset( $_POST['to_email'] ) ? sanitize_email( $_POST['to_email'] ) : '';
		if ( ! is_email( $to_email ) ) {
			wp_send_json_error( 'Please provide a valid destination email address.' );
		}

		$provider = $this->options['email_provider'] ?? 'smtp';

		$GLOBALS['og_wp_is_test_email'] = true;
		$this->last_smtp_debug = '';

		$subject = 'OG of WP Test Email - ' . date( 'Y-m-d H:i:s' );
		$body = "<h2>Congratulations!</h2>\n" .
				"<p>Your email sending configuration with <strong>OG of WP</strong> is functioning properly.</p>\n" .
				"<ul>\n" .
				"<li><strong>Active Mailer:</strong> " . strtoupper( esc_html( $provider ) ) . "</li>\n" .
				"<li><strong>From Address:</strong> " . esc_html( $this->filter_from_email( '' ) ) . "</li>\n" .
				"<li><strong>From Name:</strong> " . esc_html( $this->filter_from_name( '' ) ) . "</li>\n" .
				"<li><strong>Server Time:</strong> " . current_time( 'mysql' ) . "</li>\n" .
				"</ul>\n" .
				"<p style='color:#666; font-size:12px;'>Sent via OG of WP Sovereign Email Deliverability Engine.</p>";

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $to_email, $subject, $body, $headers );

		unset( $GLOBALS['og_wp_is_test_email'] );

		$debug_transcript = trim( $this->last_smtp_debug );

		if ( $sent ) {
			wp_send_json_success( array(
				'message'    => "Test email successfully sent to {$to_email} via " . strtoupper( $provider ) . "!",
				'transcript' => $debug_transcript,
			) );
		} else {
			wp_send_json_error( array(
				'message'    => "Failed to deliver email to {$to_email}. Review the diagnostic transcript below.",
				'transcript' => $debug_transcript,
			) );
		}
	}

	public function ajax_check_domain_dns() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$domain = isset( $_POST['domain'] ) ? sanitize_text_field( $_POST['domain'] ) : '';
		if ( empty( $domain ) ) {
			$from = $this->filter_from_email( '' );
			if ( strpos( $from, '@' ) !== false ) {
				$parts = explode( '@', $from );
				$domain = end( $parts );
			}
		}
		$domain = trim( preg_replace( '/^https?:\/\//i', '', $domain ), '/' );

		if ( empty( $domain ) ) {
			wp_send_json_error( 'Could not resolve domain name to check.' );
		}

		$results = array(
			'domain' => $domain,
			'mx'     => array( 'status' => 'missing', 'records' => array(), 'details' => 'No MX records found' ),
			'spf'    => array( 'status' => 'missing', 'record' => '', 'details' => 'No SPF record found (v=spf1)' ),
			'dmarc'  => array( 'status' => 'missing', 'record' => '', 'details' => 'No DMARC record found at _dmarc.' . $domain ),
		);

		// Check MX
		if ( function_exists( 'dns_get_record' ) ) {
			$mx_records = @dns_get_record( $domain, DNS_MX );
			if ( ! empty( $mx_records ) ) {
				$hosts = array();
				foreach ( $mx_records as $mx ) {
					$hosts[] = ( $mx['target'] ?? '' ) . ' (Pri: ' . ( $mx['pri'] ?? 0 ) . ')';
				}
				$results['mx']['status']  = 'pass';
				$results['mx']['records'] = $hosts;
				$results['mx']['details'] = 'Found ' . count( $hosts ) . ' MX record(s).';
			}

			// Check SPF (TXT records on root domain)
			$txt_records = @dns_get_record( $domain, DNS_TXT );
			$spf_found = array();
			if ( ! empty( $txt_records ) ) {
				foreach ( $txt_records as $txt ) {
					$entry = $txt['txt'] ?? ( $txt['entries'][0] ?? '' );
					if ( strpos( $entry, 'v=spf1' ) === 0 ) {
						$spf_found[] = $entry;
					}
				}
			}

			if ( count( $spf_found ) === 1 ) {
				$results['spf']['status']  = 'pass';
				$results['spf']['record']  = $spf_found[0];
				$results['spf']['details'] = 'Valid single SPF record detected.';
			} elseif ( count( $spf_found ) > 1 ) {
				$results['spf']['status']  = 'warning';
				$results['spf']['record']  = implode( "\n", $spf_found );
				$results['spf']['details'] = 'Multiple SPF records detected! RFC prohibits having more than one SPF TXT record.';
			}

			// Check DMARC (TXT record on _dmarc.domain)
			$dmarc_records = @dns_get_record( '_dmarc.' . $domain, DNS_TXT );
			if ( ! empty( $dmarc_records ) ) {
				foreach ( $dmarc_records as $txt ) {
					$entry = $txt['txt'] ?? ( $txt['entries'][0] ?? '' );
					if ( strpos( $entry, 'v=DMARC1' ) === 0 ) {
						$results['dmarc']['status']  = 'pass';
						$results['dmarc']['record']  = $entry;
						$results['dmarc']['details'] = 'Valid DMARC policy found.';
						break;
					}
				}
			}
		} else {
			wp_send_json_error( 'PHP dns_get_record() is disabled on this server host.' );
		}

		wp_send_json_success( $results );
	}

	public function ajax_resend_email() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;
		if ( ! $log_id ) {
			wp_send_json_error( 'Invalid Log ID' );
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $log_id ), ARRAY_A );
		if ( ! $row ) {
			wp_send_json_error( 'Email log not found' );
		}

		$to          = explode( ',', $row['to_email'] );
		$subject     = '[Resend] ' . $row['subject'];
		$message     = $row['message'];
		$headers     = ! empty( $row['headers'] ) ? maybe_unserialize( $row['headers'] ) : array( 'Content-Type: text/html; charset=UTF-8' );
		$attachments = ! empty( $row['attachments'] ) ? maybe_unserialize( $row['attachments'] ) : array();

		// Increment retry count on original
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET retry_count = retry_count + 1 WHERE id = %d", $log_id ) );

		$sent = wp_mail( $to, $subject, $message, $headers, $attachments );

		if ( $sent ) {
			wp_send_json_success( 'Email re-dispatched successfully.' );
		} else {
			wp_send_json_error( 'Failed to resend email. Check provider settings or logs.' );
		}
	}

	public function ajax_view_email() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $log_id ), ARRAY_A );

		if ( ! $row ) {
			wp_send_json_error( 'Email not found.' );
		}

		$raw_headers = ! empty( $row['headers'] ) ? maybe_unserialize( $row['headers'] ) : array();
		if ( is_array( $raw_headers ) ) {
			$headers_formatted = implode( "\n", $raw_headers );
		} else {
			$headers_formatted = (string) $raw_headers;
		}

		$attachments = ! empty( $row['attachments'] ) ? maybe_unserialize( $row['attachments'] ) : array();
		$attachments_str = is_array( $attachments ) ? implode( ", ", $attachments ) : (string) $attachments;

		wp_send_json_success( array(
			'id'            => intval( $row['id'] ),
			'to'            => esc_html( $row['to_email'] ),
			'subject'       => esc_html( $row['subject'] ),
			'created_at'    => esc_html( $row['created_at'] ),
			'opened_at'     => ! empty( $row['opened_at'] ) ? esc_html( $row['opened_at'] ) : 'Not yet opened',
			'open_count'    => intval( $row['open_count'] ?? 0 ),
			'status'        => esc_html( $row['status'] ),
			'provider'      => esc_html( strtoupper( $row['provider'] ) ),
			'retry_count'   => intval( $row['retry_count'] ),
			'error_details' => esc_html( $row['error_details'] ),
			'headers'       => esc_html( $headers_formatted ),
			'attachments'   => esc_html( $attachments_str ),
			'message'       => $row['message'],
			'plain_text'    => esc_html( wp_strip_all_tags( $row['message'] ) ),
		) );
	}

	public function ajax_clear_email_logs() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
		wp_send_json_success( 'All email logs have been cleared.' );
	}

	public function ajax_bulk_delete_email_logs() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : array();
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			wp_send_json_error( 'No emails selected for deletion.' );
		}

		global $wpdb;
		$id_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE id IN ($id_placeholders)", $ids ) );

		wp_send_json_success( count( $ids ) . ' email log(s) successfully deleted.' );
	}

	public function ajax_bulk_resend_email_logs() {
		check_ajax_referer('og_wp_admin_ajax', 'og_wp_nonce');
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : array();
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			wp_send_json_error( 'No emails selected to resend.' );
		}

		global $wpdb;
		$success_count = 0;

		foreach ( $ids as $id ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), ARRAY_A );
			if ( ! $row ) {
				continue;
			}

			$to          = explode( ',', $row['to_email'] );
			$subject     = '[Resend] ' . $row['subject'];
			$message     = $row['message'];
			$headers     = ! empty( $row['headers'] ) ? maybe_unserialize( $row['headers'] ) : array( 'Content-Type: text/html; charset=UTF-8' );
			$attachments = ! empty( $row['attachments'] ) ? maybe_unserialize( $row['attachments'] ) : array();

			$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET retry_count = retry_count + 1 WHERE id = %d", $id ) );
			if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
				$success_count++;
			}
		}

		wp_send_json_success( "Successfully re-dispatched {$success_count} of " . count( $ids ) . " email(s)." );
	}

	public function export_logs_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		global $wpdb;
		$logs = $wpdb->get_results( "SELECT id, created_at, to_email, subject, status, provider, retry_count, open_count, opened_at, error_details FROM {$this->table_name} ORDER BY created_at DESC", ARRAY_A );

		if ( empty( $logs ) ) {
			wp_die( 'No email logs to export.' );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=og-wp-email-logs-' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Date/Time', 'Recipient', 'Subject', 'Status', 'Provider', 'Retries', 'Open Count', 'First Opened', 'Error / Details' ) );

		foreach ( $logs as $log ) {
			fputcsv( $output, $log );
		}
		fclose( $output );
		exit;
	}

	// -------------------------------------------------------------
	// Open Tracking & Delivery Webhooks
	// -------------------------------------------------------------

	private function inject_tracking_pixel( $message, $log_id ) {
		if ( empty( $this->options['email_track_opens'] ) || ! $log_id ) {
			return $message;
		}

		$is_html = ( strpos( $message, '<html' ) !== false || strpos( $message, '<body' ) !== false || strpos( $message, '</' ) !== false );
		if ( ! $is_html ) {
			return $message;
		}

		$token = base64_encode( wp_json_encode( array(
			'id'  => $log_id,
			'sig' => wp_hash( $log_id . 'og_wp_open_token' ),
		) ) );

		$pixel_url = add_query_arg( 'og_wp_track_email', urlencode( $token ), home_url( '/' ) );
		$pixel_tag = '<img src="' . esc_url( $pixel_url ) . '" width="1" height="1" alt="" style="display:none!important;width:1px!important;height:1px!important;border:0!important;outline:none!important;" />';

		if ( stripos( $message, '</body>' ) !== false ) {
			return str_ireplace( '</body>', $pixel_tag . '</body>', $message );
		}

		return $message . $pixel_tag;
	}

	public function handle_open_tracking_pixel() {
		if ( ! isset( $_GET['og_wp_track_email'] ) ) {
			return;
		}

		$raw_token = sanitize_text_field( wp_unslash( $_GET['og_wp_track_email'] ) );
		$data = json_decode( base64_decode( $raw_token ), true );

		if ( ! empty( $data['id'] ) && ! empty( $data['sig'] ) ) {
			$log_id = intval( $data['id'] );
			$expected_sig = wp_hash( $log_id . 'og_wp_open_token' );

			if ( hash_equals( $expected_sig, $data['sig'] ) ) {
				global $wpdb;
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$this->table_name} 
					 SET open_count = open_count + 1, 
					     opened_at = IFNULL(opened_at, %s) 
					 WHERE id = %d",
					current_time( 'mysql' ),
					$log_id
				) );
			}
		}

		// Disable all active output buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Output transparent 1x1 GIF
		header( 'Content-Type: image/gif' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		// Standard 43-byte transparent GIF
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}

	public function register_webhook_routes() {
		register_rest_route( 'og-wp/v1', '/email-webhook', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_incoming_webhook' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function handle_incoming_webhook( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		if ( empty( $params ) ) {
			return new WP_REST_Response( array( 'error' => 'No payload received' ), 400 );
		}

		global $wpdb;

		// 1. Resend Webhook Format
		if ( isset( $params['type'] ) && strpos( $params['type'], 'email.' ) === 0 ) {
			$event = $params['type'];
			$to_email = $params['data']['to'][0] ?? '';
			if ( $to_email ) {
				if ( $event === 'email.delivered' ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'delivered', error_details = 'Webhook: Successfully delivered' WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", $to_email ) );
				} elseif ( $event === 'email.bounced' || $event === 'email.complained' ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'failed', error_details = %s WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", "Webhook: " . ucfirst( str_replace( 'email.', '', $event ) ), $to_email ) );
				} elseif ( $event === 'email.opened' ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET open_count = open_count + 1, opened_at = IFNULL(opened_at, %s) WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", current_time( 'mysql' ), $to_email ) );
				}
			}
		}

		// 2. SendGrid Webhook Format (Array of events)
		if ( is_array( $params ) && isset( $params[0]['event'] ) ) {
			foreach ( $params as $ev ) {
				$event = $ev['event'] ?? '';
				$to_email = $ev['email'] ?? '';
				if ( $to_email ) {
					if ( $event === 'delivered' ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'delivered' WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", $to_email ) );
					} elseif ( $event === 'bounce' || $event === 'dropped' || $event === 'spamreport' ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'failed', error_details = %s WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", "Webhook: {$event}", $to_email ) );
					} elseif ( $event === 'open' ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET open_count = open_count + 1, opened_at = IFNULL(opened_at, %s) WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", current_time( 'mysql' ), $to_email ) );
					}
				}
			}
		}

		// 3. Mailgun Webhook Format
		if ( isset( $params['event-data']['event'] ) ) {
			$event = $params['event-data']['event'];
			$to_email = $params['event-data']['recipient'] ?? '';
			if ( $to_email ) {
				if ( $event === 'delivered' ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'delivered' WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", $to_email ) );
				} elseif ( $event === 'failed' || $event === 'complained' ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET status = 'failed', error_details = 'Webhook: Delivery Failed/Bounced' WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", $to_email ) );
				} elseif ( $event === 'opened' ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_name} SET open_count = open_count + 1, opened_at = IFNULL(opened_at, %s) WHERE to_email = %s ORDER BY created_at DESC LIMIT 1", current_time( 'mysql' ), $to_email ) );
				}
			}
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}
}









