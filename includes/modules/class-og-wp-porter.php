<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Porter {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;
		
		add_action( 'admin_action_og_wp_export_post', array( $this, 'export_post_action' ) );
		add_action( 'admin_post_og_wp_import_post', array( $this, 'import_post_action' ) );
		
		add_action( 'og_wp_module_settings_porter', array( $this, 'render_settings' ) );

		if ( ! empty( $this->options['enable_module_porter'] ) ) {
			add_filter( 'post_row_actions', array( $this, 'add_export_link' ), 10, 2 );
			add_filter( 'page_row_actions', array( $this, 'add_export_link' ), 10, 2 );
		}
	}

	public function render_settings( $options ) {
		$include_meta = isset( $options['porter_include_meta'] ) ? $options['porter_include_meta'] : '1';
		$import_image = isset( $options['porter_import_image'] ) ? $options['porter_import_image'] : '1';
		?>
		<div class="og-wp-form-row">
			<label>Export Format</label>
			<select disabled>
				<option>JSON</option>
			</select>
			<p class="description">Currently only JSON is supported.</p>
		</div>

		<div class="og-wp-form-row">
			<label>Export Metadata</label>
			<label class="og-wp-switch">
				<input type="hidden" name="og_wp_options[porter_include_meta]" value="0"><input type="checkbox" name="og_wp_options[porter_include_meta]" value="1" <?php checked( $include_meta, '1' ); ?> />
				<span class="og-wp-slider"></span>
			</label>
			<p class="description">Include custom fields and post meta in the export file.</p>
		</div>

		<div class="og-wp-form-row">
			<label>Re-download Featured Image on Import</label>
			<label class="og-wp-switch">
				<input type="hidden" name="og_wp_options[porter_import_image]" value="0"><input type="checkbox" name="og_wp_options[porter_import_image]" value="1" <?php checked( $import_image, '1' ); ?> />
				<span class="og-wp-slider"></span>
			</label>
			<p class="description">If enabled, attempts to fetch and import the featured image from the original URL during import.</p>
		</div>

		<hr style="margin:20px 0; border:0; border-top:1px solid #e2e8f0;">

		<div class="og-wp-form-row">
			<label>Import Post/Page</label>
			<div style="background: #f8fafc; padding:15px; border: 1px dashed #cbd5e1; border-radius:4px; max-width: 400px;">
				<input type="file" name="import_file" accept=".json" required form="og-wp-import-form" style="margin-bottom: 10px; display:block;">
				<button type="submit" form="og-wp-import-form" class="button button-secondary">Import File</button>
			</div>
			<p class="description">Upload a .json file previously exported by Content Porter.</p>
		</div>
		<?php
		// Add the actual form to the admin footer to avoid nested forms
		add_action( 'admin_footer', function() {
			?>
			<form id="og-wp-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="display:none;">
				<input type="hidden" name="action" value="og_wp_import_post">
				<?php wp_nonce_field( 'og_wp_import_post' ); ?>
			</form>
			<?php
		});
	}

	public function add_export_link( $actions, $post ) {
		if ( current_user_can( 'edit_posts' ) ) {
			$url = wp_nonce_url(
				admin_url( 'admin.php?action=og_wp_export_post&post=' . $post->ID ),
				'og_wp_export_' . $post->ID
			);
			$actions['export'] = '<a href="' . esc_url( $url ) . '" title="Export to JSON">Export</a>';
		}
		return $actions;
	}

	public function export_post_action() {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( 'No post ID provided.' );
		}
		$post_id = absint( $_GET['post'] );
		
		check_admin_referer( 'og_wp_export_' . $post_id );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Unauthorized.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( 'Post not found.' );
		}

		$export_data = array(
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_type'    => $post->post_type,
			'post_name'    => $post->post_name,
			'terms'        => array(),
			'meta'         => array(),
			'featured_img' => ''
		);

		// Taxonomies
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
			if ( ! empty( $terms ) ) {
				$export_data['terms'][$taxonomy] = $terms;
			}
		}

		// Meta
		$include_meta = isset( $this->options['porter_include_meta'] ) ? $this->options['porter_include_meta'] : '1';
		if ( $include_meta == '1' ) {
			$meta = get_post_meta( $post_id );
			foreach ( $meta as $key => $values ) {
				if ( $key === '_wp_old_slug' ) continue;
				if ( $key === '_thumbnail_id' ) {
					$export_data['featured_img'] = wp_get_attachment_url( $values[0] );
					continue;
				}
				$export_data['meta'][$key] = $values;
			}
		}

		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="' . $post->post_type . '-' . $post->post_name . '.json"');
		echo json_encode( $export_data );
		exit;
	}

	public function import_post_action() {
		check_admin_referer( 'og_wp_import_post' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Unauthorized.' );
		}

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_die( 'No file uploaded.' );
		}

		$content = file_get_contents( $_FILES['import_file']['tmp_name'] );
		$data = json_decode( $content, true );

		if ( ! $data || empty( $data['post_title'] ) ) {
			wp_die( 'Invalid JSON format.' );
		}

		$current_user = wp_get_current_user();
		
		$new_post_args = array(
			'post_title'   => $data['post_title'],
			'post_content' => $data['post_content'],
			'post_excerpt' => $data['post_excerpt'],
			'post_type'    => $data['post_type'],
			'post_status'  => 'draft',
			'post_author'  => $current_user->ID,
			'post_name'    => $data['post_name'] . '-imported',
		);

		$new_post_id = wp_insert_post( $new_post_args );

		if ( ! is_wp_error( $new_post_id ) ) {
			// Terms
			if ( ! empty( $data['terms'] ) ) {
				foreach ( $data['terms'] as $taxonomy => $terms ) {
					wp_set_object_terms( $new_post_id, $terms, $taxonomy, false );
				}
			}

			// Meta
			if ( ! empty( $data['meta'] ) ) {
				foreach ( $data['meta'] as $key => $values ) {
					foreach ( $values as $value ) {
						add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
					}
				}
			}

			// Featured Image
			$import_image = isset( $this->options['porter_import_image'] ) ? $this->options['porter_import_image'] : '1';
			if ( $import_image == '1' && ! empty( $data['featured_img'] ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				
				$thumb_id = media_sideload_image( $data['featured_img'], $new_post_id, null, 'id' );
				if ( ! is_wp_error( $thumb_id ) ) {
					set_post_thumbnail( $new_post_id, $thumb_id );
				}
			}

			wp_redirect( admin_url( 'edit.php?post_type=' . $data['post_type'] ) );
			exit;
		} else {
			wp_die( 'Failed to import post.' );
		}
	}
}

