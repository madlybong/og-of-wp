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
			if ( $key === 'email_routing_rules' && is_array( $value ) ) {
				$sanitized_rules = array();
				foreach ( $value as $rule ) {
					if ( ! is_array( $rule ) ) {
						continue;
					}
					$type     = sanitize_key( $rule['type'] ?? 'subject_contains' );
					$val      = sanitize_text_field( $rule['value'] ?? '' );
					$provider = sanitize_key( $rule['provider'] ?? 'smtp' );
					if ( ! empty( $val ) ) {
						$sanitized_rules[] = array(
							'type'     => $type,
							'value'    => $val,
							'provider' => $provider,
						);
					}
				}
				$sanitized_input['email_routing_rules'] = $sanitized_rules;
			} elseif ( is_array( $value ) ) {
				$sanitized_input[ sanitize_key( $key ) ] = array_map( 'sanitize_text_field', $value );
			} else {
				// We don't want to over-sanitize things like CSP which has semicolons, but sanitize_text_field strips some things.
				// For textarea fields we should use sanitize_textarea_field
				if ( strpos( $key, 'csp' ) !== false || strpos( $key, 'allowlist' ) !== false || strpos( $key, 'keywords' ) !== false ) {
					$sanitized_input[ sanitize_key( $key ) ] = sanitize_textarea_field( $value );
				} elseif ( strpos( $key, 'pass' ) !== false || strpos( $key, 'api_key' ) !== false || strpos( $key, 'token' ) !== false || strpos( $key, 'secret' ) !== false || strpos( $key, 'key' ) !== false ) {
					$sanitized_input[ sanitize_key( $key ) ] = trim( (string) $value );
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
		add_action( 'og_wp_module_settings_email', array( $this, 'render_email_settings' ) );

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

	public function render_email_settings( $options ) {
		$provider   = $options['email_provider'] ?? 'smtp';
		$fallback   = $options['email_fallback_provider'] ?? 'none';
		$from_email = $options['email_from_email'] ?? get_option( 'admin_email' );
		$force_from = ! empty( $options['email_force_from_email'] );
		$from_name  = $options['email_from_name'] ?? get_bloginfo( 'name' );
		$force_name = ! empty( $options['email_force_from_name'] );
		$async      = ! empty( $options['email_async_queue'] );
		$retention  = $options['email_log_retention'] ?? '30';

		// SMTP Settings
		$smtp_host = $options['smtp_host'] ?? '';
		$smtp_port = $options['smtp_port'] ?? '587';
		$smtp_enc  = $options['smtp_encryption'] ?? 'tls';
		$smtp_auth = isset( $options['smtp_auth'] ) ? $options['smtp_auth'] : '1';
		$smtp_user = $options['smtp_user'] ?? '';
		$smtp_pass = $options['smtp_pass'] ?? '';

		// API Settings
		$resend_key   = $options['resend_api_key'] ?? '';
		$sendgrid_key = $options['sendgrid_api_key'] ?? '';
		$mailgun_key  = $options['mailgun_api_key'] ?? '';
		$mailgun_dom  = $options['mailgun_domain'] ?? '';
		$mailgun_reg  = $options['mailgun_region'] ?? 'us';
		$postmark_tok = $options['postmark_token'] ?? '';
		$brevo_key    = $options['brevo_api_key'] ?? '';
		$ses_access   = $options['ses_access_key'] ?? '';
		$ses_secret   = $options['ses_secret_key'] ?? '';
		$ses_region   = $options['ses_region'] ?? 'us-east-1';
		?>
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px;">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Primary & Fallback Dispatchers</h4>
			<div class="og-wp-form-row">
				<label>Primary Email Provider</label>
				<select name="og_wp_options[email_provider]" id="og_wp_email_provider" onchange="ogWpToggleEmailProvider(this.value)">
					<option value="smtp" <?php selected( $provider, 'smtp' ); ?>>Custom SMTP (Dedicated Mail Server)</option>
					<option value="ses" <?php selected( $provider, 'ses' ); ?>>Amazon SES (REST API v2 - Enterprise)</option>
					<option value="resend" <?php selected( $provider, 'resend' ); ?>>Resend API (Ultra-Fast Modern REST)</option>
					<option value="sendgrid" <?php selected( $provider, 'sendgrid' ); ?>>SendGrid API</option>
					<option value="mailgun" <?php selected( $provider, 'mailgun' ); ?>>Mailgun API</option>
					<option value="postmark" <?php selected( $provider, 'postmark' ); ?>>Postmark API</option>
					<option value="brevo" <?php selected( $provider, 'brevo' ); ?>>Brevo (Sendinblue) API</option>
				</select>
				<span class="og-wp-form-help">Select the primary service to deliver all transactional and system emails.</span>
			</div>
			<div class="og-wp-form-row">
				<label>Automatic Failover Provider (Secondary)</label>
				<select name="og_wp_options[email_fallback_provider]">
					<option value="none" <?php selected( $fallback, 'none' ); ?>>None (Do not retry with secondary)</option>
					<option value="smtp" <?php selected( $fallback, 'smtp' ); ?>>Custom SMTP</option>
					<option value="ses" <?php selected( $fallback, 'ses' ); ?>>Amazon SES API</option>
					<option value="resend" <?php selected( $fallback, 'resend' ); ?>>Resend API</option>
					<option value="sendgrid" <?php selected( $fallback, 'sendgrid' ); ?>>SendGrid API</option>
					<option value="mailgun" <?php selected( $fallback, 'mailgun' ); ?>>Mailgun API</option>
					<option value="postmark" <?php selected( $fallback, 'postmark' ); ?>>Postmark API</option>
					<option value="brevo" <?php selected( $fallback, 'brevo' ); ?>>Brevo API</option>
				</select>
				<span class="og-wp-form-help">If the primary provider experiences connection dropouts or 5xx API limits, emails are instantly re-routed through this backup.</span>
			</div>
		</div>

		<!-- Conditional Multi-Routing Rules -->
		<div style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:10px;">
				<div>
					<h4 style="margin:0; font-size:15px; color:var(--og-wp-navy);">Smart Conditional Routing Rules (Multi-Mailer Matrix)</h4>
					<span class="og-wp-form-help" style="margin:0;">Route emails through specialized delivery providers based on subject, recipient domain, or originating source.</span>
				</div>
				<button type="button" class="button button-secondary" onclick="ogWpAddRoutingRule()">+ Add Routing Rule</button>
			</div>
			
			<table class="wp-list-table widefat fixed striped" id="og_wp_routing_rules_table" style="border:1px solid #cbd5e1; border-radius:4px; margin-top:10px;">
				<thead>
					<tr>
						<th style="width:200px;">Condition</th>
						<th>Match Keyword / Pattern</th>
						<th style="width:220px;">Target Mailer</th>
						<th style="width:60px; text-align:center;">Action</th>
					</tr>
				</thead>
				<tbody id="og_wp_routing_rules_body">
					<?php
					$rules = isset( $options['email_routing_rules'] ) && is_array( $options['email_routing_rules'] ) ? $options['email_routing_rules'] : array();
					if ( empty( $rules ) ) {
						echo '<tr id="og_wp_no_rules_row"><td colspan="4" style="text-align:center; color:#94a3b8; padding:15px;">No custom routing rules defined. All emails will route through the Primary Provider.</td></tr>';
					} else {
						foreach ( $rules as $idx => $rule ) {
							$rtype     = $rule['type'] ?? 'subject_contains';
							$rval      = $rule['value'] ?? '';
							$rprovider = $rule['provider'] ?? 'smtp';
							?>
							<tr>
								<td>
									<select name="og_wp_options[email_routing_rules][<?php echo $idx; ?>][type]" style="width:100%;">
										<option value="subject_contains" <?php selected( $rtype, 'subject_contains' ); ?>>Subject Contains</option>
										<option value="to_domain" <?php selected( $rtype, 'to_domain' ); ?>>Recipient Domain (e.g. @domain)</option>
										<option value="from_email" <?php selected( $rtype, 'from_email' ); ?>>From Email Contains</option>
										<option value="header_contains" <?php selected( $rtype, 'header_contains' ); ?>>Header Contains (e.g. WooCommerce)</option>
									</select>
								</td>
								<td>
									<input type="text" name="og_wp_options[email_routing_rules][<?php echo $idx; ?>][value]" value="<?php echo esc_attr( $rval ); ?>" placeholder="e.g. Order, Invoice, @corp.com" style="width:100%;">
								</td>
								<td>
									<select name="og_wp_options[email_routing_rules][<?php echo $idx; ?>][provider]" style="width:100%;">
										<option value="smtp" <?php selected( $rprovider, 'smtp' ); ?>>Custom SMTP</option>
										<option value="ses" <?php selected( $rprovider, 'ses' ); ?>>Amazon SES v2</option>
										<option value="resend" <?php selected( $rprovider, 'resend' ); ?>>Resend API</option>
										<option value="sendgrid" <?php selected( $rprovider, 'sendgrid' ); ?>>SendGrid API</option>
										<option value="mailgun" <?php selected( $rprovider, 'mailgun' ); ?>>Mailgun API</option>
										<option value="postmark" <?php selected( $rprovider, 'postmark' ); ?>>Postmark API</option>
										<option value="brevo" <?php selected( $rprovider, 'brevo' ); ?>>Brevo API</option>
									</select>
								</td>
								<td style="text-align:center;">
									<button type="button" class="button button-small button-link-delete" onclick="ogWpRemoveRoutingRule(this)" style="color:var(--og-wp-red); font-weight:bold; font-size:16px;">&times;</button>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>

		<!-- Sender Configuration -->
		<div style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px;">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Sender Identity & Anti-Spoofing</h4>
			<div class="og-wp-form-row">
				<label>From Email Address</label>
				<input type="text" name="og_wp_options[email_from_email]" id="og_wp_from_email" value="<?php echo esc_attr( $from_email ); ?>" placeholder="e.g. notifications@yourdomain.com">
				<label style="margin-top:5px; font-weight:normal;">
					<input type="checkbox" name="og_wp_options[email_force_from_email]" value="1" <?php checked( $force_from ); ?>>
					<strong>Force From Email</strong> (Prevents 3rd-party plugins from using unauthorized addresses that trigger DMARC drops)
				</label>
			</div>
			<div class="og-wp-form-row">
				<label>From Name</label>
				<input type="text" name="og_wp_options[email_from_name]" value="<?php echo esc_attr( $from_name ); ?>" placeholder="e.g. Astrake Sovereign Mail">
				<label style="margin-top:5px; font-weight:normal;">
					<input type="checkbox" name="og_wp_options[email_force_from_name]" value="1" <?php checked( $force_name ); ?>>
					<strong>Force From Name</strong> (Enforces consistent brand identity across all notifications)
				</label>
			</div>
		</div>

		<!-- Performance, Queue & Analytics -->
		<div style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px;">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">High-Performance Queue & Open Tracking</h4>
			<div class="og-wp-form-row">
				<label style="font-weight:normal;">
					<input type="checkbox" name="og_wp_options[email_async_queue]" value="1" <?php checked( $async ); ?>>
					<strong>Enable Non-Blocking Asynchronous Queue</strong>
				</label>
				<span class="og-wp-form-help">Offloads SMTP/API delivery to a background worker. Speeds up WooCommerce checkout, user registrations, and form submissions to 0ms email wait times!</span>
			</div>
			<div class="og-wp-form-row">
				<label style="font-weight:normal;">
					<input type="checkbox" name="og_wp_options[email_track_opens]" value="1" <?php checked( ! empty( $options['email_track_opens'] ) ); ?>>
					<strong>Enable Invisible Email Open Tracking</strong>
				</label>
				<span class="og-wp-form-help">Injects a lightweight 1x1 transparent tracking pixel into outgoing HTML emails to accurately track when recipients open emails in real-time.</span>
			</div>
			<div class="og-wp-form-row">
				<label>Provider Delivery Webhook Endpoint</label>
				<input type="text" readonly value="<?php echo esc_url( get_rest_url( null, 'og-wp/v1/email-webhook' ) ); ?>" onclick="this.select()" style="max-width:500px; background:#f1f5f9; font-family:monospace; font-size:12px;">
				<span class="og-wp-form-help">Copy and paste this webhook URL into your Resend, SendGrid, or Mailgun account to automatically receive delivery confirmations, open events, and bounce drops.</span>
			</div>
			<div class="og-wp-form-row">
				<label>Email Log Retention</label>
				<select name="og_wp_options[email_log_retention]">
					<option value="7" <?php selected( $retention, '7' ); ?>>7 Days</option>
					<option value="14" <?php selected( $retention, '14' ); ?>>14 Days</option>
					<option value="30" <?php selected( $retention, '30' ); ?>>30 Days (Recommended)</option>
					<option value="60" <?php selected( $retention, '60' ); ?>>60 Days</option>
					<option value="90" <?php selected( $retention, '90' ); ?>>90 Days</option>
				</select>
			</div>
		</div>

		<!-- SMTP Credentials Panel -->
		<div id="og_wp_panel_smtp" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'smtp') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">SMTP Server Configuration</h4>
			<div class="og-wp-form-row">
				<label>SMTP Host</label>
				<input type="text" name="og_wp_options[smtp_host]" value="<?php echo esc_attr( $smtp_host ); ?>" placeholder="e.g. smtp.gmail.com or mail.yourdomain.com">
			</div>
			<div style="display:flex; gap:20px;">
				<div class="og-wp-form-row" style="flex:1;">
					<label>SMTP Port</label>
					<input type="number" name="og_wp_options[smtp_port]" value="<?php echo esc_attr( $smtp_port ); ?>" placeholder="587">
				</div>
				<div class="og-wp-form-row" style="flex:1;">
					<label>Encryption</label>
					<select name="og_wp_options[smtp_encryption]">
						<option value="tls" <?php selected( $smtp_enc, 'tls' ); ?>>TLS / STARTTLS (Port 587 - Recommended)</option>
						<option value="ssl" <?php selected( $smtp_enc, 'ssl' ); ?>>SSL (Port 465)</option>
						<option value="none" <?php selected( $smtp_enc, 'none' ); ?>>None (Insecure / Local)</option>
					</select>
				</div>
			</div>
			<div class="og-wp-form-row">
				<label>
					<input type="checkbox" name="og_wp_options[smtp_auth]" value="1" <?php checked( $smtp_auth, '1' ); ?>>
					SMTP Authentication Required
				</label>
			</div>
			<div class="og-wp-form-row">
				<label>SMTP Username</label>
				<input type="text" name="og_wp_options[smtp_user]" value="<?php echo esc_attr( $smtp_user ); ?>">
			</div>
			<div class="og-wp-form-row">
				<label>SMTP Password</label>
				<input type="password" name="og_wp_options[smtp_pass]" value="<?php echo esc_attr( $smtp_pass ); ?>" style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
			</div>
		</div>

		<!-- Resend API Panel -->
		<div id="og_wp_panel_resend" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'resend') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Resend API Configuration</h4>
			<div class="og-wp-form-row">
				<label>Resend API Key</label>
				<input type="password" name="og_wp_options[resend_api_key]" value="<?php echo esc_attr( $resend_key ); ?>" placeholder="re_123456789..." style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
				<span class="og-wp-form-help">Obtain your API Key from your Resend Dashboard (https://resend.com/api-keys).</span>
			</div>
		</div>

		<!-- SendGrid API Panel -->
		<div id="og_wp_panel_sendgrid" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'sendgrid') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">SendGrid API Configuration</h4>
			<div class="og-wp-form-row">
				<label>SendGrid API Key</label>
				<input type="password" name="og_wp_options[sendgrid_api_key]" value="<?php echo esc_attr( $sendgrid_key ); ?>" placeholder="SG.123456789..." style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
				<span class="og-wp-form-help">Generate an API key with 'Mail Send' permissions in your SendGrid console.</span>
			</div>
		</div>

		<!-- Mailgun API Panel -->
		<div id="og_wp_panel_mailgun" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'mailgun') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Mailgun API Configuration</h4>
			<div class="og-wp-form-row">
				<label>Mailgun Private API Key</label>
				<input type="password" name="og_wp_options[mailgun_api_key]" value="<?php echo esc_attr( $mailgun_key ); ?>" placeholder="key-xxxxxxxx..." style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
			</div>
			<div class="og-wp-form-row">
				<label>Mailgun Domain Name</label>
				<input type="text" name="og_wp_options[mailgun_domain]" value="<?php echo esc_attr( $mailgun_dom ); ?>" placeholder="mg.yourdomain.com">
			</div>
			<div class="og-wp-form-row">
				<label>Mailgun Region</label>
				<select name="og_wp_options[mailgun_region]">
					<option value="us" <?php selected( $mailgun_reg, 'us' ); ?>>US (api.mailgun.net)</option>
					<option value="eu" <?php selected( $mailgun_reg, 'eu' ); ?>>EU (api.eu.mailgun.net)</option>
				</select>
			</div>
		</div>

		<!-- Postmark API Panel -->
		<div id="og_wp_panel_postmark" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'postmark') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Postmark API Configuration</h4>
			<div class="og-wp-form-row">
				<label>Server API Token</label>
				<input type="password" name="og_wp_options[postmark_token]" value="<?php echo esc_attr( $postmark_tok ); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
			</div>
		</div>

		<!-- Brevo API Panel -->
		<div id="og_wp_panel_brevo" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'brevo') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Brevo (Sendinblue) API Configuration</h4>
			<div class="og-wp-form-row">
				<label>Brevo API v3 Key</label>
				<input type="password" name="og_wp_options[brevo_api_key]" value="<?php echo esc_attr( $brevo_key ); ?>" placeholder="xkeysib-..." style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
		</div>

		<!-- Amazon SES API Panel -->
		<div id="og_wp_panel_ses" class="og-wp-provider-panel" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px; <?php if($provider !== 'ses') echo 'display:none;'; ?>">
			<h4 style="margin:0 0 10px 0; font-size:15px; color:var(--og-wp-navy);">Amazon SES (Simple Email Service) v2 Configuration</h4>
			<div class="og-wp-form-row">
				<label>AWS Access Key ID</label>
				<input type="text" name="og_wp_options[ses_access_key]" value="<?php echo esc_attr( $ses_access ); ?>" placeholder="AKIA..." style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
				<span class="og-wp-form-help">IAM user Access Key with <code>ses:SendEmail</code> and <code>ses:SendRawEmail</code> permissions.</span>
			</div>
			<div class="og-wp-form-row">
				<label>AWS Secret Access Key</label>
				<input type="password" name="og_wp_options[ses_secret_key]" value="<?php echo esc_attr( $ses_secret ); ?>" placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" style="width:100%; max-width:400px; padding:6px 10px; border:1px solid #8c8f94; border-radius:4px;">
			</div>
			<div class="og-wp-form-row">
				<label>AWS Region</label>
				<select name="og_wp_options[ses_region]">
					<option value="us-east-1" <?php selected( $ses_region, 'us-east-1' ); ?>>US East (N. Virginia - us-east-1)</option>
					<option value="us-east-2" <?php selected( $ses_region, 'us-east-2' ); ?>>US East (Ohio - us-east-2)</option>
					<option value="us-west-2" <?php selected( $ses_region, 'us-west-2' ); ?>>US West (Oregon - us-west-2)</option>
					<option value="eu-west-1" <?php selected( $ses_region, 'eu-west-1' ); ?>>EU (Ireland - eu-west-1)</option>
					<option value="eu-central-1" <?php selected( $ses_region, 'eu-central-1' ); ?>>EU (Frankfurt - eu-central-1)</option>
					<option value="ap-south-1" <?php selected( $ses_region, 'ap-south-1' ); ?>>Asia Pacific (Mumbai - ap-south-1)</option>
					<option value="ap-southeast-1" <?php selected( $ses_region, 'ap-southeast-1' ); ?>>Asia Pacific (Singapore - ap-southeast-1)</option>
					<option value="ap-northeast-1" <?php selected( $ses_region, 'ap-northeast-1' ); ?>>Asia Pacific (Tokyo - ap-northeast-1)</option>
				</select>
				<span class="og-wp-form-help">Must match the AWS Region where your sending identity or domain is verified.</span>
			</div>
		</div>

		<script>
		function ogWpToggleEmailProvider(selected) {
			var panels = document.querySelectorAll('.og-wp-provider-panel');
			panels.forEach(function(p) { p.style.display = 'none'; });
			var activePanel = document.getElementById('og_wp_panel_' + selected);
			if (activePanel) {
				activePanel.style.display = 'block';
			}
		}

		var ogWpRuleIndex = <?php echo isset( $rules ) && is_array( $rules ) ? count( $rules ) : 0; ?>;
		function ogWpAddRoutingRule() {
			var noRow = document.getElementById("og_wp_no_rules_row");
			if (noRow) noRow.remove();

			var tbody = document.getElementById("og_wp_routing_rules_body");
			var tr = document.createElement("tr");
			tr.innerHTML = '<td>' +
				'<select name="og_wp_options[email_routing_rules][' + ogWpRuleIndex + '][type]" style="width:100%;">' +
					'<option value="subject_contains">Subject Contains</option>' +
					'<option value="to_domain">Recipient Domain (e.g. @domain)</option>' +
					'<option value="from_email">From Email Contains</option>' +
					'<option value="header_contains">Header Contains (e.g. WooCommerce)</option>' +
				'</select>' +
			'</td>' +
			'<td>' +
				'<input type="text" name="og_wp_options[email_routing_rules][' + ogWpRuleIndex + '][value]" placeholder="e.g. Order, Invoice, @corp.com" style="width:100%;">' +
			'</td>' +
			'<td>' +
				'<select name="og_wp_options[email_routing_rules][' + ogWpRuleIndex + '][provider]" style="width:100%;">' +
					'<option value="smtp">Custom SMTP</option>' +
					'<option value="ses">Amazon SES v2</option>' +
					'<option value="resend">Resend API</option>' +
					'<option value="sendgrid">SendGrid API</option>' +
					'<option value="mailgun">Mailgun API</option>' +
					'<option value="postmark">Postmark API</option>' +
					'<option value="brevo">Brevo API</option>' +
				'</select>' +
			'</td>' +
			'<td style="text-align:center;">' +
				'<button type="button" class="button button-small button-link-delete" onclick="ogWpRemoveRoutingRule(this)" style="color:var(--og-wp-red); font-weight:bold; font-size:16px;">&times;</button>' +
			'</td>';
			tbody.appendChild(tr);
			ogWpRuleIndex++;
		}

		function ogWpRemoveRoutingRule(btn) {
			var tr = btn.closest("tr");
			if (tr) tr.remove();
			var tbody = document.getElementById("og_wp_routing_rules_body");
			if (tbody && tbody.children.length === 0) {
				tbody.innerHTML = '<tr id="og_wp_no_rules_row"><td colspan="4" style="text-align:center; color:#94a3b8; padding:15px;">No custom routing rules defined. All emails will route through the Primary Provider.</td></tr>';
			}
		}
		</script>
		<?php
	}
}
