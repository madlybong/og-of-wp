<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Scanner {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		if ( ! empty( $this->options['scanner_email_alerts'] ) ) {
			add_action( 'og_wp_daily_cron', array( $this, 'run_scan' ) );
			add_action( 'og_wp_weekly_cron', array( $this, 'run_scan' ) );
		}

		// AJAX handler for manual scan
		add_action( 'wp_ajax_og_wp_run_scan', array( $this, 'ajax_run_scan' ) );
	}

	public function ajax_run_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		
		$this->run_scan();
		wp_send_json_success( 'Scan complete. Check the Audit Log for details.' );
	}

	public function run_scan() {
		$core_altered = $this->scan_core_files();
		$malware_found = $this->scan_for_malware();

		if ( $core_altered || $malware_found ) {
			// Trigger alert (handled by notifications module later, or log it)
			do_action( 'og_wp_log_event', 'scan_alert', 'Suspicious files detected during scan.' );
		} else {
			do_action( 'og_wp_log_event', 'scan_clean', 'Malware scan completed. No threats found.' );
		}
	}

	private function scan_core_files() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		
		$wp_version = get_bloginfo( 'version' );
		$locale = get_locale();
		
		$checksums = get_core_checksums( $wp_version, $locale );
		if ( ! $checksums ) {
			return false;
		}

		$altered_files = [];
		foreach ( $checksums as $file => $checksum ) {
			$path = ABSPATH . $file;
			if ( file_exists( $path ) ) {
				$actual_checksum = md5_file( $path );
				if ( $actual_checksum !== $checksum ) {
					$altered_files[] = $file;
				}
			}
		}

		if ( ! empty( $altered_files ) ) {
			update_option( 'og_wp_core_scan_results', $altered_files, false );
			return true;
		}

		delete_option( 'og_wp_core_scan_results' );
		return false;
	}

	private function scan_for_malware() {
		$suspicious_patterns = [
			'eval(base64_decode(',
			'gzinflate(base64_decode(',
			'eval($_GET',
			'eval($_POST',
			'str_rot13(',
			'preg_replace(\'/.*/e\''
		];

		$themes_dir = get_theme_root();
		$plugins_dir = WP_PLUGIN_DIR;

		$flagged_files = [];
		
		// Very basic scanner to avoid timeout: only scan active theme and active plugins
		$active_theme = wp_get_theme()->get_stylesheet_directory();
		$active_plugins = get_option( 'active_plugins', [] );

		$directories_to_scan = [ $active_theme ];
		foreach ( $active_plugins as $plugin ) {
			$directories_to_scan[] = dirname( $plugins_dir . '/' . $plugin );
		}

		foreach ( $directories_to_scan as $dir ) {
			if ( ! is_dir( $dir ) ) continue;
			
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
			foreach ( $files as $file ) {
				if ( $file->getExtension() === 'php' ) {
					$content = file_get_contents( $file->getPathname() );
					foreach ( $suspicious_patterns as $pattern ) {
						if ( strpos( $content, $pattern ) !== false ) {
							$flagged_files[] = $file->getPathname();
							break;
						}
					}
				}
			}
		}

		if ( ! empty( $flagged_files ) ) {
			update_option( 'og_wp_malware_scan_results', $flagged_files, false );
			return true;
		}

		delete_option( 'og_wp_malware_scan_results' );
		return false;
	}
}
