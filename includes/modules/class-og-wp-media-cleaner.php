<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Media_Cleaner {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;
		
		add_action( 'og_wp_module_settings_media_cleaner', array( $this, 'render_settings' ) );
		
		add_action( 'wp_ajax_og_wp_scan_media', array( $this, 'ajax_scan_media' ) );
		add_action( 'wp_ajax_og_wp_clean_media', array( $this, 'ajax_clean_media' ) );
	}

	public function render_settings( $options ) {
		?>
		<div class="og-wp-form-row">
			<label>Media Cleaner</label>
			<p class="description">Scan your media library for images that are not used in any post, page, or metadata. <strong>A backup zip is automatically created before any deletion.</strong></p>
			
			<div style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px;">
				<div style="display:flex; gap: 10px; align-items:center;">
					<button type="button" class="button" onclick="ogWpScanMedia()" id="btn-scan-media">Scan Now</button>
					<button type="button" class="button button-primary" onclick="ogWpCleanMedia()" id="btn-clean-media" style="display:none; background: var(--og-wp-red); border-color: var(--og-wp-red);">Create Backup & Clean</button>
					<span id="media-scan-result" style="font-weight:600;"></span>
				</div>
				<div id="media-clean-progress" style="margin-top:10px; display:none; color: var(--og-wp-green);"></div>
			</div>
		</div>

		<script>
		function ogWpScanMedia() {
			var btn = document.getElementById('btn-scan-media');
			var res = document.getElementById('media-scan-result');
			var cleanBtn = document.getElementById('btn-clean-media');
			
			btn.disabled = true;
			btn.textContent = "Scanning...";
			res.textContent = "";
			cleanBtn.style.display = 'none';

			var formData = new URLSearchParams();
			formData.append('action', 'og_wp_scan_media');

			fetch(ajaxurl, {
				method: 'POST',
				body: formData,
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			})
			.then(r => r.json())
			.then(data => {
				btn.disabled = false;
				btn.textContent = "Scan Again";
				if (data.success) {
					res.textContent = data.data.message;
					if (data.data.count > 0) {
						cleanBtn.style.display = 'inline-block';
					}
				} else {
					res.textContent = "Error: " + data.data;
				}
			});
		}

		function ogWpCleanMedia() {
			if (!confirm("This will create a backup zip and then permanently delete orphaned media. Proceed?")) return;
			
			var btn = document.getElementById('btn-clean-media');
			var scanBtn = document.getElementById('btn-scan-media');
			var prog = document.getElementById('media-clean-progress');
			
			btn.disabled = true;
			scanBtn.disabled = true;
			btn.textContent = "Backing up and cleaning...";
			prog.style.display = 'block';
			prog.textContent = "Please wait, this may take a while...";

			var formData = new URLSearchParams();
			formData.append('action', 'og_wp_clean_media');

			fetch(ajaxurl, {
				method: 'POST',
				body: formData,
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			})
			.then(r => r.json())
			.then(data => {
				btn.disabled = false;
				scanBtn.disabled = false;
				btn.textContent = "Create Backup & Clean";
				if (data.success) {
					prog.innerHTML = data.data.message;
					btn.style.display = 'none';
					document.getElementById('media-scan-result').textContent = "";
				} else {
					prog.innerHTML = "<span style='color:red'>Error: " + data.data + "</span>";
				}
			});
		}
		</script>
		<?php
	}

	private function get_orphaned_media() {
		global $wpdb;
		
		// 1. Get all attachments
		$attachments = $wpdb->get_results( "SELECT ID, guid FROM $wpdb->posts WHERE post_type = 'attachment'" );
		
		// 2. Get all post contents
		$post_contents = $wpdb->get_results( "SELECT post_content FROM $wpdb->posts WHERE post_type NOT IN ('attachment', 'revision') AND post_status != 'auto-draft'" );
		$all_content = '';
		foreach ( $post_contents as $p ) {
			$all_content .= $p->post_content . ' ';
		}

		// 3. Get all meta values (could be featured images, gallery ids, etc)
		$meta_values = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key IN ('_thumbnail_id')" );
		$used_ids = array();
		foreach ( $meta_values as $m ) {
			$used_ids[] = (int) $m->meta_value;
		}

		$orphaned = array();
		
		foreach ( $attachments as $att ) {
			$id = $att->ID;
			
			// Is it a featured image?
			if ( in_array( $id, $used_ids ) ) {
				continue;
			}
			
			// Get image URLs to check in content
			$file_path = get_attached_file( $id );
			if ( ! $file_path || ! file_exists( $file_path ) ) {
				$orphaned[] = $id; // file doesn't exist, it's orphan data
				continue;
			}
			
			$filename = basename( $file_path );
			// A simple check: is the filename string present anywhere in the post contents?
			if ( strpos( $all_content, $filename ) !== false ) {
				continue;
			}

			// If we got here, it's highly likely orphaned
			$orphaned[] = $id;
		}
		
		return $orphaned;
	}

	public function ajax_scan_media() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$orphaned = $this->get_orphaned_media();
		
		$count = count( $orphaned );
		if ( $count === 0 ) {
			wp_send_json_success( array( 'count' => 0, 'message' => '0 orphaned files found. Your media library is clean!' ) );
		} else {
			// calculate approximate size
			$total_size = 0;
			foreach ( $orphaned as $id ) {
				$file = get_attached_file( $id );
				if ( $file && file_exists( $file ) ) {
					$total_size += filesize( $file );
				}
			}
			$mb = round( $total_size / 1024 / 1024, 2 );
			wp_send_json_success( array( 'count' => $count, 'message' => "$count orphaned files found, approx $mb MB wasted." ) );
		}
	}

	public function ajax_clean_media() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$orphaned = $this->get_orphaned_media();
		if ( empty( $orphaned ) ) {
			wp_send_json_error( 'No orphaned media to clean.' );
		}

		// 1. Backup
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] . '/og-wp-backups';
		if ( ! file_exists( $backup_dir ) ) {
			mkdir( $backup_dir, 0755, true );
		}
		
		// Create empty index.php for security
		if ( ! file_exists( $backup_dir . '/index.php' ) ) {
			file_put_contents( $backup_dir . '/index.php', '<?php // silence' );
		}

		$backup_file = $backup_dir . '/og-wp-media-backup-' . date('Ymd-His') . '.zip';
		
		if ( class_exists('ZipArchive') ) {
			$zip = new ZipArchive();
			if ( $zip->open( $backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) === true ) {
				foreach ( $orphaned as $id ) {
					$file = get_attached_file( $id );
					if ( $file && file_exists( $file ) ) {
						$zip->addFile( $file, basename( $file ) );
					}
				}
				$zip->close();
			} else {
				wp_send_json_error( 'Failed to create backup zip. Aborting clean.' );
			}
		} else {
			wp_send_json_error( 'ZipArchive extension not available on this server.' );
		}

		// 2. Clean
		$deleted = 0;
		foreach ( $orphaned as $id ) {
			if ( wp_delete_attachment( $id, true ) ) {
				$deleted++;
			}
		}

		$backup_url = $upload_dir['baseurl'] . '/og-wp-backups/' . basename( $backup_file );

		wp_send_json_success( array( 
			'message' => "Successfully cleaned $deleted orphaned files. <br><a href='" . esc_url( $backup_url ) . "' download>Download Backup Zip</a>"
		) );
	}
}
