<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Duplicator {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;
		
		add_action( 'admin_action_og_wp_duplicate_post', array( $this, 'duplicate_post_action' ) );
		
		// Add settings hook
		add_action( 'og_wp_module_settings_duplicator', array( $this, 'render_settings' ) );

		if ( ! empty( $this->options['enable_module_duplicator'] ) ) {
			$saved_types = isset( $this->options['duplicator_post_types'] ) ? $this->options['duplicator_post_types'] : array( 'post', 'page' );
			foreach ( $saved_types as $post_type ) {
				add_filter( "{$post_type}_row_actions", array( $this, 'add_duplicate_link' ), 10, 2 );
				if ( $post_type === 'page' ) {
					add_filter( "page_row_actions", array( $this, 'add_duplicate_link' ), 10, 2 );
				}
			}
		}
	}

	public function render_settings( $options ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$saved_types = isset( $options['duplicator_post_types'] ) ? $options['duplicator_post_types'] : array( 'post', 'page' );
		$status = isset( $options['duplicator_status'] ) ? $options['duplicator_status'] : 'draft';
		?>
		<div class="og-wp-form-row">
			<label>Supported Post Types</label>
			<div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:5px;">
				<?php foreach ( $post_types as $pt ) : ?>
					<label style="display:flex; align-items:center; font-weight:normal;">
						<input type="checkbox" name="og_wp_options[duplicator_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $saved_types ) ); ?> />
						<span style="margin-left:5px;"><?php echo esc_html( $pt->label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="description">Select which post types should have the Duplicate action link.</p>
		</div>

		<div class="og-wp-form-row" style="margin-top:15px;">
			<label>Duplicate Status</label>
			<select name="og_wp_options[duplicator_status]">
				<option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
				<option value="pending" <?php selected( $status, 'pending' ); ?>>Pending Review</option>
				<option value="private" <?php selected( $status, 'private' ); ?>>Private</option>
			</select>
			<p class="description">The post status applied to the newly duplicated content.</p>
		</div>
		<?php
	}

	public function add_duplicate_link( $actions, $post ) {
		if ( current_user_can( 'edit_posts' ) ) {
			$url = wp_nonce_url(
				admin_url( 'admin.php?action=og_wp_duplicate_post&post=' . $post->ID ),
				'og_wp_duplicate_' . $post->ID
			);
			$actions['duplicate'] = '<a href="' . esc_url( $url ) . '" title="Duplicate this item">Duplicate</a>';
		}
		return $actions;
	}

	public function duplicate_post_action() {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( 'No post ID provided.' );
		}
		$post_id = absint( $_GET['post'] );
		
		check_admin_referer( 'og_wp_duplicate_' . $post_id );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Unauthorized.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( 'Post not found.' );
		}

		$current_user = wp_get_current_user();
		$status = isset( $this->options['duplicator_status'] ) ? $this->options['duplicator_status'] : 'draft';

		$new_post_args = array(
			'post_title'     => $post->post_title . ' (Copy)',
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_status'    => $status,
			'post_type'      => $post->post_type,
			'post_author'    => $current_user->ID,
			'post_password'  => $post->post_password,
			'post_name'      => $post->post_name . '-copy',
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
		);

		$new_post_id = wp_insert_post( $new_post_args );

		if ( ! is_wp_error( $new_post_id ) ) {
			// Taxonomies
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $taxonomies as $taxonomy ) {
				$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
				wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
			}

			// Post meta
			global $wpdb;
			$post_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post_id ) );
			if ( count( $post_meta ) != 0 ) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
				$sql_values = array();
				foreach ( $post_meta as $meta_info ) {
					$meta_key = $meta_info->meta_key;
					if ( $meta_key === '_wp_old_slug' ) continue;
					$meta_value = addslashes( $meta_info->meta_value );
					$sql_values[] = "($new_post_id, '$meta_key', '$meta_value')";
				}
				if ( ! empty( $sql_values ) ) {
					$sql_query .= implode( ",", $sql_values );
					$wpdb->query( $sql_query );
				}
			}

			wp_redirect( admin_url( 'edit.php?post_type=' . $post->post_type ) );
			exit;
		} else {
			wp_die( 'Failed to duplicate.' );
		}
	}
}
