<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_CF7 {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;
		
		add_action( 'og_wp_module_settings_cf7', array( $this, 'render_settings' ) );

		if ( empty( $this->options['enable_module_cf7'] ) ) {
			return;
		}

		add_action( 'wpcf7_before_send_mail', array( $this, 'intercept_attachments' ), 10, 3 );
		add_action( 'wpcf7_mail_sent', array( $this, 'on_mail_sent' ) );
		add_filter( 'og_wp_pre_mail_args', array( $this, 'inject_source_args' ) );
	}

	public function ensure_tables_exist() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'og_wp_cf7_submissions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			submission_hash varchar(64) NOT NULL,
			data longtext NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function render_settings( $options ) {
		$capture = isset( $options['cf7_local_capture'] ) ? $options['cf7_local_capture'] : '0';
		?>
		<div class="og-wp-form-row">
			<label>Local Data Capture</label>
			<label class="og-wp-switch">
				<input type="hidden" name="og_wp_options[cf7_local_capture]" value="0">
				<input type="checkbox" name="og_wp_options[cf7_local_capture]" value="1" <?php checked( $capture, '1' ); ?> />
				<span class="og-wp-slider"></span>
			</label>
			<p class="description">Save Contact Form 7 submissions locally in the database before sending emails.</p>
		</div>
		<?php
	}

	public function intercept_attachments( $contact_form, &$abort, $submission ) {
		$files = $submission->uploaded_files();
		if ( empty( $files ) ) return;

		$persistent_dir = wp_upload_dir()['basedir'] . '/og_wp_cf7_attachments';
		if ( ! is_dir( $persistent_dir ) ) {
			wp_mkdir_p( $persistent_dir );
			file_put_contents($persistent_dir . '/.htaccess', 'deny from all');
			file_put_contents($persistent_dir . '/index.php', '<?php // silence');
		}

		$new_files = [];
		foreach ( $files as $name => $paths ) {
			$new_paths = [];
			foreach ( (array) $paths as $path ) {
				if ( file_exists( $path ) ) {
					$new_path = $persistent_dir . '/' . basename( $path ) . '_' . uniqid();
					if ( copy( $path, $new_path ) ) {
						$new_paths[] = $new_path;
					} else {
						$new_paths[] = $path; // Fallback
					}
				}
			}
			$new_files[$name] = $new_paths;
		}

		// Inject new files into the mail component so CF7 passes the copied files to wp_mail instead
		$mail = $contact_form->prop( 'mail' );
		if ( ! empty( $mail['attachments'] ) ) {
			// This is tricky because CF7 resolves attachments from the shortcodes.
			// The safest way to preserve attachments across async requests is to store them in a transient 
			// and hook into the actual email sending process.
			// However, since we intercept pre_wp_mail, we can just alter the global state.
		}
		$GLOBALS['og_wp_cf7_files'] = $new_files;
		$GLOBALS['og_wp_cf7_submission_hash'] = md5( uniqid() );
		$GLOBALS['og_wp_cf7_form_id'] = $contact_form->id();
		
		if ( ! empty( $this->options['cf7_local_capture'] ) ) {
			$this->save_submission( $contact_form, $submission );
		}
	}
	
	private function save_submission( $contact_form, $submission ) {
		global $wpdb;
		$data = $submission->get_posted_data();
		$wpdb->insert(
			$wpdb->prefix . 'og_wp_cf7_submissions',
			array(
				'form_id' => $contact_form->id(),
				'submission_hash' => $GLOBALS['og_wp_cf7_submission_hash'],
				'data' => wp_json_encode( $data )
			)
		);
	}

	public function inject_source_args( $args ) {
		if ( isset( $GLOBALS['og_wp_cf7_submission_hash'] ) ) {
			$args['source_plugin'] = 'contact-form-7';
			$args['source_ref'] = $GLOBALS['og_wp_cf7_submission_hash'];
			
			// Replace attachments with persistent ones
			if ( ! empty( $GLOBALS['og_wp_cf7_files'] ) ) {
				$new_atts = [];
				foreach ( $GLOBALS['og_wp_cf7_files'] as $paths ) {
					$new_atts = array_merge( $new_atts, $paths );
				}
				$args['attachments'] = $new_atts;
			}
		}
		return $args;
	}

	public function on_mail_sent( $contact_form ) {
		unset( $GLOBALS['og_wp_cf7_submission_hash'] );
		unset( $GLOBALS['og_wp_cf7_files'] );
		unset( $GLOBALS['og_wp_cf7_form_id'] );
	}
}
