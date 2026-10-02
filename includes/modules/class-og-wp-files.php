<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Files {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Disable file editing in WP Admin
		add_filter( 'map_meta_cap', array( $this, 'disable_file_editing_caps' ), 10, 2 );

		// Secure the uploads directory (on plugin load/activation)
		add_action( 'admin_init', array( $this, 'secure_uploads_dir' ) );
	}

	public function disable_file_editing_caps( $caps, $cap ) {
		$blocked_caps = array( 'edit_files', 'edit_plugins', 'edit_themes' );
		if ( in_array( $cap, $blocked_caps ) ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	}

	public function secure_uploads_dir() {
		// Run this once per day or on activation
		$transient_key = 'og_wp_uploads_dir_check';
		if ( get_transient( $transient_key ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		$basedir = $upload_dir['basedir'];

		if ( wp_is_writable( $basedir ) ) {
			// Write index.php to prevent directory listing
			$index_file = $basedir . '/index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, '<?php // Silence is golden.' );
			}

			// Write .htaccess to prevent PHP execution & hotlinking
			$htaccess_file = $basedir . '/.htaccess';
			$htaccess_content = "<Files *.php>\nDeny from all\n</Files>\n";
			
			$site_host = parse_url( home_url(), PHP_URL_HOST );
			$htaccess_content .= "\n<IfModule mod_rewrite.c>\nRewriteEngine on\nRewriteCond %{HTTP_REFERER} !^$\nRewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?{$site_host} [NC]\nRewriteRule \.(jpg|jpeg|png|gif|svg)$ - [NC,F,L]\n</IfModule>\n";
			
			if ( ! file_exists( $htaccess_file ) ) {
				file_put_contents( $htaccess_file, $htaccess_content );
			} else {
				$current_htaccess = file_get_contents( $htaccess_file );
				if ( strpos( $current_htaccess, '<Files *.php>' ) === false ) {
					file_put_contents( $htaccess_file, $htaccess_content, FILE_APPEND );
				}
			}
		}

		set_transient( $transient_key, true, DAY_IN_SECONDS );
	}
}
