<div class="wrap og-wp-wrap">
	<h1 style="display:none;">OG of WP</h1>
	
	<?php settings_errors(); ?>

	<?php
	$options = get_option( 'og_wp_options', array() );
	$modules = [
		'auth'      => 'Authentication & Login Hardening',
		'waf'       => 'Firewall (WAF)',
		'files'     => 'File & Filesystem Security',
		'scanner'   => 'Malware & Vulnerability Scanner',
		'spam'      => 'Spam Protection',
		'headers'   => 'HTTP Security Headers',
		'audit'     => 'Activity Log & Audit Trail',
		'ssl'       => 'SSL / HTTPS Enforcement',
		'db'        => 'Database Security',
		'user'      => 'User & Role Security',
		'hardening' => 'WordPress Hardening',
	];

	$enabled_count = 0;
	foreach ( $modules as $key => $label ) {
		if ( ! empty( $options["enable_module_{$key}"] ) ) {
			$enabled_count++;
		}
	}
	$score = round( ( $enabled_count / count( $modules ) ) * 100 );
	$score_color = $score < 50 ? 'var(--og-wp-red)' : ( $score < 80 ? '#f0b849' : 'var(--og-wp-green)' );
	$stroke_dasharray = ( $score / 100 ) * 283; // 283 is approx circumference of r=45

	$threats_blocked = '--';
	if ( ! empty( $options['enable_module_audit'] ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'og_wp_audit_log';
		if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name ) {
			$threats_blocked = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE action = %s AND time > DATE_SUB(NOW(), INTERVAL 24 HOUR)", 'waf_block' ) );
		}
	}
	?>

	<div class="og-wp-nav-tabs">
		<a href="#dashboard" class="og-wp-nav-tab active" onclick="switchTab(event, 'dashboard')">Dashboard</a>
		<a href="#modules" class="og-wp-nav-tab" onclick="switchTab(event, 'modules')">Security</a>
		<a href="#utilities" class="og-wp-nav-tab" onclick="switchTab(event, 'utilities')">Utilities</a>
		<a href="#email" class="og-wp-nav-tab" onclick="switchTab(event, 'email')">Email & Deliverability</a>
		<a href="#branding" class="og-wp-nav-tab" onclick="switchTab(event, 'branding')">Branding</a>
		<a href="#logs" class="og-wp-nav-tab" onclick="switchTab(event, 'logs')">Audit Log</a>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'og_wp_option_group' ); ?>
		
		<div id="tab-dashboard" class="og-wp-tab-content active">
			<div class="og-wp-dashboard-grid">
				<div class="og-wp-stat-card" style="display:flex; flex-direction:column; align-items:center;">
					<h3>Security Score</h3>
					<div style="position:relative; width:120px; height:120px; margin: 10px 0;">
						<svg width="120" height="120" viewBox="0 0 100 100">
							<circle cx="50" cy="50" r="45" fill="none" stroke="#e2e8f0" stroke-width="10" />
							<circle cx="50" cy="50" r="45" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="10" stroke-dasharray="<?php echo $stroke_dasharray; ?>, 283" stroke-linecap="round" transform="rotate(-90 50 50)" style="transition: stroke-dasharray 1s ease-out;" />
						</svg>
						<div style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:bold; color:var(--og-wp-navy);">
							<?php echo $score; ?>
						</div>
					</div>
					<p>out of 100</p>
				</div>
				<div class="og-wp-stat-card">
					<h3>Active Modules</h3>
					<div class="og-wp-stat-value" style="margin: 30px 0;">
						<?php echo $enabled_count; ?>
					</div>
					<p>out of <?php echo count( $modules ); ?></p>
				</div>
				<div class="og-wp-stat-card">
					<h3>Threats Blocked</h3>
					<div class="og-wp-stat-value" id="og_wp_threats_blocked" style="margin: 30px 0;">
						<?php echo esc_html( $threats_blocked ); ?>
					</div>
					<p>in last 24 hours</p>
				</div>
			</div>
			<div style="background: var(--og-wp-card); border: 1px solid var(--og-wp-border); border-radius: 6px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
				<h3 style="margin-top:0;">Quick Actions</h3>
				<?php if ( $score < 100 ) : ?>
					<div style="background: #fdf2f2; color: var(--og-wp-red); padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
						<strong>Attention:</strong> Some security modules are disabled. To achieve maximum security, enable all modules in the Modules tab.
					</div>
				<?php endif; ?>
				<button type="button" class="og-wp-btn-primary" id="og-wp-run-scan-btn" onclick="runMalwareScan()">Run Malware Scan Now</button>
				<span id="og-wp-scan-result" style="margin-left: 15px; font-weight: 600;"></span>
			</div>
		</div>

		<div id="tab-modules" class="og-wp-tab-content">
			<h2 style="margin-top:0;">Security Modules</h2>
			<p>Toggle modules to enable them. Expand the panel to configure advanced settings.</p>
			
			<div style="max-width: 800px; margin-top: 20px;">
				<?php 
				foreach ($modules as $key => $label) :
					$field_id = "enable_module_{$key}";
					$checked = isset($options[$field_id]) && $options[$field_id] == '1' ? 'checked' : '';
					$status_class = $checked ? 'status-active' : 'status-disabled';
					$status_text = $checked ? 'Active' : 'Disabled';
				?>
				<div class="og-wp-module-card">
					<div class="og-wp-module-header" onclick="toggleConfig(event, '<?php echo esc_attr($key); ?>')">
						<label class="og-wp-switch" onclick="event.stopPropagation()">
							<input type="checkbox" name="og_wp_options[<?php echo esc_attr($field_id); ?>]" value="1" <?php echo $checked; ?> onchange="updateStatus(this, '<?php echo esc_attr($key); ?>')" />
							<span class="og-wp-slider"></span>
						</label>
						<h3 class="og-wp-module-title"><?php echo esc_html($label); ?></h3>
						<span class="og-wp-module-status <?php echo $status_class; ?>" id="status-<?php echo esc_attr($key); ?>"><?php echo $status_text; ?></span>
					</div>
					
					<div class="og-wp-module-config" id="config-<?php echo esc_attr($key); ?>" <?php if($checked) echo 'style="display:block;"'; ?>>
						<?php do_action('og_wp_module_settings_' . $key, $options); ?>
						<?php if ( ! has_action('og_wp_module_settings_' . $key) ) : ?>
							<p><em>No advanced configuration available for this module.</em></p>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			
			<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--og-wp-border);">
				<button type="submit" class="og-wp-btn-primary">Save Changes</button>
			</div>
		</div>
	<div id="tab-utilities" class="og-wp-tab-content">
		<h2 style="margin-top:0;">Utilities</h2>
		<p>Enable and configure multipurpose utility modules.</p>
		
		<div style="max-width: 800px; margin-top: 20px;">
			<?php 
			$utility_modules = [
				'duplicator' => 'Post/Page Duplicator',
				'porter' => 'Content Porter (Export/Import)',
				'media_cleaner' => 'Media Gallery Cleaner',
			];
			foreach ($utility_modules as $key => $label) :
				$field_id = "enable_module_{$key}";
				$checked = isset($options[$field_id]) && $options[$field_id] == '1' ? 'checked' : '';
				$status_class = $checked ? 'status-active' : 'status-disabled';
				$status_text = $checked ? 'Active' : 'Disabled';
			?>
				<div class="og-wp-module-card">
					<div class="og-wp-module-header" onclick="toggleConfig(event, '<?php echo esc_attr($key); ?>')">
						<label class="og-wp-switch" onclick="event.stopPropagation()">
							<input type="checkbox" name="og_wp_options[<?php echo esc_attr($field_id); ?>]" value="1" <?php echo $checked; ?> onchange="updateStatus(this, '<?php echo esc_attr($key); ?>')" />
							<span class="og-wp-slider"></span>
						</label>
						<h3 class="og-wp-module-title"><?php echo esc_html($label); ?></h3>
						<span class="og-wp-module-status <?php echo $status_class; ?>" id="status-<?php echo esc_attr($key); ?>"><?php echo $status_text; ?></span>
					</div>
					
					<div class="og-wp-module-config" id="config-<?php echo esc_attr($key); ?>" <?php if($checked) echo 'style="display:block;"'; ?>>
						<?php do_action('og_wp_module_settings_' . $key, $options); ?>
						<?php if ( ! has_action('og_wp_module_settings_' . $key) ) : ?>
							<p><em>No advanced configuration available for this module.</em></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--og-wp-border);">
				<button type="submit" class="og-wp-btn-primary">Save Changes</button>
			</div>
		</div>
	</div>

	<div id="tab-email" class="og-wp-tab-content">
		<?php
		global $wpdb;
		$email_table = $wpdb->prefix . 'og_wp_email_logs';
		$total_sent = 0;
		$total_failed = 0;
		$total_queued = 0;
		$raw_provider = $options['email_provider'] ?? 'smtp';
		$provider_names = array(
			'smtp'     => 'SMTP',
			'ses'      => 'Amazon SES v2',
			'resend'   => 'Resend API',
			'sendgrid' => 'SendGrid API',
			'mailgun'  => 'Mailgun API',
			'postmark' => 'Postmark API',
			'brevo'    => 'Brevo API',
		);
		$active_mailer = $provider_names[ $raw_provider ] ?? strtoupper( $raw_provider );

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$email_table'" ) === $email_table ) {
			$total_sent   = $wpdb->get_var( "SELECT COUNT(*) FROM $email_table WHERE status = 'sent'" ) ?: 0;
			$total_failed = $wpdb->get_var( "SELECT COUNT(*) FROM $email_table WHERE status = 'failed'" ) ?: 0;
			$total_queued = $wpdb->get_var( "SELECT COUNT(*) FROM $email_table WHERE status = 'queued'" ) ?: 0;
		}

		$email_enabled = ! empty( $options['enable_module_email'] );
		$email_status_class = $email_enabled ? 'status-active' : 'status-disabled';
		$email_status_text  = $email_enabled ? 'Active' : 'Disabled';
		?>
		
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
			<div>
				<h2 style="margin:0 0 5px 0;">Email & Deliverability Suite</h2>
				<p style="margin:0; color:#646970;">Manage multi-provider routing, zero-blocking async queues, live SMTP diagnostics, and DNS health.</p>
			</div>
			<div style="display:flex; align-items:center; gap:12px; background:#fff; padding:8px 15px; border-radius:6px; border:1px solid var(--og-wp-border);">
				<span style="font-weight:600; font-size:14px; color:var(--og-wp-text);">Engine Status:</span>
				<label class="og-wp-switch">
					<input type="checkbox" name="og_wp_options[enable_module_email]" value="1" <?php checked( $email_enabled ); ?> onchange="updateStatus(this, 'email_top')" />
					<span class="og-wp-slider"></span>
				</label>
				<span class="og-wp-module-status <?php echo $email_status_class; ?>" id="status-email_top"><?php echo $email_status_text; ?></span>
			</div>
		</div>

		<!-- Email Metric Cards -->
		<div class="og-wp-dashboard-grid" style="margin-bottom:25px;">
			<div class="og-wp-stat-card">
				<h3>Primary Engine</h3>
				<div class="og-wp-stat-value" style="font-size:24px; margin:20px 0; color:var(--og-wp-gold);">
					<?php echo esc_html( $active_mailer ); ?>
				</div>
				<p>Active Provider</p>
			</div>
			<div class="og-wp-stat-card">
				<h3>Delivered Emails</h3>
				<div class="og-wp-stat-value" style="color:var(--og-wp-green); font-size:32px; margin:15px 0;">
					<?php echo number_format_i18n( $total_sent ); ?>
				</div>
				<p>Successfully Handed Off</p>
			</div>
			<div class="og-wp-stat-card">
				<h3>Failed Deliveries</h3>
				<div class="og-wp-stat-value" style="color:var(--og-wp-red); font-size:32px; margin:15px 0;">
					<?php echo number_format_i18n( $total_failed ); ?>
				</div>
				<p>Connection/Auth Drops</p>
			</div>
			<div class="og-wp-stat-card">
				<h3>Async Queue</h3>
				<div class="og-wp-stat-value" style="color:var(--og-wp-navy); font-size:32px; margin:15px 0;">
					<span id="og_wp_stat_queued_count"><?php echo number_format_i18n( $total_queued ); ?></span>
				</div>
				<div style="display:flex; justify-content:space-between; align-items:center;">
					<p style="margin:0;">Pending Worker Dispatch</p>
					<button type="button" class="button button-small" id="og_wp_flush_queue_btn" onclick="flushEmailQueue()" style="font-size:11px;">⚡ Flush Now</button>
				</div>
			</div>
		</div>

		<?php
		// Calculate Deliverability Health Score (0 - 100)
		$score = 0;
		$score_items = array();

		$from_email_cfg = $options['email_from_email'] ?? get_option( 'admin_email' );
		$score_domain = '';
		if ( strpos( $from_email_cfg, '@' ) !== false ) {
			$score_domain = substr( strrchr( $from_email_cfg, '@' ), 1 );
		}

		$has_spf = false;
		$has_dmarc = false;
		$has_mx = false;
		$is_freemail = false;

		if ( ! empty( $score_domain ) ) {
			$free_domains = array( 'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com' );
			$is_freemail = in_array( strtolower( $score_domain ), $free_domains, true );

			if ( function_exists( 'dns_get_record' ) ) {
				$dns_cache_key = 'og_wp_dns_score_' . md5( $score_domain );
				$dns_cached = get_transient( $dns_cache_key );
				if ( false === $dns_cached ) {
					$txts = @dns_get_record( $score_domain, DNS_TXT ) ?: array();
					$dmarc_txts = @dns_get_record( '_dmarc.' . $score_domain, DNS_TXT ) ?: array();
					$mxs = @dns_get_record( $score_domain, DNS_MX ) ?: array();

					$spf_found = false;
					foreach ( $txts as $t ) {
						$e = $t['txt'] ?? ( $t['entries'][0] ?? '' );
						if ( strpos( $e, 'v=spf1' ) === 0 ) {
							$spf_found = true;
							break;
						}
					}

					$dmarc_found = false;
					foreach ( $dmarc_txts as $t ) {
						$e = $t['txt'] ?? ( $t['entries'][0] ?? '' );
						if ( strpos( $e, 'v=DMARC1' ) === 0 ) {
							$dmarc_found = true;
							break;
						}
					}

					$dns_cached = array(
						'spf'   => $spf_found,
						'dmarc' => $dmarc_found,
						'mx'    => ! empty( $mxs ),
					);
					set_transient( $dns_cache_key, $dns_cached, 6 * HOUR_IN_SECONDS );
				}

				$has_spf   = ! empty( $dns_cached['spf'] );
				$has_dmarc = ! empty( $dns_cached['dmarc'] );
				$has_mx    = ! empty( $dns_cached['mx'] );
			}
		}

		if ( $has_spf ) {
			$score += 25;
			$score_items[] = array( 'label' => 'SPF Record (v=spf1)', 'status' => 'pass', 'desc' => 'Valid SPF record detected.' );
		} else {
			$score_items[] = array( 'label' => 'SPF Record (v=spf1)', 'status' => 'fail', 'desc' => 'Missing SPF record on sending domain.' );
		}

		if ( $has_dmarc ) {
			$score += 25;
			$score_items[] = array( 'label' => 'DMARC Policy (v=DMARC1)', 'status' => 'pass', 'desc' => 'Valid DMARC policy active.' );
		} else {
			$score_items[] = array( 'label' => 'DMARC Policy (v=DMARC1)', 'status' => 'fail', 'desc' => 'Missing DMARC policy at _dmarc.' . ( $score_domain ?: 'domain' ) );
		}

		if ( $has_mx ) {
			$score += 15;
			$score_items[] = array( 'label' => 'MX Routing (Mail Exchange)', 'status' => 'pass', 'desc' => 'Valid incoming mail exchangers configured.' );
		} else {
			$score_items[] = array( 'label' => 'MX Routing (Mail Exchange)', 'status' => 'warn', 'desc' => 'No MX records found.' );
		}

		if ( ! $is_freemail && ! empty( $score_domain ) ) {
			$score += 15;
			$score_items[] = array( 'label' => 'Domain Reputation Alignment', 'status' => 'pass', 'desc' => 'Using custom business domain (' . esc_html( $score_domain ) . ').' );
		} else {
			$score_items[] = array( 'label' => 'Domain Reputation Alignment', 'status' => 'fail', 'desc' => 'Free webmail addresses violate DMARC when sent from servers.' );
		}

		if ( ! empty( $options['email_async_queue'] ) ) {
			$score += 10;
			$score_items[] = array( 'label' => 'Async Non-Blocking Queue', 'status' => 'pass', 'desc' => '0ms checkout/user wait times enabled.' );
		} else {
			$score_items[] = array( 'label' => 'Async Non-Blocking Queue', 'status' => 'warn', 'desc' => 'Synchronous SMTP handshakes block page requests.' );
		}

		$fallback_prov = $options['email_fallback_provider'] ?? 'none';
		if ( ! empty( $fallback_prov ) && $fallback_prov !== 'none' ) {
			$score += 10;
			$score_items[] = array( 'label' => 'High-Availability Failover', 'status' => 'pass', 'desc' => 'Secondary redundancy active (' . strtoupper( $fallback_prov ) . ').' );
		} else {
			$score_items[] = array( 'label' => 'High-Availability Failover', 'status' => 'warn', 'desc' => 'No backup mailer configured for automatic failover.' );
		}

		$score_badge_color = 'var(--og-wp-green)';
		$score_text = 'Optimal Deliverability';
		if ( $score < 60 ) {
			$score_badge_color = 'var(--og-wp-red)';
			$score_text = 'Action Required (At Risk of Spam / Drops)';
		} elseif ( $score < 85 ) {
			$score_badge_color = 'var(--og-wp-gold)';
			$score_text = 'Good (Deliverability Can Be Hardened)';
		}
		?>

		<!-- Deliverability Health Scorecard -->
		<div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:20px; margin-bottom:25px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px; margin-bottom:15px;">
				<div style="display:flex; align-items:center; gap:15px;">
					<div style="background:<?php echo $score_badge_color; ?>; color:#fff; border-radius:50%; width:54px; height:54px; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
						<?php echo $score; ?>
					</div>
					<div>
						<h3 style="margin:0; font-size:16px; color:var(--og-wp-navy);">Deliverability Health Score: <span style="color:<?php echo $score_badge_color; ?>;"><?php echo $score; ?> / 100</span></h3>
						<p style="margin:3px 0 0 0; color:#646970; font-size:13px;"><?php echo esc_html( $score_text ); ?></p>
					</div>
				</div>
				<div>
					<a href="#config-email_dns" onclick="document.getElementById('config-email_dns').scrollIntoView({behavior:'smooth'})" class="button button-secondary" style="font-size:12px;">Inspect DNS Records &rarr;</a>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
				<?php foreach ( $score_items as $item ) : 
					$icon = '✅';
					$bg = '#f0fdf4';
					$border = '#bbf7d0';
					$text_col = '#166534';
					if ( $item['status'] === 'fail' ) {
						$icon = '❌';
						$bg = '#fef2f2';
						$border = '#fecaca';
						$text_col = '#991b1b';
					} elseif ( $item['status'] === 'warn' ) {
						$icon = '⚠️';
						$bg = '#fffbeb';
						$border = '#fef3c7';
						$text_col = '#92400e';
					}
				?>
					<div style="background:<?php echo $bg; ?>; border:1px solid <?php echo $border; ?>; border-radius:6px; padding:10px 12px; font-size:12px;">
						<div style="font-weight:600; color:<?php echo $text_col; ?>; margin-bottom:2px;">
							<?php echo $icon . ' ' . esc_html( $item['label'] ); ?>
						</div>
						<div style="color:#475569; font-size:11px; line-height:1.4;">
							<?php echo esc_html( $item['desc'] ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div style="max-width: 900px;">
			<!-- Sub Section 1: Dispatcher Settings -->
			<div class="og-wp-module-card">
				<div class="og-wp-module-header" onclick="toggleConfig(event, 'email_settings')">
					<h3 class="og-wp-module-title" style="margin-left:0;">⚙️ Dispatcher & Connection Configuration</h3>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="og-wp-module-config open" id="config-email_settings" style="display:block;">
					<?php do_action( 'og_wp_module_settings_email', $options ); ?>
					<div style="margin-top:20px; padding-top:15px; border-top:1px solid var(--og-wp-border);">
						<button type="submit" class="og-wp-btn-primary">Save Email Configuration</button>
					</div>
				</div>
			</div>

			<!-- Sub Section 2: Live Test Mailer & Diagnostics -->
			<div class="og-wp-module-card">
				<div class="og-wp-module-header" onclick="toggleConfig(event, 'email_test')">
					<h3 class="og-wp-module-title" style="margin-left:0;">🚀 Send Test Email & Live Diagnostics</h3>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="og-wp-module-config" id="config-email_test" style="display:block;">
					<p style="margin-top:0; color:#646970;">Send a real-time test payload to verify provider authentication, SSL handshakes, and SPF/DKIM delivery headers.</p>
					
					<div class="og-wp-form-row">
						<label>Recipient Email Address</label>
						<input type="text" id="og_wp_test_to_email" placeholder="you@domain.com" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" style="max-width:400px;">
					</div>
					<div style="margin-top:15px;">
						<button type="button" class="og-wp-btn-primary" id="og_wp_send_test_btn" onclick="sendTestEmail()">Send Test Email</button>
						<span id="og_wp_test_status" style="margin-left:15px; font-weight:600;"></span>
					</div>

					<div id="og_wp_test_transcript_wrap" style="display:none; margin-top:20px;">
						<h4 style="margin:0 0 8px 0; font-size:14px; color:var(--og-wp-navy);">Live Protocol Transcript & Debug Log:</h4>
						<pre class="og-wp-terminal" id="og_wp_test_transcript"></pre>
					</div>
				</div>
			</div>

			<!-- Sub Section 3: DNS & Deliverability Verifier -->
			<div class="og-wp-module-card">
				<div class="og-wp-module-header" onclick="toggleConfig(event, 'email_dns')">
					<h3 class="og-wp-module-title" style="margin-left:0;">🛡️ Domain Deliverability & DNS Health (SPF, DMARC, MX)</h3>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="og-wp-module-config" id="config-email_dns" style="display:block;">
					<p style="margin-top:0; color:#646970;">Verify whether your sending domain has proper DNS records configured to avoid Gmail & Yahoo spam filters and DMARC drops.</p>
					
					<?php
					$detected_domain = '';
					$from_em = $options['email_from_email'] ?? get_option( 'admin_email' );
					if ( strpos( $from_em, '@' ) !== false ) {
						$detected_domain = substr( strrchr( $from_em, '@' ), 1 );
					}
					?>
					<div class="og-wp-form-row">
						<label>Sending Domain</label>
						<div style="display:flex; gap:10px; max-width:500px;">
							<input type="text" id="og_wp_dns_domain" value="<?php echo esc_attr( $detected_domain ); ?>" placeholder="yourdomain.com">
							<button type="button" class="button button-secondary" id="og_wp_dns_check_btn" onclick="checkDomainDns()">Verify DNS</button>
						</div>
					</div>

					<div id="og_wp_dns_results_wrap" style="display:none; margin-top:15px;">
						<div id="og_wp_dns_cards" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:15px;"></div>
					</div>
				</div>
			</div>

			<!-- Sub Section 4: Live Email Logs & Audit Trail -->
			<div class="og-wp-module-card">
				<div class="og-wp-module-header" onclick="toggleConfig(event, 'email_logs_panel')">
					<h3 class="og-wp-module-title" style="margin-left:0;">📋 Email Logs & Resend Console</h3>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="og-wp-module-config" id="config-email_logs_panel" style="display:block;">
					<?php
					$search_term   = isset( $_GET['email_search'] ) ? sanitize_text_field( $_GET['email_search'] ) : '';
					$filter_status = isset( $_GET['email_status'] ) ? sanitize_text_field( $_GET['email_status'] ) : '';
					$paged         = isset( $_GET['email_paged'] ) ? max( 1, intval( $_GET['email_paged'] ) ) : 1;
					$per_page      = 15;
					$offset        = ( $paged - 1 ) * $per_page;

					$where_clauses = array( '1=1' );
					$where_args    = array();

					if ( ! empty( $search_term ) ) {
						$where_clauses[] = '(to_email LIKE %s OR subject LIKE %s)';
						$like_term = '%' . $wpdb->esc_like( $search_term ) . '%';
						$where_args[] = $like_term;
						$where_args[] = $like_term;
					}

					if ( ! empty( $filter_status ) && $filter_status !== 'all' ) {
						if ( $filter_status === 'opened' ) {
							$where_clauses[] = 'open_count > 0';
						} else {
							$where_clauses[] = 'status = %s';
							$where_args[] = $filter_status;
						}
					}

					$where_sql = implode( ' AND ', $where_clauses );

					$total_query = "SELECT COUNT(id) FROM $email_table WHERE $where_sql";
					$total_logs  = ! empty( $where_args ) ? $wpdb->get_var( $wpdb->prepare( $total_query, $where_args ) ) : $wpdb->get_var( $total_query );
					$total_pages = ceil( $total_logs / $per_page );

					$logs_query = "SELECT id, created_at, to_email, subject, status, provider, retry_count, open_count, opened_at, error_details FROM $email_table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
					$query_args = array_merge( $where_args, array( $per_page, $offset ) );
					$email_logs = $wpdb->get_results( $wpdb->prepare( $logs_query, $query_args ), ARRAY_A );
					?>

					<!-- Search, Filter & Bulk Action Toolbar -->
					<div style="background:#f8fafc; border:1px solid var(--og-wp-border); border-radius:6px; padding:12px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
						<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
							<input type="text" id="og_wp_log_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="Search recipient or subject..." style="max-width:220px; font-size:12px; padding:5px 8px;">
							<select id="og_wp_log_status_filter" style="font-size:12px; padding:5px 8px;">
								<option value="all" <?php selected( $filter_status, 'all' ); ?>>All Statuses</option>
								<option value="sent" <?php selected( $filter_status, 'sent' ); ?>>Sent / Delivered</option>
								<option value="opened" <?php selected( $filter_status, 'opened' ); ?>>Opened</option>
								<option value="failed" <?php selected( $filter_status, 'failed' ); ?>>Failed</option>
								<option value="queued" <?php selected( $filter_status, 'queued' ); ?>>Queued</option>
							</select>
							<button type="button" class="button button-secondary" onclick="filterEmailLogs()">Filter</button>
							<?php if ( ! empty( $search_term ) || ( ! empty( $filter_status ) && $filter_status !== 'all' ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=og-of-wp#email' ) ); ?>" class="button button-link" style="font-size:12px;">Reset</a>
							<?php endif; ?>
						</div>

						<div style="display:flex; gap:8px; align-items:center;">
							<select id="og_wp_bulk_action_select" style="font-size:12px; padding:5px 8px;">
								<option value="">Bulk Actions</option>
								<option value="resend">Re-dispatch Selected</option>
								<option value="delete">Delete Selected</option>
							</select>
							<button type="button" class="button button-secondary" onclick="applyEmailBulkAction()">Apply</button>
							<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=og_wp_export_email_logs' ) ); ?>" class="button button-secondary" style="font-size:12px;">Export CSV</a>
							<button type="button" class="button button-link-delete" onclick="clearEmailLogs()" style="color:var(--og-wp-red); font-size:12px;">Clear All</button>
						</div>
					</div>

					<?php if ( ! empty( $email_logs ) ) : ?>
						<table class="wp-list-table widefat fixed striped" style="border: 1px solid var(--og-wp-border); border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
							<thead><tr>
								<th style="width:30px; text-align:center;"><input type="checkbox" id="og_wp_select_all_logs" onchange="toggleSelectAllLogs(this)"></th>
								<th style="width:130px;">Date / Time</th>
								<th style="width:160px;">Recipient</th>
								<th>Subject</th>
								<th style="width:85px;">Delivery</th>
								<th style="width:105px;">Open Tracking</th>
								<th style="width:85px;">Engine</th>
								<th style="width:130px; text-align:right;">Actions</th>
							</tr></thead>
							<tbody>
							<?php foreach ( $email_logs as $elog ) : 
								$st = esc_attr( $elog['status'] );
								$badge_class = 'status-active';
								if ( $st === 'failed' ) {
									$badge_class = 'status-disabled';
								} elseif ( $st === 'queued' || $st === 'pending' || $st === 'retrying' ) {
									$badge_class = 'og-wp-badge-queued';
								}
								$open_count = intval( $elog['open_count'] ?? 0 );
							?>
								<tr>
									<td style="text-align:center;"><input type="checkbox" class="og-wp-email-checkbox" value="<?php echo intval( $elog['id'] ); ?>"></td>
									<td style="font-size:11px;"><?php echo esc_html( $elog['created_at'] ); ?></td>
									<td style="font-size:12px;" title="<?php echo esc_attr( $elog['to_email'] ); ?>">
										<strong><?php echo esc_html( wp_trim_words( $elog['to_email'], 3, '...' ) ); ?></strong>
									</td>
									<td>
										<strong><?php echo esc_html( $elog['subject'] ); ?></strong>
										<?php if ( ! empty( $elog['error_details'] ) ) : ?>
											<div style="font-size:11px; color:var(--og-wp-red); margin-top:2px;">
												⚠️ <?php echo esc_html( wp_trim_words( $elog['error_details'], 12, '...' ) ); ?>
											</div>
										<?php endif; ?>
									</td>
									<td><span class="og-wp-module-status <?php echo $badge_class; ?>"><?php echo esc_html( ucfirst( $st ) ); ?></span></td>
									<td>
										<?php if ( $open_count > 0 ) : ?>
											<span style="color:var(--og-wp-green); font-weight:600; font-size:12px;">👁️ <?php echo $open_count; ?> open<?php echo $open_count > 1 ? 's' : ''; ?></span>
											<div style="font-size:10px; color:#646970;"><?php echo esc_html( substr( $elog['opened_at'], 5, 11 ) ); ?></div>
										<?php else : ?>
											<span style="color:#94a3b8; font-size:11px;">Unopened</span>
										<?php endif; ?>
									</td>
									<td style="font-size:12px;">
										<?php echo esc_html( strtoupper( $elog['provider'] ) ); ?>
										<?php if ( $elog['retry_count'] > 0 ) : ?>
											<span style="font-size:10px; color:#646970;">(<?php echo intval( $elog['retry_count'] ); ?> retries)</span>
										<?php endif; ?>
									</td>
									<td style="text-align:right;">
										<button type="button" class="button button-small" onclick="viewEmail(<?php echo intval( $elog['id'] ); ?>)" style="margin-right:4px;">Preview</button>
										<button type="button" class="button button-small" onclick="resendEmail(<?php echo intval( $elog['id'] ); ?>)">Resend</button>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>

						<?php if ( $total_pages > 1 ) : ?>
							<div class="tablenav" style="margin-top:10px;">
								<div class="tablenav-pages">
									<span class="displaying-num"><?php echo $total_logs; ?> transmissions</span>
									<?php
									echo paginate_links( array(
										'base'      => add_query_arg( 'email_paged', '%#%' ) . '#email',
										'format'    => '',
										'prev_text' => '&laquo;',
										'next_text' => '&raquo;',
										'total'     => $total_pages,
										'current'   => $paged,
									) );
									?>
								</div>
							</div>
						<?php endif; ?>

					<?php else : ?>
						<div style="padding:30px; text-align:center; background:#fff; border:1px dashed #cbd5e1; border-radius:6px; color:#646970;">
							No email transmissions match your query. Outgoing emails and automated deliveries will be logged here.
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div id="tab-branding" class="og-wp-tab-content">
		<h2 style="margin-top:0;">Admin Branding</h2>
		<p>Customize the WordPress admin area to match your premium brand.</p>
		<div style="max-width: 800px; margin-top: 20px;">
				<div class="og-wp-module-card">
					<div class="og-wp-module-config" style="display:block; padding: 20px;">
						<?php do_action('og_wp_module_settings_branding', $options); ?>
					</div>
				</div>
				<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--og-wp-border);">
					<button type="submit" class="og-wp-btn-primary">Save Branding</button>
				</div>
		</div>
	</div>
	</form>

	<div id="tab-logs" class="og-wp-tab-content">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
			<h2 style="margin:0;">Audit Log</h2>
			<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=og_wp_export_logs' ) ); ?>" class="button button-secondary">Export to CSV</a>
		</div>
		<?php
		if ( ! empty( $options['enable_module_audit'] ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'og_wp_audit_log';
			if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name ) {
				
				$per_page = 20;
				$current_page = isset( $_GET['og_wp_paged'] ) ? max( 1, intval( $_GET['og_wp_paged'] ) ) : 1;
				$offset = ( $current_page - 1 ) * $per_page;
				
				$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
				$total_pages = ceil( $total_items / $per_page );

				$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
				
				if ( $logs ) {
					echo '<table class="wp-list-table widefat fixed striped" style="border: 1px solid var(--og-wp-border); border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
					echo '<thead><tr><th>Time</th><th>IP</th><th>User</th><th>Action</th><th>Details</th></tr></thead>';
					echo '<tbody>';
					foreach ( $logs as $log ) {
						$user_display = 'Guest / System';
						if ( $log->user_id > 0 ) {
							$user_info = get_userdata( $log->user_id );
							$user_display = $user_info ? esc_html( $user_info->user_login ) : 'ID: ' . esc_html( $log->user_id );
						}

						echo '<tr>';
						echo '<td>' . esc_html( $log->time ) . '</td>';
						echo '<td>' . esc_html( $log->ip_address ) . '</td>';
						echo '<td>' . $user_display . '</td>';
						echo '<td><strong>' . esc_html( $log->action ) . '</strong></td>';
						echo '<td>' . esc_html( $log->details ) . '</td>';
						echo '</tr>';
					}
					echo '</tbody></table>';

					// Pagination links
					if ( $total_pages > 1 ) {
						$page_links = paginate_links( array(
							'base' => add_query_arg( 'og_wp_paged', '%#%' ),
							'format' => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total' => $total_pages,
							'current' => $current_page
						) );

						if ( $page_links ) {
							echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
						}
					}
				} else {
					echo '<p>No logs found.</p>';
				}
			} else {
				echo '<p>Audit log table has not been created yet.</p>';
			}
		} else {
			echo '<p>Audit Log module is disabled. Please enable it to see activity.</p>';
		}
		?>
	</div>

	<!-- Email Preview Modal -->
	<div class="og-wp-modal-overlay" id="og_wp_email_modal">
		<div class="og-wp-modal-dialog" style="max-width:850px;">
			<div class="og-wp-modal-header">
				<h3 id="og_wp_modal_title">Email Transmission Details</h3>
				<button type="button" class="og-wp-modal-close" onclick="closeEmailModal()">&times;</button>
			</div>
			<div class="og-wp-modal-body">
				<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px; margin-bottom:15px; font-size:13px; line-height:1.6;">
					<div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
						<div><strong>To:</strong> <span id="og_wp_modal_to"></span></div>
						<div><strong>Engine:</strong> <span id="og_wp_modal_provider" style="font-weight:600; color:var(--og-wp-navy);"></span></div>
					</div>
					<div><strong>Subject:</strong> <span id="og_wp_modal_subject"></span></div>
					<div style="margin-top:4px; display:flex; gap:15px; flex-wrap:wrap; font-size:12px; color:#475569;">
						<span><strong>Queued/Sent:</strong> <span id="og_wp_modal_date"></span></span>
						<span><strong>Status:</strong> <span id="og_wp_modal_status"></span></span>
						<span><strong>Open Tracking:</strong> <span id="og_wp_modal_opens" style="font-weight:600;"></span></span>
						<span id="og_wp_modal_retries_wrap"><strong>Retries:</strong> <span id="og_wp_modal_retries">0</span></span>
					</div>
					<div id="og_wp_modal_error_wrap" style="display:none; color:var(--og-wp-red); margin-top:8px; background:#fef2f2; padding:6px 10px; border-radius:4px; font-size:12px;">
						<strong>Error Details:</strong> <span id="og_wp_modal_error"></span>
					</div>
				</div>

				<!-- Modal Tabs -->
				<div style="display:flex; gap:8px; border-bottom:1px solid var(--og-wp-border); margin-bottom:12px; padding-bottom:8px;">
					<button type="button" class="button button-small" id="og_wp_tabbtn_html" onclick="switchModalView('html')" style="font-weight:600;">Rendered HTML</button>
					<button type="button" class="button button-small" id="og_wp_tabbtn_text" onclick="switchModalView('text')">Raw Text</button>
					<button type="button" class="button button-small" id="og_wp_tabbtn_headers" onclick="switchModalView('headers')">Technical Headers</button>
				</div>

				<div id="og_wp_modal_view_html" style="border:1px solid #cbd5e1; border-radius:4px; overflow:hidden; min-height:260px; background:#fff;">
					<iframe id="og_wp_modal_iframe" style="width:100%; height:320px; border:none; display:block;"></iframe>
				</div>

				<div id="og_wp_modal_view_text" style="display:none;">
					<pre id="og_wp_modal_plain_text" class="og-wp-terminal" style="background:#f8fafc; color:#334155; border:1px solid #cbd5e1; max-height:320px;"></pre>
				</div>

				<div id="og_wp_modal_view_headers" style="display:none;">
					<pre id="og_wp_modal_headers" class="og-wp-terminal" style="max-height:320px; font-size:12px;"></pre>
				</div>
			</div>
		</div>
	</div>

</div>

<script>
function switchTab(evt, tabName) {
	evt.preventDefault();
	var i, tabcontent, navtabs;
	tabcontent = document.getElementsByClassName("og-wp-tab-content");
	for (i = 0; i < tabcontent.length; i++) {
		tabcontent[i].classList.remove("active");
	}
	navtabs = document.getElementsByClassName("og-wp-nav-tab");
	for (i = 0; i < navtabs.length; i++) {
		navtabs[i].classList.remove("active");
	}
	document.getElementById("tab-" + tabName).classList.add("active");
	evt.currentTarget.classList.add("active");
}

function toggleConfig(evt, key) {
	var configPanel = document.getElementById("config-" + key);
	if (configPanel.style.display === "block") {
		configPanel.style.display = "none";
	} else {
		configPanel.style.display = "block";
	}
}

function updateStatus(checkbox, key) {
	var statusBadge = document.getElementById("status-" + key);
	if (checkbox.checked) {
		statusBadge.textContent = "Active";
		statusBadge.className = "og-wp-module-status status-active";
		document.getElementById("config-" + key).style.display = "block";
	} else {
		statusBadge.textContent = "Disabled";
		statusBadge.className = "og-wp-module-status status-disabled";
	}
}

function runMalwareScan() {
	var btn = document.getElementById("og-wp-run-scan-btn");
	var resultSpan = document.getElementById("og-wp-scan-result");
	
	btn.disabled = true;
	btn.textContent = "Scanning...";
	resultSpan.textContent = "";

	var formData = new URLSearchParams();
	formData.append('action', 'og_wp_run_scan');
	// In WP, admin-ajax.php is at ajaxurl (but it might not be defined globally on all pages if not enqueued, so we'll use a relative path since this is an admin page)
	var ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

	fetch(ajaxUrl, {
		method: 'POST',
		body: formData,
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		}
	})
	.then(response => response.json())
	.then(data => {
		btn.disabled = false;
		btn.textContent = "Run Malware Scan Now";
		if ( data.success ) {
			resultSpan.style.color = "var(--og-wp-green)";
			resultSpan.textContent = data.data;
		} else {
			resultSpan.style.color = "var(--og-wp-red)";
			resultSpan.textContent = "Scan failed: " + (data.data || 'Unknown error');
		}
	})
	.catch(err => {
		btn.disabled = false;
		btn.textContent = "Run Malware Scan Now";
		resultSpan.style.color = "var(--og-wp-red)";
		resultSpan.textContent = "Request failed.";
	});
}

function sendTestEmail() {
	var btn = document.getElementById("og_wp_send_test_btn");
	var status = document.getElementById("og_wp_test_status");
	var transcriptWrap = document.getElementById("og_wp_test_transcript_wrap");
	var transcript = document.getElementById("og_wp_test_transcript");
	var toEmail = document.getElementById("og_wp_test_to_email").value.trim();

	if (!toEmail) {
		alert("Please enter a valid recipient email address.");
		return;
	}

	btn.disabled = true;
	btn.textContent = "Sending Test Payload...";
	status.textContent = "";
	status.style.color = "var(--og-wp-navy)";
	transcriptWrap.style.display = "none";
	transcript.textContent = "";

	var formData = new URLSearchParams();
	formData.append("action", "og_wp_send_test_email");
	formData.append("to_email", toEmail);

	fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
		method: "POST",
		body: formData,
		headers: { "Content-Type": "application/x-www-form-urlencoded" }
	})
	.then(res => res.json())
	.then(data => {
		btn.disabled = false;
		btn.textContent = "Send Test Email";
		if (data.success) {
			status.style.color = "var(--og-wp-green)";
			status.textContent = data.data.message;
		} else {
			status.style.color = "var(--og-wp-red)";
			status.textContent = (data.data && data.data.message) ? data.data.message : "Failed to deliver test email.";
		}
		if (data.data && data.data.transcript) {
			transcriptWrap.style.display = "block";
			transcript.textContent = data.data.transcript;
		}
	})
	.catch(err => {
		btn.disabled = false;
		btn.textContent = "Send Test Email";
		status.style.color = "var(--og-wp-red)";
		status.textContent = "Network error during test.";
	});
}

function checkDomainDns() {
	var btn = document.getElementById("og_wp_dns_check_btn");
	var domain = document.getElementById("og_wp_dns_domain").value.trim();
	var resultsWrap = document.getElementById("og_wp_dns_results_wrap");
	var cardsContainer = document.getElementById("og_wp_dns_cards");

	if (!domain) {
		alert("Please enter a domain name to inspect.");
		return;
	}

	btn.disabled = true;
	btn.textContent = "Querying DNS...";
	cardsContainer.innerHTML = "";
	resultsWrap.style.display = "none";

	var formData = new URLSearchParams();
	formData.append("action", "og_wp_check_domain_dns");
	formData.append("domain", domain);

	fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
		method: "POST",
		body: formData,
		headers: { "Content-Type": "application/x-www-form-urlencoded" }
	})
	.then(res => res.json())
	.then(data => {
		btn.disabled = false;
		btn.textContent = "Verify DNS";
		if (data.success) {
			resultsWrap.style.display = "block";
			var r = data.data;

			function getBadge(status) {
				if (status === "pass") return '<span class="og-wp-module-status status-active">Pass</span>';
				if (status === "warning") return '<span class="og-wp-module-status og-wp-badge-queued">Warning</span>';
				return '<span class="og-wp-module-status status-disabled">Missing</span>';
			}

			var mxHtml = '<div style="background:#fff; border:1px solid var(--og-wp-border); border-radius:6px; padding:15px;">' +
				'<div style="display:flex; justify-content:space-between; margin-bottom:8px;"><strong>MX Records</strong>' + getBadge(r.mx.status) + '</div>' +
				'<div style="font-size:12px; color:#646970;">' + (r.mx.records.length ? r.mx.records.join("<br>") : r.mx.details) + '</div></div>';

			var spfHtml = '<div style="background:#fff; border:1px solid var(--og-wp-border); border-radius:6px; padding:15px;">' +
				'<div style="display:flex; justify-content:space-between; margin-bottom:8px;"><strong>SPF Record (TXT)</strong>' + getBadge(r.spf.status) + '</div>' +
				'<div style="font-size:12px; color:#646970; word-break:break-all;">' + (r.spf.record ? '<code style="background:#f1f5f9; padding:2px 4px; border-radius:3px;">' + r.spf.record + '</code>' : r.spf.details) + '</div></div>';

			var dmarcHtml = '<div style="background:#fff; border:1px solid var(--og-wp-border); border-radius:6px; padding:15px;">' +
				'<div style="display:flex; justify-content:space-between; margin-bottom:8px;"><strong>DMARC Record</strong>' + getBadge(r.dmarc.status) + '</div>' +
				'<div style="font-size:12px; color:#646970; word-break:break-all;">' + (r.dmarc.record ? '<code style="background:#f1f5f9; padding:2px 4px; border-radius:3px;">' + r.dmarc.record + '</code>' : r.dmarc.details + '<br><small style="color:#d97706;">Recommendation: Add TXT record at _dmarc.' + r.domain + ' with value "v=DMARC1; p=none;"</small>') + '</div></div>';

			cardsContainer.innerHTML = mxHtml + spfHtml + dmarcHtml;
		} else {
			alert(data.data || "DNS query failed.");
		}
	})
	.catch(err => {
		btn.disabled = false;
		btn.textContent = "Verify DNS";
		alert("DNS query request failed.");
	});
}

function resendEmail(logId) {
	if (!confirm("Are you sure you want to re-dispatch this email?")) return;

	var formData = new URLSearchParams();
	formData.append("action", "og_wp_resend_email");
	formData.append("log_id", logId);

	fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
		method: "POST",
		body: formData,
		headers: { "Content-Type": "application/x-www-form-urlencoded" }
	})
	.then(res => res.json())
	.then(data => {
		if (data.success) {
			alert(data.data);
			window.location.reload();
		} else {
			alert("Error: " + (data.data || "Failed to resend."));
		}
	});
}

function switchModalView(view) {
	document.getElementById("og_wp_modal_view_html").style.display = (view === 'html') ? 'block' : 'none';
	document.getElementById("og_wp_modal_view_text").style.display = (view === 'text') ? 'block' : 'none';
	document.getElementById("og_wp_modal_view_headers").style.display = (view === 'headers') ? 'block' : 'none';

	document.getElementById("og_wp_tabbtn_html").style.fontWeight = (view === 'html') ? '600' : 'normal';
	document.getElementById("og_wp_tabbtn_text").style.fontWeight = (view === 'text') ? '600' : 'normal';
	document.getElementById("og_wp_tabbtn_headers").style.fontWeight = (view === 'headers') ? '600' : 'normal';
}

function viewEmail(logId) {
	var formData = new URLSearchParams();
	formData.append("action", "og_wp_view_email");
	formData.append("log_id", logId);

	fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
		method: "POST",
		body: formData,
		headers: { "Content-Type": "application/x-www-form-urlencoded" }
	})
	.then(res => res.json())
	.then(data => {
		if (data.success) {
			var d = data.data;
			document.getElementById("og_wp_modal_to").textContent = d.to;
			document.getElementById("og_wp_modal_subject").textContent = d.subject;
			document.getElementById("og_wp_modal_date").textContent = d.created_at;
			document.getElementById("og_wp_modal_status").textContent = d.status.toUpperCase();
			document.getElementById("og_wp_modal_provider").textContent = d.provider;
			document.getElementById("og_wp_modal_retries").textContent = d.retry_count || 0;

			var opensText = "Unopened";
			if (d.open_count > 0) {
				opensText = "👁️ " + d.open_count + " open(s) (First: " + d.opened_at + ")";
			}
			document.getElementById("og_wp_modal_opens").textContent = opensText;

			var errWrap = document.getElementById("og_wp_modal_error_wrap");
			if (d.error_details) {
				errWrap.style.display = "block";
				document.getElementById("og_wp_modal_error").textContent = d.error_details;
			} else {
				errWrap.style.display = "none";
			}

			// Render HTML in iframe
			var iframe = document.getElementById("og_wp_modal_iframe");
			iframe.srcdoc = d.message;

			// Plain text view
			document.getElementById("og_wp_modal_plain_text").textContent = d.plain_text || "No text content.";

			// Headers view
			var headersFormatted = "Headers:\n" + (d.headers || "Standard WordPress Headers") + "\n\nAttachments:\n" + (d.attachments || "None");
			document.getElementById("og_wp_modal_headers").textContent = headersFormatted;

			switchModalView('html');
			document.getElementById("og_wp_email_modal").style.display = "flex";
		} else {
			alert("Could not load email details.");
		}
	});
}

function closeEmailModal() {
	document.getElementById("og_wp_email_modal").style.display = "none";
}

function filterEmailLogs() {
	var search = document.getElementById("og_wp_log_search").value.trim();
	var status = document.getElementById("og_wp_log_status_filter").value;

	var url = new URL(window.location.href);
	if (search) {
		url.searchParams.set("email_search", search);
	} else {
		url.searchParams.delete("email_search");
	}

	if (status && status !== "all") {
		url.searchParams.set("email_status", status);
	} else {
		url.searchParams.delete("email_status");
	}

	url.searchParams.set("email_paged", "1");
	url.hash = "email";
	window.location.href = url.toString();
}

function toggleSelectAllLogs(master) {
	var checkboxes = document.querySelectorAll(".og-wp-email-checkbox");
	checkboxes.forEach(function(cb) {
		cb.checked = master.checked;
	});
}

function applyEmailBulkAction() {
	var action = document.getElementById("og_wp_bulk_action_select").value;
	if (!action) {
		alert("Please select a bulk action.");
		return;
	}

	var checkedBoxes = document.querySelectorAll(".og-wp-email-checkbox:checked");
	if (checkedBoxes.length === 0) {
		alert("Please select at least one email log row.");
		return;
	}

	var ids = [];
	checkedBoxes.forEach(function(cb) {
		ids.push(cb.value);
	});

	if (action === "delete") {
		if (!confirm("Are you sure you want to delete " + ids.length + " selected log(s)?")) return;

		var formData = new URLSearchParams();
		formData.append("action", "og_wp_bulk_delete_email_logs");
		ids.forEach(function(id) { formData.append("ids[]", id); });

		fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
			method: "POST",
			body: formData,
			headers: { "Content-Type": "application/x-www-form-urlencoded" }
		})
		.then(res => res.json())
		.then(data => {
			if (data.success) {
				alert(data.data);
				window.location.reload();
			} else {
				alert("Error: " + data.data);
			}
		});
	} else if (action === "resend") {
		if (!confirm("Are you sure you want to re-dispatch " + ids.length + " selected email(s)?")) return;

		var formData = new URLSearchParams();
		formData.append("action", "og_wp_bulk_resend_email_logs");
		ids.forEach(function(id) { formData.append("ids[]", id); });

		fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
			method: "POST",
			body: formData,
			headers: { "Content-Type": "application/x-www-form-urlencoded" }
		})
		.then(res => res.json())
		.then(data => {
			if (data.success) {
				alert(data.data);
				window.location.reload();
			} else {
				alert("Error: " + data.data);
			}
		});
	}
}

function clearEmailLogs() {
	if (!confirm("Are you sure you want to permanently clear all email transmission logs?")) return;

	var formData = new URLSearchParams();
	formData.append("action", "og_wp_clear_email_logs");

	fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
		method: "POST",
		body: formData,
		headers: { "Content-Type": "application/x-www-form-urlencoded" }
	})
	.then(res => res.json())
	.then(data => {
		if (data.success) {
			alert(data.data);
			window.location.reload();
		} else {
			alert("Error: " + data.data);
		}
	});
}

function flushEmailQueue() {
	var btn = document.getElementById("og_wp_flush_queue_btn");
	if (btn) {
		btn.disabled = true;
		btn.textContent = "Flushing...";
	}

	var formData = new URLSearchParams();
	formData.append("action", "og_wp_flush_email_queue");

	fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
		method: "POST",
		body: formData,
		headers: { "Content-Type": "application/x-www-form-urlencoded" }
	})
	.then(res => res.json())
	.then(data => {
		if (btn) {
			btn.disabled = false;
			btn.textContent = "⚡ Flush Now";
		}
		if (data.success) {
			alert(data.data.message);
			window.location.reload();
		} else {
			alert("Error: " + (data.data || "Could not flush queue"));
		}
	})
	.catch(err => {
		if (btn) {
			btn.disabled = false;
			btn.textContent = "⚡ Flush Now";
		}
		alert("Network error flushing queue: " + err);
	});
}

// Preserve active tab from location hash on load
document.addEventListener("DOMContentLoaded", function() {
	if (window.location.hash) {
		var tab = window.location.hash.replace("#", "");
		var tabContent = document.getElementById("tab-" + tab);
		if (tabContent) {
			var tabLink = document.querySelector('a.og-wp-nav-tab[href="#' + tab + '"]');
			if (tabLink) {
				switchTab({ preventDefault: function(){}, currentTarget: tabLink }, tab);
			}
		}
	}
});
</script>
