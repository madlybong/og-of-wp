<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Settings {
	private $option_name = 'og_wp_options';
	private $page_slug   = 'og-of-wp';

	public function add_plugin_admin_menu() {
		add_menu_page(
			'OG of WP',
			'OG of WP',
			'manage_options',
			$this->page_slug,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-shield',
			80
		);
	}

	public function register_settings() {
		register_setting( 'og_wp_option_group', $this->option_name, array( $this, 'sanitize' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	public function enqueue_admin_styles( $hook ) {
		if ( $hook !== 'toplevel_page_' . $this->page_slug ) {
			return;
		}
		wp_enqueue_style( 'og-wp-admin-css', OG_WP_PLUGIN_URL . 'admin/css/og-wp-admin.css', array(), OG_WP_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		
		$sanitized_input = array();
		foreach ( $input as $key => $value ) {
			if ( is_array( $value ) ) {
				$sanitized_input[ sanitize_key( $key ) ] = array_map( 'sanitize_text_field', $value );
			} else {
				// We don't want to over-sanitize things like CSP which has semicolons, but sanitize_text_field strips some things.
				// For textarea fields we should use sanitize_textarea_field
				if ( strpos( $key, 'csp' ) !== false || strpos( $key, 'allowlist' ) !== false || strpos( $key, 'keywords' ) !== false ) {
					$sanitized_input[ sanitize_key( $key ) ] = sanitize_textarea_field( $value );
				} else {
					$sanitized_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
				}
			}
		}
		return $sanitized_input;
	}

	public function display_plugin_admin_page() {
		$options = get_option( $this->option_name, array() );
		
		// Register module UI hooks here so they display inline
		add_action( 'og_wp_module_settings_auth', array( $this, 'render_auth_settings' ) );
		add_action( 'og_wp_module_settings_waf', array( $this, 'render_waf_settings' ) );
		add_action( 'og_wp_module_settings_files', array( $this, 'render_files_settings' ) );
		add_action( 'og_wp_module_settings_scanner', array( $this, 'render_scanner_settings' ) );
		add_action( 'og_wp_module_settings_spam', array( $this, 'render_spam_settings' ) );
		add_action( 'og_wp_module_settings_headers', array( $this, 'render_headers_settings' ) );
		add_action( 'og_wp_module_settings_audit', array( $this, 'render_audit_settings' ) );
		add_action( 'og_wp_module_settings_ssl', array( $this, 'render_ssl_settings' ) );
		add_action( 'og_wp_module_settings_db', array( $this, 'render_db_settings' ) );
		add_action( 'og_wp_module_settings_user', array( $this, 'render_user_settings' ) );
		add_action( 'og_wp_module_settings_hardening', array( $this, 'render_hardening_settings' ) );

		require_once OG_WP_PLUGIN_DIR . 'admin/views/admin-display.php';
	}

	public function render_auth_settings( $options ) {
		$slug = $options['auth_custom_login_slug'] ?? '';
		$attempts = $options['auth_max_attempts'] ?? '5';
		$lockout = $options['auth_lockout_duration'] ?? '15';
		$idle = $options['auth_idle_timeout'] ?? '60';
		$captcha = isset($options['auth_enable_captcha']) ? $options['auth_enable_captcha'] : '1';
		$twofa = isset($options['auth_enable_2fa']) ? $options['auth_enable_2fa'] : '0';
		?>
		<div class="og-wp-form-row">
			<label>Custom Login URL Slug</label>
			<input type="text" name="og_wp_options[auth_custom_login_slug]" value="<?php echo esc_attr($slug); ?>" placeholder="e.g. secure-login">
			<span class="og-wp-form-help">Leave empty to use default wp-login.php. If set, wp-login.php will return 404.</span>
		</div>
		<div class="og-wp-form-row">
			<label>Max Failed Login Attempts</label>
			<input type="number" name="og_wp_options[auth_max_attempts]" value="<?php echo esc_attr($attempts); ?>" min="1">
		</div>
		<div class="og-wp-form-row">
			<label>Lockout Duration (Minutes)</label>
			<input type="number" name="og_wp_options[auth_lockout_duration]" value="<?php echo esc_attr($lockout); ?>" min="1">
		</div>
		<div class="og-wp-form-row">
			<label>Idle Session Timeout (Minutes)</label>
			<input type="number" name="og_wp_options[auth_idle_timeout]" value="<?php echo esc_attr($idle); ?>" min="1">
		</div>
		<div class="og-wp-form-row">
			<label>
				<input type="checkbox" name="og_wp_options[auth_enable_captcha]" value="1" <?php checked($captcha, '1'); ?>>
				Enable Math CAPTCHA on Login
			</label>
		</div>
		<div class="og-wp-form-row">
			<label>
				<input type="checkbox" name="og_wp_options[auth_enable_2fa]" value="1" <?php checked($twofa, '1'); ?>>
				Enable Email 2FA for all users
			</label>
		</div>
		<?php
	}

	public function render_waf_settings( $options ) {
		$allowlist = $options['waf_admin_ip_allowlist'] ?? '';
		$blocklist = $options['waf_block_ip_list'] ?? '';
		$msg = $options['waf_block_message'] ?? 'Forbidden';
		$sqli = isset($options['waf_enable_sqli']) ? $options['waf_enable_sqli'] : '1';
		$bots = isset($options['waf_enable_bots']) ? $options['waf_enable_bots'] : '1';
		?>
		<div class="og-wp-form-row">
			<label>Admin IP Allowlist</label>
			<textarea name="og_wp_options[waf_admin_ip_allowlist]" placeholder="Comma-separated IPs"><?php echo esc_textarea($allowlist); ?></textarea>
			<span class="og-wp-form-help">Only these IPs can access wp-admin. Leave empty to allow all.</span>
		</div>
		<div class="og-wp-form-row">
			<label>Block IP List</label>
			<textarea name="og_wp_options[waf_block_ip_list]" placeholder="Comma-separated IPs"><?php echo esc_textarea($blocklist); ?></textarea>
			<span class="og-wp-form-help">These IPs will be blocked from accessing the site entirely.</span>
		</div>
		<div class="og-wp-form-row">
			<label>WAF Block Message</label>
			<input type="text" name="og_wp_options[waf_block_message]" value="<?php echo esc_attr($msg); ?>">
		</div>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[waf_enable_sqli]" value="1" <?php checked($sqli, '1'); ?>> Enable SQL Injection Protection</label><br>
			<label><input type="checkbox" name="og_wp_options[waf_enable_bots]" value="1" <?php checked($bots, '1'); ?>> Enable Bad Bot Blocking</label>
		</div>
		<?php
	}

	public function render_files_settings( $options ) {
		$php = isset($options['files_block_php']) ? $options['files_block_php'] : '1';
		$hotlink = isset($options['files_enable_hotlink']) ? $options['files_enable_hotlink'] : '1';
		$editor = isset($options['files_disable_editor']) ? $options['files_disable_editor'] : '1';
		?>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[files_block_php]" value="1" <?php checked($php, '1'); ?>> Block PHP execution in Uploads folder</label><br>
			<label><input type="checkbox" name="og_wp_options[files_enable_hotlink]" value="1" <?php checked($hotlink, '1'); ?>> Enable Image Hotlink Protection</label><br>
			<label><input type="checkbox" name="og_wp_options[files_disable_editor]" value="1" <?php checked($editor, '1'); ?>> Disable Plugin/Theme Editor</label>
		</div>
		<?php
	}

	public function render_scanner_settings( $options ) {
		$freq = $options['scanner_frequency'] ?? 'daily';
		$scope = $options['scanner_scope'] ?? 'active';
		$email = isset($options['scanner_email_alerts']) ? $options['scanner_email_alerts'] : '1';
		?>
		<div class="og-wp-form-row">
			<label>Scan Frequency</label>
			<select name="og_wp_options[scanner_frequency]">
				<option value="daily" <?php selected($freq, 'daily'); ?>>Daily</option>
				<option value="weekly" <?php selected($freq, 'weekly'); ?>>Weekly</option>
			</select>
		</div>
		<div class="og-wp-form-row">
			<label>Scan Scope</label>
			<select name="og_wp_options[scanner_scope]">
				<option value="active" <?php selected($scope, 'active'); ?>>Active Plugins & Theme Only (Faster)</option>
				<option value="all" <?php selected($scope, 'all'); ?>>All Plugins & Themes</option>
			</select>
		</div>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[scanner_email_alerts]" value="1" <?php checked($email, '1'); ?>> Email me when a threat is found</label>
		</div>
		<?php
	}

	public function render_spam_settings( $options ) {
		$links = $options['spam_max_links'] ?? '2';
		$keywords = $options['spam_custom_keywords'] ?? '';
		$honeypot = isset($options['spam_enable_honeypot']) ? $options['spam_enable_honeypot'] : '1';
		$disposable = isset($options['spam_block_disposable']) ? $options['spam_block_disposable'] : '1';
		?>
		<div class="og-wp-form-row">
			<label>Max Links in Comments</label>
			<input type="number" name="og_wp_options[spam_max_links]" value="<?php echo esc_attr($links); ?>" min="0">
		</div>
		<div class="og-wp-form-row">
			<label>Custom Blocked Keywords</label>
			<textarea name="og_wp_options[spam_custom_keywords]" placeholder="Comma-separated keywords"><?php echo esc_textarea($keywords); ?></textarea>
		</div>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[spam_enable_honeypot]" value="1" <?php checked($honeypot, '1'); ?>> Enable Comment Form Honeypot</label><br>
			<label><input type="checkbox" name="og_wp_options[spam_block_disposable]" value="1" <?php checked($disposable, '1'); ?>> Block Disposable Email Registrations</label>
		</div>
		<?php
	}

	public function render_headers_settings( $options ) {
		$xframe = $options['headers_xframe'] ?? 'SAMEORIGIN';
		$referrer = $options['headers_referrer'] ?? 'strict-origin-when-cross-origin';
		$csp = $options['headers_csp'] ?? 'upgrade-insecure-requests;';
		$hsts = isset($options['headers_enable_hsts']) ? $options['headers_enable_hsts'] : '1';
		$hsts_age = $options['headers_hsts_age'] ?? '365';
		?>
		<div class="og-wp-form-row">
			<label>X-Frame-Options</label>
			<select name="og_wp_options[headers_xframe]">
				<option value="SAMEORIGIN" <?php selected($xframe, 'SAMEORIGIN'); ?>>SAMEORIGIN</option>
				<option value="DENY" <?php selected($xframe, 'DENY'); ?>>DENY</option>
			</select>
		</div>
		<div class="og-wp-form-row">
			<label>Referrer-Policy</label>
			<select name="og_wp_options[headers_referrer]">
				<option value="strict-origin-when-cross-origin" <?php selected($referrer, 'strict-origin-when-cross-origin'); ?>>strict-origin-when-cross-origin</option>
				<option value="no-referrer" <?php selected($referrer, 'no-referrer'); ?>>no-referrer</option>
			</select>
		</div>
		<div class="og-wp-form-row">
			<label>Content-Security-Policy</label>
			<textarea name="og_wp_options[headers_csp]"><?php echo esc_textarea($csp); ?></textarea>
		</div>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[headers_enable_hsts]" value="1" <?php checked($hsts, '1'); ?>> Enable HSTS (Strict-Transport-Security)</label>
		</div>
		<div class="og-wp-form-row">
			<label>HSTS Max-Age (Days)</label>
			<input type="number" name="og_wp_options[headers_hsts_age]" value="<?php echo esc_attr($hsts_age); ?>" min="1">
		</div>
		<?php
	}

	public function render_audit_settings( $options ) {
		$retention = $options['audit_retention_days'] ?? '30';
		?>
		<div class="og-wp-form-row">
			<label>Log Retention (Days)</label>
			<input type="number" name="og_wp_options[audit_retention_days]" value="<?php echo esc_attr($retention); ?>" min="1">
			<span class="og-wp-form-help">Logs older than this will be automatically deleted.</span>
		</div>
		<?php
	}

	public function render_ssl_settings( $options ) {
		$force = isset($options['ssl_force_https']) ? $options['ssl_force_https'] : '1';
		$mixed = isset($options['ssl_fix_mixed']) ? $options['ssl_fix_mixed'] : '1';
		$alerts = isset($options['ssl_cert_alerts']) ? $options['ssl_cert_alerts'] : '1';
		$days = $options['ssl_alert_days'] ?? '14';
		?>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[ssl_force_https]" value="1" <?php checked($force, '1'); ?>> Force HTTPS (301 Redirect)</label><br>
			<label><input type="checkbox" name="og_wp_options[ssl_fix_mixed]" value="1" <?php checked($mixed, '1'); ?>> Auto-Fix Mixed Content on the fly</label><br>
			<label><input type="checkbox" name="og_wp_options[ssl_cert_alerts]" value="1" <?php checked($alerts, '1'); ?>> Send SSL Expiry Alerts</label>
		</div>
		<div class="og-wp-form-row">
			<label>Alert Days Before Expiry</label>
			<input type="number" name="og_wp_options[ssl_alert_days]" value="<?php echo esc_attr($days); ?>" min="1">
		</div>
		<?php
	}

	public function render_db_settings( $options ) {
		$suppress = isset($options['db_suppress_errors']) ? $options['db_suppress_errors'] : '1';
		$prefix = isset($options['db_alert_prefix']) ? $options['db_alert_prefix'] : '1';
		?>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[db_suppress_errors]" value="1" <?php checked($suppress, '1'); ?>> Suppress DB Errors from public</label><br>
			<label><input type="checkbox" name="og_wp_options[db_alert_prefix]" value="1" <?php checked($prefix, '1'); ?>> Alert if default "wp_" prefix is used</label>
		</div>
		<?php
	}

	public function render_user_settings( $options ) {
		$enum = isset($options['user_block_enum']) ? $options['user_block_enum'] : '1';
		$rest = $options['user_restrict_rest'] ?? 'logged_in';
		$roles = $options['user_block_admin_roles'] ?? array('subscriber', 'contributor');
		?>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[user_block_enum]" value="1" <?php checked($enum, '1'); ?>> Block Username Enumeration</label>
		</div>
		<div class="og-wp-form-row">
			<label>Restrict REST API</label>
			<select name="og_wp_options[user_restrict_rest]">
				<option value="disabled" <?php selected($rest, 'disabled'); ?>>Fully Disabled (Breaks Gutenberg)</option>
				<option value="logged_in" <?php selected($rest, 'logged_in'); ?>>Logged In Users Only</option>
				<option value="off" <?php selected($rest, 'off'); ?>>Do Not Restrict</option>
			</select>
		</div>
		<div class="og-wp-form-row">
			<label>Block WP-Admin Access For Roles:</label>
			<label><input type="checkbox" name="og_wp_options[user_block_admin_roles][]" value="subscriber" <?php checked(in_array('subscriber', (array)$roles)); ?>> Subscriber</label><br>
			<label><input type="checkbox" name="og_wp_options[user_block_admin_roles][]" value="contributor" <?php checked(in_array('contributor', (array)$roles)); ?>> Contributor</label>
		</div>
		<?php
	}

	public function render_hardening_settings( $options ) {
		$version = isset($options['hardening_hide_version']) ? $options['hardening_hide_version'] : '1';
		$pingbacks = isset($options['hardening_disable_pingbacks']) ? $options['hardening_disable_pingbacks'] : '1';
		$cron = isset($options['hardening_disable_cron']) ? $options['hardening_disable_cron'] : '0';
		$author = isset($options['hardening_strip_author']) ? $options['hardening_strip_author'] : '1';
		?>
		<div class="og-wp-form-row">
			<label><input type="checkbox" name="og_wp_options[hardening_hide_version]" value="1" <?php checked($version, '1'); ?>> Hide WP Version</label><br>
			<label><input type="checkbox" name="og_wp_options[hardening_disable_pingbacks]" value="1" <?php checked($pingbacks, '1'); ?>> Disable Pingbacks</label><br>
			<label><input type="checkbox" name="og_wp_options[hardening_strip_author]" value="1" <?php checked($author, '1'); ?>> Strip Comment Author URLs</label><br>
			<label><input type="checkbox" name="og_wp_options[hardening_disable_cron]" value="1" <?php checked($cron, '1'); ?>> Disable WP Cron via HTTP (requires server cron)</label>
		</div>
		<?php
	}
}
