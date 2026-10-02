<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Auth {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;

		// Disable XML-RPC
		if ( ! empty( $this->options['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', array( $this, 'remove_x_pingback' ) );
		}

		// Disable Application Passwords
		if ( ! empty( $this->options['disable_app_passwords'] ) ) {
			add_filter( 'wp_is_application_passwords_available', '__return_false' );
		}

		// Login Rate Limiting
		add_filter( 'authenticate', array( $this, 'check_login_rate_limit' ), 30, 3 );
		add_action( 'wp_login_failed', array( $this, 'record_failed_login' ) );

		// Enforce strong passwords (basic check on registration/profile update)
		add_action( 'user_profile_update_errors', array( $this, 'validate_strong_password' ), 10, 3 );
		
		// Optional math captcha on login
		add_action( 'login_form', array( $this, 'add_math_captcha' ) );
		add_filter( 'authenticate', array( $this, 'verify_math_captcha' ), 20, 3 );

		// Idle session logout
		add_action( 'init', array( $this, 'check_idle_session' ) );
		add_action( 'wp_login', array( $this, 'reset_idle_session' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'clear_idle_session' ) );

		// 2FA
		if ( ! empty( $this->options['auth_enable_2fa'] ) ) {
			add_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 10, 1 );
		}
	}

	public function check_idle_session() {
		if ( is_user_logged_in() ) {
			$timeout_minutes = isset( $this->options['auth_idle_timeout'] ) ? (int) $this->options['auth_idle_timeout'] : 60;
			$timeout = $timeout_minutes * 60;
			$last_activity = get_user_meta( get_current_user_id(), 'og_wp_last_activity', true );
			
			if ( $last_activity && ( time() - $last_activity > $timeout ) ) {
				wp_logout();
				wp_redirect( wp_login_url() . '?logged_out=idle' );
				exit;
			} elseif ( ! $last_activity || ( time() - $last_activity > 60 ) ) {
				// Throttle DB writes: only update if at least 60 seconds have passed since last update
				update_user_meta( get_current_user_id(), 'og_wp_last_activity', time() );
			}
		}
	}

	public function reset_idle_session( $user_login, $user ) {
		update_user_meta( $user->ID, 'og_wp_last_activity', time() );
	}

	public function clear_idle_session() {
		delete_user_meta( get_current_user_id(), 'og_wp_last_activity' );
	}

	public function remove_x_pingback( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	public function get_client_ip() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
		}
		return trim( $ip );
	}

	public function check_login_rate_limit( $user, $username, $password ) {
		$ip = $this->get_client_ip();
		$attempts = get_transient( 'og_wp_failed_logins_' . md5( $ip ) );
		
		if ( $attempts !== false && $attempts >= 5 ) {
			// Trigger lockout email once per lockout cycle
			if ( $attempts == 5 ) {
				$admin_email = get_option( 'admin_email' );
				wp_mail( $admin_email, 'Security Alert: IP Locked Out', "The IP {$ip} has been locked out after 5 failed login attempts." );
				set_transient( 'og_wp_failed_logins_' . md5( $ip ), 6, 15 * MINUTE_IN_SECONDS ); // Bump to 6 so we don't email again
			}
			return new WP_Error( 'too_many_attempts', '<strong>ERROR</strong>: Too many failed login attempts. Please try again later.' );
		}
		return $user;
	}

	public function record_failed_login( $username ) {
		$ip = $this->get_client_ip();
		$key = 'og_wp_failed_logins_' . md5( $ip );
		$attempts = get_transient( $key );
		
		if ( $attempts === false ) {
			set_transient( $key, 1, 15 * MINUTE_IN_SECONDS );
		} else {
			set_transient( $key, $attempts + 1, 15 * MINUTE_IN_SECONDS );
		}
	}

	public function validate_strong_password( $errors, $update, $user ) {
		if ( ! empty( $_POST['pass1'] ) ) {
			$password = $_POST['pass1'];
			if ( strlen( $password ) < 8 || ! preg_match( '/[A-Z]/', $password ) || ! preg_match( '/[0-9]/', $password ) ) {
				$errors->add( 'weak_password', '<strong>ERROR</strong>: Password must be at least 8 characters long, contain a number and an uppercase letter.' );
			}
		}
	}

	public function add_math_captcha() {
		$num1 = rand( 1, 10 );
		$num2 = rand( 1, 10 );
		$sum = $num1 + $num2;
		
		// Store sum securely in a transient bound to IP (could also use session but transient is native)
		$ip = $this->get_client_ip();
		set_transient( 'og_wp_captcha_' . md5( $ip ), $sum, 5 * MINUTE_IN_SECONDS );

		echo '<p>';
		echo '<label for="og_wp_captcha">Security Check: ' . $num1 . ' + ' . $num2 . ' =</label>';
		echo '<input type="text" name="og_wp_captcha" id="og_wp_captcha" class="input" value="" size="20" required />';
		echo '</p>';
	}

	public function verify_math_captcha( $user, $username, $password ) {
		// Only check if it's a POST request for login, not empty username/pass, and not in admin dashboard
		if ( isset( $_POST['wp-submit'] ) && ! is_admin() && isset( $_POST['log'] ) ) {
			$ip = $this->get_client_ip();
			$expected_sum = get_transient( 'og_wp_captcha_' . md5( $ip ) );
			$user_sum = isset( $_POST['og_wp_captcha'] ) ? intval( $_POST['og_wp_captcha'] ) : '';

			if ( $expected_sum === false || $user_sum !== (int)$expected_sum ) {
				return new WP_Error( 'invalid_captcha', '<strong>ERROR</strong>: Incorrect math captcha answer.' );
			}
			
			// Clear on success
			delete_transient( 'og_wp_captcha_' . md5( $ip ) );
		}
		return $user;
	}

	// Basic Email 2FA
	public function wp_authenticate_user( $user ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( isset( $_POST['og_wp_2fa_code'] ) ) {
			$saved_code = get_user_meta( $user->ID, 'og_wp_2fa_code', true );
			if ( $_POST['og_wp_2fa_code'] === $saved_code ) {
				delete_user_meta( $user->ID, 'og_wp_2fa_code' );
				return $user; // Success
			} else {
				return new WP_Error( 'invalid_2fa', '<strong>ERROR</strong>: Invalid 2FA code.' );
			}
		}

		// If no code provided, generate and send one, then show the form
		$code = sprintf( "%06d", mt_rand( 1, 999999 ) );
		update_user_meta( $user->ID, 'og_wp_2fa_code', $code );

		wp_mail( $user->user_email, 'Your Login 2FA Code', "Your login code is: $code" );

		// Render the 2FA form and halt login
		ob_start();
		login_header( 'Two-Factor Authentication' );
		?>
		<form name="loginform" id="loginform" action="" method="post">
			<p>
				<label for="og_wp_2fa_code">Check your email for the 2FA code.<br />
				<input type="text" name="og_wp_2fa_code" id="og_wp_2fa_code" class="input" value="" size="20" required /></label>
			</p>
			<input type="hidden" name="log" value="<?php echo esc_attr( $_POST['log'] ?? '' ); ?>" />
			<input type="hidden" name="pwd" value="<?php echo esc_attr( $_POST['pwd'] ?? '' ); ?>" />
			<input type="hidden" name="wp-submit" value="Log In" />
			<p class="submit">
				<input type="submit" name="wp-submit-2fa" id="wp-submit" class="button button-primary button-large" value="Verify" />
			</p>
		</form>
		<?php
		login_footer();
		exit;
	}
}
