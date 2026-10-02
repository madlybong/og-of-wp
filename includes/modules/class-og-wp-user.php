<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_User {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Block username enumeration
		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'block_user_enumeration' ) );
			add_filter( 'rest_endpoints', array( $this, 'disable_rest_users_endpoint' ) );
		}

		// Disable user registration
		if ( ! empty( $this->options['disable_registration'] ) ) {
			add_filter( 'option_users_can_register', '__return_zero' );
		}

		// Block subscriber admin access
		add_action( 'admin_init', array( $this, 'block_subscriber_admin_access' ) );

		// REST API Authentication Guard
		add_filter( 'rest_authentication_errors', array( $this, 'restrict_rest_api' ) );
	}

	public function restrict_rest_api( $result ) {
		// If a previous authentication check applied an error, return it.
		if ( ! empty( $result ) ) {
			return $result;
		}
		
		// If not logged in, block
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
		}
		
		return $result;
	}

	public function block_user_enumeration() {
		// Block ?author=N queries
		if ( isset( $_GET['author'] ) && ! empty( $_GET['author'] ) ) {
			$this->trigger_enumeration_block();
		}
	}

	public function disable_rest_users_endpoint( $endpoints ) {
		// Remove the /wp/v2/users endpoint for non-admins
		if ( ! current_user_can( 'list_users' ) ) {
			if ( isset( $endpoints['/wp/v2/users'] ) ) {
				unset( $endpoints['/wp/v2/users'] );
			}
			if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
				unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
			}
		}
		return $endpoints;
	}

	private function trigger_enumeration_block() {
		do_action( 'og_wp_log_event', 'enumeration_blocked', 'Blocked username enumeration attempt.' );
		wp_die( 'Forbidden: Username enumeration is blocked.', 403 );
	}

	public function block_subscriber_admin_access() {
		if ( wp_doing_ajax() ) {
			return;
		}

		$user = wp_get_current_user();
		
		// If user has only subscriber/contributor capabilities, block access to wp-admin
		if ( isset( $user->roles ) && is_array( $user->roles ) ) {
			$allowed_roles = array( 'administrator', 'editor', 'author' );
			$has_allowed_role = false;
			
			foreach ( $user->roles as $role ) {
				if ( in_array( $role, $allowed_roles ) ) {
					$has_allowed_role = true;
					break;
				}
			}

			if ( ! $has_allowed_role ) {
				wp_redirect( home_url() );
				exit;
			}
		}
	}
}
