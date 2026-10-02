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
</script>
