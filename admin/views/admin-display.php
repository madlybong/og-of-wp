<?php
/**
 * OG of WP - Standardized Vue 3 Single-Page Settings Application
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'og_wp_options', array() );

$security_modules = [
	'auth'      => [ 'title' => 'Authentication Hardening', 'desc' => 'Limit login attempts, disable XML-RPC, and enforce strong password policies.' ],
	'waf'       => [ 'title' => 'Web Application Firewall', 'desc' => 'Inspect requests for SQL injection, bad bots, and manage IP allow/block lists.' ],
	'files'     => [ 'title' => 'Filesystem Security', 'desc' => 'Disable file editor, block execution in upload folders, and restrict permissions.' ],
	'scanner'   => [ 'title' => 'Malware & File Scanner', 'desc' => 'Compare core checksums and scan plugin/theme files for malicious signatures.' ],
	'spam'      => [ 'title' => 'Spam & Form Protection', 'desc' => 'Block automated comment spam, disposable emails, and honeypot form abuse.' ],
	'headers'   => [ 'title' => 'HTTP Security Headers', 'desc' => 'Configure HSTS, Content-Security-Policy, X-Frame-Options, and Referrer-Policy.' ],
	'audit'     => [ 'title' => 'Audit & Activity Trail', 'desc' => 'Record administrative logins, failed attempts, and sensitive changes in local DB.' ],
	'ssl'       => [ 'title' => 'SSL / HTTPS Enforcement', 'desc' => 'Enforce site-wide SSL redirects, secure cookies, and mixed-content remediation.' ],
	'db'        => [ 'title' => 'Database Security', 'desc' => 'Audit table prefix vulnerabilities, optimize tables, and schedule repair tasks.' ],
	'user'      => [ 'title' => 'User & Role Security', 'desc' => 'Automate idle session logouts, restrict default usernames, and role governance.' ],
	'hardening' => [ 'title' => 'WordPress Hardening', 'desc' => 'Hide WP version fingerprints, disable pingbacks, and protect wp-config.php.' ],
];

$utility_modules = [
	'duplicator'    => [ 'title' => 'Post/Page Duplicator', 'desc' => 'One-click cloning of posts, pages, and custom post types with all metadata.' ],
	'porter'        => [ 'title' => 'Content Porter (JSON)', 'desc' => 'Export and import structured content, taxonomies, and terms across sites.' ],
	'media_cleaner' => [ 'title' => 'Media Gallery Cleaner', 'desc' => 'Identify orphaned, unattached, and unused image assets in your media library.' ],
	'cf7'           => [ 'title' => 'Contact Form 7 Integration', 'desc' => 'Native submission handler with zero-loss attachment queueing and local DB capture.' ],
];

// Pre-render module settings into buffers
$module_settings_html = [];
foreach ( array_merge( $security_modules, $utility_modules ) as $key => $info ) {
	ob_start();
	do_action( 'og_wp_module_settings_' . $key, $options );
	$module_settings_html[ $key ] = ob_get_clean();
}

ob_start();
do_action( 'og_wp_module_settings_email', $options );
$email_settings_html = ob_get_clean();

ob_start();
do_action( 'og_wp_module_settings_branding', $options );
$branding_settings_html = ob_get_clean();

// Count enabled modules
$enabled_security_count = 0;
foreach ( $security_modules as $key => $info ) {
	if ( ! empty( $options["enable_module_{$key}"] ) ) {
		$enabled_security_count++;
	}
}

// Threats blocked in 24h
$threats_blocked = 0;
global $wpdb;
$audit_table = $wpdb->prefix . 'og_wp_audit_log';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$audit_table'" ) === $audit_table ) {
	$threats_blocked = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $audit_table WHERE action = %s AND time > DATE_SUB(NOW(), INTERVAL 24 HOUR)", 'waf_block' ) );
}

// Email stats
$email_table = $wpdb->prefix . 'og_wp_email_logs';
$email_stats = [ 'sent' => 0, 'failed' => 0, 'queued' => 0 ];
if ( $wpdb->get_var( "SHOW TABLES LIKE '$email_table'" ) === $email_table ) {
	$email_stats['sent']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $email_table WHERE status = 'sent'" );
	$email_stats['failed'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $email_table WHERE status = 'failed'" );
	$email_stats['queued'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $email_table WHERE status = 'queued'" );
}

// Email deliverability score calculation
$score_items = [];
$from_email_cfg = $options['email_from_email'] ?? get_option( 'admin_email' );
$score_domain = '';
if ( strpos( $from_email_cfg, '@' ) !== false ) {
	$score_domain = substr( strrchr( $from_email_cfg, '@' ), 1 );
}
$d_score = 0;
$has_spf = false; $has_dmarc = false; $has_mx = false;
$is_freemail = in_array( strtolower( $score_domain ), ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com'], true );

if ( ! empty( $score_domain ) && function_exists( 'dns_get_record' ) ) {
	$dns_cached = get_transient( 'og_wp_dns_score_' . md5( $score_domain ) );
	if ( false === $dns_cached ) {
		$txts = @dns_get_record( $score_domain, DNS_TXT ) ?: [];
		$dmarc_txts = @dns_get_record( '_dmarc.' . $score_domain, DNS_TXT ) ?: [];
		$mxs = @dns_get_record( $score_domain, DNS_MX ) ?: [];
		$spf_found = false;
		foreach ( $txts as $t ) {
			$e = $t['txt'] ?? ( $t['entries'][0] ?? '' );
			if ( strpos( $e, 'v=spf1' ) === 0 ) { $spf_found = true; break; }
		}
		$dmarc_found = false;
		foreach ( $dmarc_txts as $t ) {
			$e = $t['txt'] ?? ( $t['entries'][0] ?? '' );
			if ( strpos( $e, 'v=DMARC1' ) === 0 ) { $dmarc_found = true; break; }
		}
		$dns_cached = [ 'spf' => $spf_found, 'dmarc' => $dmarc_found, 'mx' => ! empty( $mxs ) ];
		set_transient( 'og_wp_dns_score_' . md5( $score_domain ), $dns_cached, 6 * HOUR_IN_SECONDS );
	}
	$has_spf = ! empty( $dns_cached['spf'] );
	$has_dmarc = ! empty( $dns_cached['dmarc'] );
	$has_mx = ! empty( $dns_cached['mx'] );
}

if ( $has_spf ) { $d_score += 25; $score_items[] = [ 'label' => 'SPF Record (v=spf1)', 'status' => 'pass', 'desc' => 'Valid SPF authentication record detected.' ]; }
else { $score_items[] = [ 'label' => 'SPF Record (v=spf1)', 'status' => 'fail', 'desc' => 'Missing SPF record on sending domain.' ]; }

if ( $has_dmarc ) { $d_score += 25; $score_items[] = [ 'label' => 'DMARC Policy (v=DMARC1)', 'status' => 'pass', 'desc' => 'Valid DMARC domain policy active.' ]; }
else { $score_items[] = [ 'label' => 'DMARC Policy (v=DMARC1)', 'status' => 'fail', 'desc' => 'Missing DMARC policy at _dmarc.' . ( $score_domain ?: 'domain' ) ]; }

if ( $has_mx ) { $d_score += 15; $score_items[] = [ 'label' => 'MX Mail Exchange', 'status' => 'pass', 'desc' => 'Valid incoming mail exchangers configured.' ]; }
else { $score_items[] = [ 'label' => 'MX Mail Exchange', 'status' => 'warn', 'desc' => 'No MX mail records found.' ]; }

if ( ! $is_freemail && ! empty( $score_domain ) ) { $d_score += 15; $score_items[] = [ 'label' => 'Domain Reputation', 'status' => 'pass', 'desc' => 'Using custom domain (' . esc_html( $score_domain ) . ').' ]; }
else { $score_items[] = [ 'label' => 'Domain Reputation', 'status' => 'fail', 'desc' => 'Free webmail addresses violate DMARC when sent from servers.' ]; }

if ( ! empty( $options['email_async_queue'] ) ) { $d_score += 10; $score_items[] = [ 'label' => 'Async Email Queue', 'status' => 'pass', 'desc' => 'Zero-blocking background worker enabled.' ]; }
else { $score_items[] = [ 'label' => 'Async Email Queue', 'status' => 'warn', 'desc' => 'Synchronous SMTP connections block web requests.' ]; }

$fallback_prov = $options['email_fallback_provider'] ?? 'none';
if ( ! empty( $fallback_prov ) && $fallback_prov !== 'none' ) { $d_score += 10; $score_items[] = [ 'label' => 'High-Availability Failover', 'status' => 'pass', 'desc' => 'Secondary provider active (' . strtoupper( $fallback_prov ) . ').' ]; }
else { $score_items[] = [ 'label' => 'High-Availability Failover', 'status' => 'warn', 'desc' => 'No automatic failover provider configured.' ]; }

// Email logs
$email_logs = [];
if ( $wpdb->get_var( "SHOW TABLES LIKE '$email_table'" ) === $email_table ) {
	$email_logs = $wpdb->get_results( "SELECT id, created_at, to_email, subject, status, provider, retry_count, open_count, opened_at, error_details FROM $email_table ORDER BY created_at DESC LIMIT 20", ARRAY_A ) ?: [];
}

// Audit logs
$audit_logs = [];
if ( $wpdb->get_var( "SHOW TABLES LIKE '$audit_table'" ) === $audit_table ) {
	$raw_audit = $wpdb->get_results( "SELECT * FROM $audit_table ORDER BY time DESC LIMIT 25" ) ?: [];
	foreach ( $raw_audit as $log ) {
		$user_display = 'Guest / System';
		if ( $log->user_id > 0 ) {
			$u = get_userdata( $log->user_id );
			$user_display = $u ? $u->user_login : 'ID: ' . $log->user_id;
		}
		$audit_logs[] = [
			'id'      => $log->id,
			'time'    => $log->time,
			'ip'      => $log->ip_address,
			'user'    => $user_display,
			'action'  => $log->action,
			'details' => $log->details,
		];
	}
}

// Export CSV URLs
$export_logs_url = admin_url( 'admin-post.php?action=og_wp_export_logs' );
$export_email_logs_url = admin_url( 'admin-post.php?action=og_wp_export_email_logs' );
$provider_names = [
	'smtp'     => 'SMTP Server',
	'ses'      => 'Amazon SES v2',
	'resend'   => 'Resend API',
	'sendgrid' => 'SendGrid API',
	'mailgun'  => 'Mailgun API',
	'postmark' => 'Postmark API',
	'brevo'    => 'Brevo API',
];
$active_provider = $provider_names[ $options['email_provider'] ?? 'smtp' ] ?? 'SMTP Server';
?>

<div id="og-wp-app" class="antialiased font-sans text-slate-800" v-cloak>
	<!-- Floating Toast Notification -->
	<transition name="fade">
		<div v-if="toast.visible" 
			:class="toast.type === 'error' ? 'bg-rose-600 text-white' : 'bg-slate-900 text-white'"
			class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-xl text-sm font-medium border border-slate-700">
			<span>{{ toast.message }}</span>
			<button @click="toast.visible = false" class="text-slate-400 hover:text-white">&times;</button>
		</div>
	</transition>

	<!-- Main Shell Container (Sidebar + Content) -->
	<div class="flex flex-col lg:flex-row gap-5 items-start">
		
		<!-- Left Sidebar Navigation -->
		<aside class="w-full lg:w-56 shrink-0 bg-white border border-slate-200 rounded-xl shadow-sm p-3 sticky top-10">
			<!-- Plugin Brand Header -->
			<div class="flex items-center gap-2.5 px-3 py-2.5 mb-3 border-b border-slate-100">
				<div class="w-8 h-8 rounded-lg bg-sky-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
					⚡
				</div>
				<div>
					<h1 class="text-sm font-bold text-slate-900 leading-tight m-0">OG of WP</h1>
					<span class="text-xs text-slate-500 font-medium">v<?php echo esc_html( OG_WP_VERSION ); ?></span>
				</div>
			</div>

			<!-- Navigation Links -->
			<nav class="space-y-1">
				<button type="button" @click="activeTab = 'dashboard'"
					:class="activeTab === 'dashboard' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
					class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs transition text-left cursor-pointer">
					<span class="flex items-center gap-2.5">
						<span class="text-sm">📊</span> Dashboard
					</span>
					<span v-if="securityScore < 100" class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">
						{{ securityScore }}%
					</span>
				</button>

				<button type="button" @click="activeTab = 'modules'"
					:class="activeTab === 'modules' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
					class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs transition text-left cursor-pointer">
					<span class="flex items-center gap-2.5">
						<span class="text-sm">🛡️</span> Security Modules
					</span>
					<span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold">
						{{ activeSecurityCount }}/11
					</span>
				</button>

				<button type="button" @click="activeTab = 'utilities'"
					:class="activeTab === 'utilities' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
					class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs transition text-left cursor-pointer">
					<span class="flex items-center gap-2.5">
						<span class="text-sm">⚡</span> Utilities
					</span>
				</button>

				<button type="button" @click="activeTab = 'email'"
					:class="activeTab === 'email' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
					class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs transition text-left cursor-pointer">
					<span class="flex items-center gap-2.5">
						<span class="text-sm">✉️</span> Email Suite
					</span>
					<span v-if="options.enable_module_email == 1" class="w-2 h-2 rounded-full bg-emerald-500"></span>
				</button>

				<button type="button" @click="activeTab = 'branding'"
					:class="activeTab === 'branding' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
					class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition text-left cursor-pointer">
					<span class="text-sm">🎨</span> Branding
				</button>

				<button type="button" @click="activeTab = 'logs'"
					:class="activeTab === 'logs' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
					class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs transition text-left cursor-pointer">
					<span class="flex items-center gap-2.5">
						<span class="text-sm">📜</span> Audit Logs
					</span>
				</button>
			</nav>

			<!-- Quick Global Save Action in Sidebar -->
			<div class="mt-4 pt-3 border-t border-slate-100">
				<button type="button" @click="saveAllSettings" :disabled="isSaving"
					class="w-full py-2 px-3 bg-sky-600 hover:bg-sky-700 disabled:bg-sky-400 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
					<span v-if="isSaving" class="inline-block animate-spin">⏳</span>
					<span>{{ isSaving ? 'Saving...' : 'Save All Changes' }}</span>
				</button>
			</div>

			<!-- Status footer -->
			<div class="mt-4 pt-2 text-[11px] text-slate-500 flex items-center gap-1.5 px-2">
				<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
				<span>Engine Active</span>
			</div>
		</aside>

		<!-- Main Content Area -->
		<main class="flex-1 min-w-0 w-full space-y-4">
			
			<!-- Settings Form Wrapper (captures all inputs for traditional + AJAX saves) -->
			<form id="og-wp-settings-form" method="post" action="options.php" @submit.prevent="saveAllSettings">
				<?php settings_fields( 'og_wp_option_group' ); ?>

				<!-- TAB 1: DASHBOARD -->
				<section v-show="activeTab === 'dashboard'" class="space-y-4">
					<!-- Top Metric Cards Row -->
					<div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
						<!-- Security Score Card -->
						<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center gap-4">
							<div class="relative w-16 h-16 shrink-0 flex items-center justify-center">
								<svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
									<circle cx="50" cy="50" r="42" stroke="#e2e8f0" stroke-width="8" fill="none"></circle>
									<circle cx="50" cy="50" r="42" :stroke="scoreColor" stroke-width="8" fill="none"
										:stroke-dasharray="strokeDasharray" stroke-linecap="round" class="transition-all duration-700"></circle>
								</svg>
								<span class="absolute text-base font-bold text-slate-900">{{ securityScore }}</span>
							</div>
							<div>
								<span class="text-xs uppercase tracking-wider font-semibold text-slate-600 block">Security Score</span>
								<p class="text-xs text-slate-600 m-0 mt-0.5">
									{{ securityScore >= 80 ? 'Optimal Protection' : (securityScore >= 50 ? 'Moderate Security' : 'Attention Needed') }}
								</p>
							</div>
						</div>

						<!-- Active Modules Card -->
						<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
							<div>
								<span class="text-xs uppercase tracking-wider font-semibold text-slate-600 block">Active Modules</span>
								<div class="text-2xl font-bold text-slate-900 mt-1">
									{{ activeSecurityCount }} <span class="text-xs text-slate-600 font-normal">/ 11 active</span>
								</div>
								<p class="text-xs text-slate-600 m-0 mt-0.5">Core protection engines</p>
							</div>
							<div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
								🛡️
							</div>
						</div>

						<!-- Threats Blocked Card -->
						<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
							<div>
								<span class="text-xs uppercase tracking-wider font-semibold text-slate-600 block">Threats Blocked</span>
								<div class="text-2xl font-bold text-slate-900 mt-1">
									<?php echo number_format_i18n( $threats_blocked ); ?>
								</div>
								<p class="text-xs text-slate-600 m-0 mt-0.5">WAF blocks in last 24h</p>
							</div>
							<div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
								🚫
							</div>
						</div>
					</div>

					<!-- Quick Actions & Scanner Banner -->
					<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
						<div>
							<h3 class="text-sm font-bold text-slate-900 m-0">Malware & Checksum Scanner</h3>
							<p class="text-xs text-slate-600 m-0 mt-0.5">Quickly verify core WordPress file integrity and scan plugins for tampered code.</p>
						</div>
						<div class="flex items-center gap-3 shrink-0">
							<span v-if="scanner.result" :class="scanner.isError ? 'text-rose-600' : 'text-emerald-600'" class="text-xs font-semibold">
								{{ scanner.result }}
							</span>
							<button type="button" @click="runMalwareScan" :disabled="scanner.isRunning"
								class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-500 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-1.5 cursor-pointer">
								<span v-if="scanner.isRunning" class="inline-block animate-spin">⚙️</span>
								<span>{{ scanner.isRunning ? 'Scanning Core Files...' : 'Run Malware Scan Now' }}</span>
							</button>
						</div>
					</div>

					<!-- Settings JSON Import / Export -->
					<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
						<h3 class="text-sm font-bold text-slate-900 m-0 mb-3 flex items-center gap-2">
							<span>🔄</span> Settings Import & Export
						</h3>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<!-- Export Card -->
							<div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 flex flex-col justify-between">
								<div>
									<h4 class="text-xs font-bold text-slate-900 m-0">Export Configuration</h4>
									<p class="text-xs text-slate-600 mt-1 mb-2.5">Download current plugin configurations and module rules into a JSON file.</p>
									<label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer mb-3">
										<input type="checkbox" v-model="exportSecrets" class="rounded border-slate-300 text-sky-600">
										<span>Include API Keys & Passwords</span>
									</label>
								</div>
								<div>
									<a :href="exportUrl" class="inline-block px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded text-xs font-semibold shadow-sm transition">
										📥 Download JSON Export
									</a>
								</div>
							</div>

							<!-- Import Card -->
							<div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 flex flex-col justify-between">
								<div>
									<h4 class="text-xs font-bold text-slate-900 m-0">Import Configuration</h4>
									<p class="text-xs text-slate-600 mt-1 mb-2.5">Restore plugin settings from a previously exported JSON backup file.</p>
									<input type="file" ref="importFileInput" accept=".json" class="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 mb-2">
								</div>
								<div>
									<button type="button" @click="handleImportSettings" :disabled="isImporting"
										class="px-3 py-1.5 bg-sky-600 hover:bg-sky-700 disabled:bg-sky-400 text-white rounded text-xs font-semibold shadow-sm transition cursor-pointer">
										{{ isImporting ? 'Importing...' : 'Upload & Restore JSON' }}
									</button>
								</div>
							</div>
						</div>
					</div>
				</section>

				<!-- TAB 2: SECURITY MODULES (GRID LAYOUT) -->
				<section v-show="activeTab === 'modules'" class="space-y-4">
					<div class="flex items-center justify-between flex-wrap gap-2 pb-1">
						<div>
							<h2 class="text-base font-bold text-slate-900 m-0">Security Modules</h2>
							<p class="text-xs text-slate-600 m-0 mt-0.5">Toggle and configure security protection layers. Click any card to expand its settings.</p>
						</div>
						<div class="flex items-center gap-2">
							<input type="text" v-model="moduleSearch" placeholder="Filter modules..." 
								class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 w-44 bg-white">
						</div>
					</div>

					<!-- Compact Responsive Grid (2-3 columns) -->
					<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
						<?php foreach ( $security_modules as $key => $info ) : 
							$field_id = "enable_module_{$key}";
						?>
						<div v-show="matchesModule('<?php echo esc_js( $key ); ?>', '<?php echo esc_js( $info['title'] ); ?>')"
							class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex flex-col transition hover:border-slate-300">
							<!-- Card Header -->
							<div class="p-3.5 flex items-start justify-between gap-3 cursor-pointer select-none bg-white"
								@click="toggleConfig('<?php echo esc_js( $key ); ?>')">
								<div class="flex items-start gap-2.5">
									<label class="og-switch mt-0.5" @click.stop>
										<input type="hidden" name="og_wp_options[<?php echo esc_attr( $field_id ); ?>]" value="0">
										<input type="checkbox" name="og_wp_options[<?php echo esc_attr( $field_id ); ?>]" value="1"
											v-model="options.<?php echo esc_attr( $field_id ); ?>"
											true-value="1" false-value="0">
										<span class="og-slider"></span>
									</label>
									<div>
										<h4 class="text-xs font-bold text-slate-900 m-0 leading-tight">
											<?php echo esc_html( $info['title'] ); ?>
										</h4>
										<p class="text-[11px] text-slate-600 m-0 mt-1 line-clamp-2">
											<?php echo esc_html( $info['desc'] ); ?>
										</p>
									</div>
								</div>
								<div class="shrink-0 flex items-center gap-1.5">
									<span :class="options.<?php echo esc_attr( $field_id ); ?> == '1' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
										class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full border">
										{{ options.<?php echo esc_attr( $field_id ); ?> == '1' ? 'Active' : 'Off' }}
									</span>
									<span class="text-xs text-slate-400 transition transform"
										:class="openConfigs['<?php echo esc_js( $key ); ?>'] ? 'rotate-180' : ''">▾</span>
								</div>
							</div>

							<!-- Collapsible Configuration Drawer -->
							<div v-show="openConfigs['<?php echo esc_js( $key ); ?>']"
								class="border-t border-slate-100 bg-slate-50 p-3.5 text-xs text-slate-700 space-y-3">
								<?php 
								if ( ! empty( $module_settings_html[ $key ] ) ) {
									echo $module_settings_html[ $key ];
								} else {
									echo '<p class="text-slate-600 italic m-0">No advanced settings required for this module.</p>';
								}
								?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</section>

				<!-- TAB 3: UTILITIES (GRID LAYOUT) -->
				<section v-show="activeTab === 'utilities'" class="space-y-4">
					<div>
						<h2 class="text-base font-bold text-slate-900 m-0">Multipurpose Utilities</h2>
						<p class="text-xs text-slate-600 m-0 mt-0.5">Enable and configure auxiliary content and optimization tools.</p>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
						<?php foreach ( $utility_modules as $key => $info ) : 
							$field_id = "enable_module_{$key}";
						?>
						<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex flex-col transition hover:border-slate-300">
							<div class="p-3.5 flex items-start justify-between gap-3 cursor-pointer select-none bg-white"
								@click="toggleConfig('<?php echo esc_js( $key ); ?>')">
								<div class="flex items-start gap-2.5">
									<label class="og-switch mt-0.5" @click.stop>
										<input type="hidden" name="og_wp_options[<?php echo esc_attr( $field_id ); ?>]" value="0">
										<input type="checkbox" name="og_wp_options[<?php echo esc_attr( $field_id ); ?>]" value="1"
											v-model="options.<?php echo esc_attr( $field_id ); ?>"
											true-value="1" false-value="0">
										<span class="og-slider"></span>
									</label>
									<div>
										<h4 class="text-xs font-bold text-slate-900 m-0 leading-tight">
											<?php echo esc_html( $info['title'] ); ?>
										</h4>
										<p class="text-[11px] text-slate-600 m-0 mt-1">
											<?php echo esc_html( $info['desc'] ); ?>
										</p>
									</div>
								</div>
								<div class="shrink-0 flex items-center gap-1.5">
									<span :class="options.<?php echo esc_attr( $field_id ); ?> == '1' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
										class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full border">
										{{ options.<?php echo esc_attr( $field_id ); ?> == '1' ? 'Active' : 'Off' }}
									</span>
									<span class="text-xs text-slate-400 transition transform"
										:class="openConfigs['<?php echo esc_js( $key ); ?>'] ? 'rotate-180' : ''">▾</span>
								</div>
							</div>

							<div v-show="openConfigs['<?php echo esc_js( $key ); ?>']"
								class="border-t border-slate-100 bg-slate-50 p-3.5 text-xs text-slate-700 space-y-3">
								<?php 
								if ( ! empty( $module_settings_html[ $key ] ) ) {
									echo $module_settings_html[ $key ];
								} else {
									echo '<p class="text-slate-600 italic m-0">No configuration required.</p>';
								}
								?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</section>

				<!-- TAB 4: EMAIL & DELIVERABILITY SUITE -->
				<section v-show="activeTab === 'email'" class="space-y-4">
					<div class="flex items-center justify-between flex-wrap gap-2">
						<div>
							<h2 class="text-base font-bold text-slate-900 m-0">Email & Deliverability Suite</h2>
							<p class="text-xs text-slate-600 m-0 mt-0.5">Multi-provider routing, zero-blocking async queues, live diagnostics, and DNS health.</p>
						</div>
						<div class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-1.5 shadow-sm">
							<span class="text-xs font-semibold text-slate-700">Mailer Engine:</span>
							<label class="og-switch">
								<input type="hidden" name="og_wp_options[enable_module_email]" value="0">
								<input type="checkbox" name="og_wp_options[enable_module_email]" value="1"
									v-model="options.enable_module_email" true-value="1" false-value="0">
								<span class="og-slider"></span>
							</label>
							<span :class="options.enable_module_email == '1' ? 'text-emerald-700 font-semibold' : 'text-slate-600'" class="text-xs">
								{{ options.enable_module_email == '1' ? 'Enabled' : 'Disabled' }}
							</span>
						</div>
					</div>

					<!-- Email Metrics Cards -->
					<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
						<div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 block">Primary Provider</span>
							<div class="text-sm font-bold text-sky-700 mt-1"><?php echo esc_html( $active_provider ); ?></div>
							<p class="text-[11px] text-slate-600 m-0 mt-0.5">Active Mailer</p>
						</div>
						<div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 block">Delivered Emails</span>
							<div class="text-xl font-bold text-emerald-600 mt-1"><?php echo number_format_i18n( $email_stats['sent'] ); ?></div>
							<p class="text-[11px] text-slate-600 m-0 mt-0.5">Successful transmissions</p>
						</div>
						<div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 block">Failed Deliveries</span>
							<div class="text-xl font-bold text-rose-600 mt-1"><?php echo number_format_i18n( $email_stats['failed'] ); ?></div>
							<p class="text-[11px] text-slate-600 m-0 mt-0.5">Connection/Auth drops</p>
						</div>
						<div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm flex flex-col justify-between">
							<div>
								<span class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 block">Async Queue</span>
								<div class="text-xl font-bold text-slate-900 mt-1">{{ emailQueueCount }}</div>
							</div>
							<div class="flex items-center justify-between mt-2 pt-1.5 border-t border-slate-100">
								<span class="text-[10px] text-slate-600">Pending Worker</span>
								<button type="button" @click="flushEmailQueue" :disabled="isFlushingQueue"
									class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-semibold transition cursor-pointer">
									{{ isFlushingQueue ? 'Flushing...' : '⚡ Flush Now' }}
								</button>
							</div>
						</div>
					</div>

					<!-- Deliverability Health Scorecard -->
					<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
						<div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
							<div class="flex items-center gap-3">
								<div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shadow-sm"
									:class="<?php echo $d_score; ?> >= 80 ? 'bg-emerald-500' : (<?php echo $d_score; ?> >= 50 ? 'bg-amber-500' : 'bg-rose-500')">
									<?php echo $d_score; ?>
								</div>
								<div>
									<h4 class="text-xs font-bold text-slate-900 m-0">Deliverability Health Score: <?php echo $d_score; ?> / 100</h4>
									<p class="text-[11px] text-slate-600 m-0 mt-0.5">
										<?php echo $d_score >= 80 ? 'Optimal Sender Reputation' : 'Domain DNS Configuration Hardening Recommended'; ?>
									</p>
								</div>
							</div>
						</div>
						<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
							<?php foreach ( $score_items as $item ) : 
								$badge_bg = $item['status'] === 'pass' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($item['status'] === 'warn' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-800');
								$icon = $item['status'] === 'pass' ? '✅' : ($item['status'] === 'warn' ? '⚠️' : '❌');
							?>
							<div class="border rounded-lg p-2.5 text-xs <?php echo $badge_bg; ?>">
								<div class="font-bold flex items-center gap-1.5">
									<span><?php echo $icon; ?></span> <?php echo esc_html( $item['label'] ); ?>
								</div>
								<div class="text-[11px] text-slate-600 mt-1"><?php echo esc_html( $item['desc'] ); ?></div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Dispatcher Configuration Drawer -->
					<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
						<div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between cursor-pointer select-none"
							@click="toggleConfig('email_dispatcher')">
							<h3 class="text-xs font-bold text-slate-900 m-0 flex items-center gap-2">
								<span>⚙️</span> Dispatcher & Mail Server Authentication
							</h3>
							<span class="text-xs text-slate-400">▾</span>
						</div>
						<div v-show="openConfigs['email_dispatcher'] !== false" class="p-4 text-xs text-slate-700">
							<?php echo $email_settings_html; ?>
						</div>
					</div>

					<!-- Live Test Email & Diagnostics -->
					<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
						<h3 class="text-xs font-bold text-slate-900 m-0 flex items-center gap-2">
							<span>🚀</span> Send Test Email & Live Diagnostics
						</h3>
						<div class="flex flex-col sm:flex-row gap-2 max-w-xl">
							<input type="email" v-model="testEmailAddress" placeholder="you@domain.com"
								class="text-xs px-3 py-2 rounded-lg border border-slate-200 flex-1">
							<button type="button" @click="sendTestEmail" :disabled="isSendingTest"
								class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-500 text-white rounded-lg text-xs font-semibold shadow-sm transition shrink-0 cursor-pointer">
								{{ isSendingTest ? 'Dispatching Payload...' : 'Send Test Email' }}
							</button>
						</div>
						<div v-if="testResult" :class="testResult.success ? 'text-emerald-600' : 'text-rose-600'" class="text-xs font-semibold">
							{{ testResult.message }}
						</div>
						<div v-if="testResult && testResult.transcript" class="mt-2">
							<span class="text-[11px] font-bold text-slate-700 block mb-1">Live SMTP Protocol Transcript:</span>
							<pre class="og-terminal">{{ testResult.transcript }}</pre>
						</div>
					</div>

					<!-- DNS Health Inspector -->
					<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
						<h3 class="text-xs font-bold text-slate-900 m-0 flex items-center gap-2">
							<span>🛡️</span> Domain DNS Inspector (SPF, DMARC, MX)
						</h3>
						<div class="flex flex-col sm:flex-row gap-2 max-w-xl">
							<input type="text" v-model="dnsDomain" placeholder="yourdomain.com"
								class="text-xs px-3 py-2 rounded-lg border border-slate-200 flex-1">
							<button type="button" @click="verifyDns" :disabled="isVerifyingDns"
								class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold shadow-sm transition shrink-0 cursor-pointer">
								{{ isVerifyingDns ? 'Querying DNS...' : 'Verify DNS Records' }}
							</button>
						</div>
						<div v-if="dnsResults" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
							<div class="border border-slate-200 rounded-lg p-3 bg-slate-50 text-xs">
								<div class="flex items-center justify-between font-bold text-slate-800 mb-1">
									<span>MX Records</span>
									<span :class="dnsResults.mx.status === 'pass' ? 'text-emerald-600' : 'text-amber-600'">{{ dnsResults.mx.status }}</span>
								</div>
								<div class="text-[11px] text-slate-600 font-mono">{{ dnsResults.mx.records.join(', ') || dnsResults.mx.details }}</div>
							</div>
							<div class="border border-slate-200 rounded-lg p-3 bg-slate-50 text-xs">
								<div class="flex items-center justify-between font-bold text-slate-800 mb-1">
									<span>SPF Record</span>
									<span :class="dnsResults.spf.status === 'pass' ? 'text-emerald-600' : 'text-rose-600'">{{ dnsResults.spf.status }}</span>
								</div>
								<div class="text-[11px] text-slate-600 font-mono break-all">{{ dnsResults.spf.record || dnsResults.spf.details }}</div>
							</div>
							<div class="border border-slate-200 rounded-lg p-3 bg-slate-50 text-xs">
								<div class="flex items-center justify-between font-bold text-slate-800 mb-1">
									<span>DMARC Record</span>
									<span :class="dnsResults.dmarc.status === 'pass' ? 'text-emerald-600' : 'text-rose-600'">{{ dnsResults.dmarc.status }}</span>
								</div>
								<div class="text-[11px] text-slate-600 font-mono break-all">{{ dnsResults.dmarc.record || dnsResults.dmarc.details }}</div>
							</div>
						</div>
					</div>

					<!-- Email Logs Table -->
					<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden p-4 space-y-3">
						<div class="flex items-center justify-between flex-wrap gap-2">
							<h3 class="text-xs font-bold text-slate-900 m-0 flex items-center gap-2">
								<span>📋</span> Recent Outgoing Deliveries
							</h3>
							<div class="flex items-center gap-2">
								<a href="<?php echo esc_url( $export_email_logs_url ); ?>" class="text-[11px] px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold transition">
									Export CSV
								</a>
								<button type="button" @click="clearEmailLogs" class="text-[11px] px-2.5 py-1 text-rose-600 hover:bg-rose-50 rounded font-semibold transition cursor-pointer">
									Clear Logs
								</button>
							</div>
						</div>

						<div class="overflow-x-auto">
							<table class="w-full text-left text-xs border-collapse">
								<thead>
									<tr class="border-b border-slate-200 text-slate-600 bg-slate-50">
										<th class="py-2 px-3 font-semibold">Date / Time</th>
										<th class="py-2 px-3 font-semibold">Recipient</th>
										<th class="py-2 px-3 font-semibold">Subject</th>
										<th class="py-2 px-3 font-semibold">Status</th>
										<th class="py-2 px-3 font-semibold">Opens</th>
										<th class="py-2 px-3 font-semibold">Engine</th>
										<th class="py-2 px-3 font-semibold text-right">Actions</th>
									</tr>
								</thead>
								<tbody class="divide-y divide-slate-100">
									<?php if ( empty( $email_logs ) ) : ?>
										<tr><td colspan="7" class="py-6 text-center text-slate-600 italic">No outgoing deliveries logged yet.</td></tr>
									<?php else : ?>
										<?php foreach ( $email_logs as $l ) : 
											$status_bg = $l['status'] === 'sent' ? 'bg-emerald-50 text-emerald-700' : ($l['status'] === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700');
										?>
										<tr class="hover:bg-slate-50 transition">
											<td class="py-2.5 px-3 text-slate-500 whitespace-nowrap"><?php echo esc_html( substr( $l['created_at'], 0, 16 ) ); ?></td>
											<td class="py-2.5 px-3 font-medium text-slate-900"><?php echo esc_html( $l['to_email'] ); ?></td>
											<td class="py-2.5 px-3 text-slate-700"><?php echo esc_html( wp_trim_words( $l['subject'], 6 ) ); ?></td>
											<td class="py-2.5 px-3 whitespace-nowrap">
												<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo $status_bg; ?>">
													<?php echo esc_html( $l['status'] ); ?>
												</span>
											</td>
											<td class="py-2.5 px-3 whitespace-nowrap">
												<?php if ( (int)$l['open_count'] > 0 ) : ?>
													<span class="text-emerald-600 font-bold">👁️ <?php echo (int)$l['open_count']; ?></span>
												<?php else : ?>
													<span class="text-slate-600">—</span>
												<?php endif; ?>
											</td>
											<td class="py-2.5 px-3 uppercase text-[11px] font-semibold text-slate-600"><?php echo esc_html( $l['provider'] ); ?></td>
											<td class="py-2.5 px-3 text-right whitespace-nowrap space-x-1">
												<button type="button" @click="viewEmail(<?php echo (int)$l['id']; ?>)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-semibold transition cursor-pointer">Preview</button>
												<button type="button" @click="resendEmail(<?php echo (int)$l['id']; ?>)" class="px-2 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded text-[11px] font-semibold transition cursor-pointer">Resend</button>
											</td>
										</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</section>

				<!-- TAB 5: BRANDING -->
				<section v-show="activeTab === 'branding'" class="space-y-4">
					<div>
						<h2 class="text-base font-bold text-slate-900 m-0">Admin Branding & White Labeling</h2>
						<p class="text-xs text-slate-600 m-0 mt-0.5">Customize the WordPress administration interface to reflect your client or company brand.</p>
					</div>

					<div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm text-xs text-slate-700 space-y-3">
						<?php echo $branding_settings_html; ?>
					</div>
				</section>

				<!-- TAB 6: AUDIT LOGS -->
				<section v-show="activeTab === 'logs'" class="space-y-4">
					<div class="flex items-center justify-between flex-wrap gap-2">
						<div>
							<h2 class="text-base font-bold text-slate-900 m-0">Security Audit Trail</h2>
							<p class="text-xs text-slate-600 m-0 mt-0.5">Immutable record of logins, administrative events, and security detections.</p>
						</div>
						<a href="<?php echo esc_url( $export_logs_url ); ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold shadow-sm transition">
							📥 Export Audit CSV
						</a>
					</div>

					<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden p-4">
						<div class="overflow-x-auto">
							<table class="w-full text-left text-xs border-collapse">
								<thead>
									<tr class="border-b border-slate-200 text-slate-600 bg-slate-50">
										<th class="py-2 px-3 font-semibold">Time</th>
										<th class="py-2 px-3 font-semibold">IP Address</th>
										<th class="py-2 px-3 font-semibold">User</th>
										<th class="py-2 px-3 font-semibold">Action</th>
										<th class="py-2 px-3 font-semibold">Details</th>
									</tr>
								</thead>
								<tbody class="divide-y divide-slate-100">
									<?php if ( empty( $audit_logs ) ) : ?>
										<tr><td colspan="5" class="py-6 text-center text-slate-600 italic">No activity recorded yet.</td></tr>
									<?php else : ?>
										<?php foreach ( $audit_logs as $a ) : ?>
										<tr class="hover:bg-slate-50 transition">
											<td class="py-2.5 px-3 text-slate-500 whitespace-nowrap"><?php echo esc_html( $a['time'] ); ?></td>
											<td class="py-2.5 px-3 font-mono text-[11px] text-slate-600"><?php echo esc_html( $a['ip'] ); ?></td>
											<td class="py-2.5 px-3 text-slate-800 font-medium"><?php echo esc_html( $a['user'] ); ?></td>
											<td class="py-2.5 px-3 font-bold text-slate-900"><?php echo esc_html( $a['action'] ); ?></td>
											<td class="py-2.5 px-3 text-slate-600"><?php echo esc_html( $a['details'] ); ?></td>
										</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</section>

			</form>
		</main>
	</div>

	<!-- Email Preview Modal -->
	<div v-if="emailModal.visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
		<div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[85vh]">
			<div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
				<h3 class="text-xs font-bold m-0 flex items-center gap-2">
					<span>✉️</span> {{ emailModal.data.subject || 'Email Transmission Details' }}
				</h3>
				<button type="button" @click="emailModal.visible = false" class="text-slate-400 hover:text-white text-lg leading-none">&times;</button>
			</div>
			<div class="p-4 overflow-y-auto space-y-3 flex-1 text-xs">
				<div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
					<div><strong>To:</strong> {{ emailModal.data.to }}</div>
					<div><strong>Engine:</strong> {{ emailModal.data.provider }}</div>
					<div><strong>Date:</strong> {{ emailModal.data.created_at }}</div>
					<div><strong>Status:</strong> <span class="uppercase font-bold text-sky-700">{{ emailModal.data.status }}</span></div>
				</div>
				<div v-if="emailModal.data.error_details" class="p-2.5 bg-rose-50 border border-rose-200 rounded text-rose-700 font-medium">
					⚠️ {{ emailModal.data.error_details }}
				</div>
				<div class="flex gap-2 border-b border-slate-200 pb-2">
					<button type="button" @click="emailModal.view = 'html'"
						:class="emailModal.view === 'html' ? 'bg-sky-50 text-sky-700 font-bold' : 'text-slate-600'"
						class="px-2.5 py-1 rounded text-xs cursor-pointer">HTML Preview</button>
					<button type="button" @click="emailModal.view = 'text'"
						:class="emailModal.view === 'text' ? 'bg-sky-50 text-sky-700 font-bold' : 'text-slate-600'"
						class="px-2.5 py-1 rounded text-xs cursor-pointer">Plain Text</button>
					<button type="button" @click="emailModal.view = 'headers'"
						:class="emailModal.view === 'headers' ? 'bg-sky-50 text-sky-700 font-bold' : 'text-slate-600'"
						class="px-2.5 py-1 rounded text-xs cursor-pointer">Headers</button>
				</div>
				<div v-show="emailModal.view === 'html'" class="border border-slate-200 rounded overflow-hidden">
					<iframe :srcdoc="emailModal.data.message" class="w-full h-64 border-0"></iframe>
				</div>
				<div v-show="emailModal.view === 'text'">
					<pre class="og-terminal max-h-64">{{ emailModal.data.plain_text || 'No text content.' }}</pre>
				</div>
				<div v-show="emailModal.view === 'headers'">
					<pre class="og-terminal max-h-64">{{ emailModal.data.headers }}</pre>
				</div>
			</div>
			<div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex justify-end">
				<button type="button" @click="emailModal.visible = false" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded text-xs font-semibold shadow-sm transition cursor-pointer">
					Close
				</button>
			</div>
		</div>
	</div>
</div>

<script>
// Tailwind Preflight Disable to preserve WordPress Core Admin
if (window.tailwind) {
	tailwind.config = {
		corePlugins: {
			preflight: false
		}
	};
}

(function() {
	if (typeof Vue === 'undefined') {
		console.error('Vue 3 is not loaded.');
		return;
	}

	const { createApp, ref, computed } = Vue;

	createApp({
		setup() {
			const activeTab = ref('dashboard');
			const isSaving = ref(false);
			const isImporting = ref(false);
			const isFlushingQueue = ref(false);
			const isSendingTest = ref(false);
			const isVerifyingDns = ref(false);
			const exportSecrets = ref(false);
			const moduleSearch = ref('');
			const testEmailAddress = ref('<?php echo esc_js( wp_get_current_user()->user_email ); ?>');
			const dnsDomain = ref('<?php echo esc_js( $score_domain ); ?>');
			const emailQueueCount = ref(<?php echo (int) $email_stats['queued']; ?>);

			const options = ref(<?php echo wp_json_encode( $options ); ?> || {});
			const openConfigs = ref({});

			const toast = ref({ visible: false, message: '', type: 'success' });
			const scanner = ref({ isRunning: false, result: '', isError: false });
			const testResult = ref(null);
			const dnsResults = ref(null);
			const emailModal = ref({ visible: false, view: 'html', data: {} });

			function showToast(message, type = 'success') {
				toast.value = { visible: true, message, type };
				setTimeout(() => { toast.value.visible = false; }, 3500);
			}

			// Reactive security calculations
			const securityModuleKeys = [
				'auth', 'waf', 'files', 'scanner', 'spam',
				'headers', 'audit', 'ssl', 'db', 'user', 'hardening'
			];

			const activeSecurityCount = computed(() => {
				let count = 0;
				securityModuleKeys.forEach(k => {
					if (options.value['enable_module_' + k] == '1') count++;
				});
				return count;
			});

			const securityScore = computed(() => {
				return Math.round((activeSecurityCount.value / 11) * 100);
			});

			const scoreColor = computed(() => {
				if (securityScore.value >= 80) return '#10b981';
				if (securityScore.value >= 50) return '#f59e0b';
				return '#ef4444';
			});

			const strokeDasharray = computed(() => {
				const circ = 2 * Math.PI * 42; // ~263.89
				const val = (securityScore.value / 100) * circ;
				return `${val}, ${circ}`;
			});

			const exportUrl = computed(() => {
				return '<?php echo admin_url( 'admin-ajax.php' ); ?>?action=og_wp_export_settings&include_secrets=' + (exportSecrets.value ? '1' : '0');
			});

			function toggleConfig(key) {
				openConfigs.value[key] = !openConfigs.value[key];
			}

			function matchesModule(key, title) {
				if (!moduleSearch.value) return true;
				const q = moduleSearch.value.toLowerCase();
				return key.toLowerCase().includes(q) || title.toLowerCase().includes(q);
			}

			// Save all settings via AJAX
			async function saveAllSettings() {
				isSaving.value = true;
				try {
					const form = document.getElementById('og-wp-settings-form');
					const formData = new FormData(form);
					formData.append('action', 'og_wp_save_all_settings');
					formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');

					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						showToast(data.data && data.data.message ? data.data.message : 'Settings saved successfully!');
					} else {
						showToast('Save failed: ' + (data.data || 'Unknown error'), 'error');
					}
				} catch (err) {
					showToast('Error saving settings: ' + err.message, 'error');
				} finally {
					isSaving.value = false;
				}
			}

			// Malware scan
			async function runMalwareScan() {
				scanner.value = { isRunning: true, result: '', isError: false };
				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_run_scan');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						scanner.value = { isRunning: false, result: data.data, isError: false };
						showToast('Scan complete: ' + data.data);
					} else {
						scanner.value = { isRunning: false, result: data.data || 'Scan failed', isError: true };
						showToast('Scan error: ' + (data.data || 'Failed'), 'error');
					}
				} catch (e) {
					scanner.value = { isRunning: false, result: 'Network error', isError: true };
					showToast('Network error during scan', 'error');
				}
			}

			// Import settings
			async function handleImportSettings() {
				const fileInput = document.querySelector('input[type="file"]');
				if (!fileInput || !fileInput.files.length) {
					alert('Please choose a valid JSON settings file.');
					return;
				}
				isImporting.value = true;
				const formData = new FormData();
				formData.append('action', 'og_wp_import_settings');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');
				formData.append('settings_file', fileInput.files[0]);

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						alert(data.data || 'Settings imported!');
						window.location.reload();
					} else {
						alert('Import error: ' + (data.data || 'Failed'));
					}
				} catch (e) {
					alert('Error importing: ' + e.message);
				} finally {
					isImporting.value = false;
				}
			}

			// Send test email
			async function sendTestEmail() {
				if (!testEmailAddress.value) {
					alert('Please enter a recipient email.');
					return;
				}
				isSendingTest.value = true;
				testResult.value = null;

				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_send_test_email');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');
				formData.append('to_email', testEmailAddress.value);

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					testResult.value = data.data || { success: data.success, message: 'Dispatched' };
					showToast(data.success ? 'Test email dispatched' : 'Test delivery failed', data.success ? 'success' : 'error');
				} catch (e) {
					testResult.value = { success: false, message: 'Network error: ' + e.message };
				} finally {
					isSendingTest.value = false;
				}
			}

			// Verify DNS
			async function verifyDns() {
				if (!dnsDomain.value) {
					alert('Please enter a domain to check.');
					return;
				}
				isVerifyingDns.value = true;
				dnsResults.value = null;

				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_check_domain_dns');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');
				formData.append('domain', dnsDomain.value);

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						dnsResults.value = data.data;
						showToast('DNS verification complete');
					} else {
						alert(data.data || 'DNS check failed');
					}
				} catch (e) {
					alert('DNS check error: ' + e.message);
				} finally {
					isVerifyingDns.value = false;
				}
			}

			// Flush queue
			async function flushEmailQueue() {
				isFlushingQueue.value = true;
				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_flush_email_queue');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						emailQueueCount.value = 0;
						showToast(data.data && data.data.message ? data.data.message : 'Queue flushed successfully!');
					} else {
						showToast('Flush failed: ' + (data.data || 'Error'), 'error');
					}
				} catch (e) {
					showToast('Error flushing queue', 'error');
				} finally {
					isFlushingQueue.value = false;
				}
			}

			// View email modal
			async function viewEmail(logId) {
				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_view_email');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');
				formData.append('log_id', logId);

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						emailModal.value = { visible: true, view: 'html', data: data.data };
					} else {
						alert('Could not load email details.');
					}
				} catch (e) {
					alert('Error loading email: ' + e.message);
				}
			}

			// Resend email
			async function resendEmail(logId) {
				if (!confirm('Re-dispatch this email transmission?')) return;
				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_resend_email');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');
				formData.append('log_id', logId);

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						showToast(data.data || 'Email re-queued for transmission!');
					} else {
						alert(data.data || 'Failed to resend');
					}
				} catch (e) {
					alert('Error: ' + e.message);
				}
			}

			// Clear all email logs
			async function clearEmailLogs() {
				if (!confirm('Permanently clear all email delivery logs?')) return;
				const formData = new URLSearchParams();
				formData.append('action', 'og_wp_clear_email_logs');
				formData.append('og_wp_nonce', '<?php echo wp_create_nonce( 'og_wp_admin_ajax' ); ?>');

				try {
					const res = await fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						method: 'POST',
						body: formData
					});
					const data = await res.json();
					if (data.success) {
						showToast('Logs cleared!');
						setTimeout(() => window.location.reload(), 800);
					}
				} catch (e) {
					alert('Error: ' + e.message);
				}
			}

			return {
				activeTab,
				options,
				openConfigs,
				toggleConfig,
				matchesModule,
				moduleSearch,
				activeSecurityCount,
				securityScore,
				scoreColor,
				strokeDasharray,
				saveAllSettings,
				isSaving,
				scanner,
				runMalwareScan,
				exportSecrets,
				exportUrl,
				handleImportSettings,
				isImporting,
				testEmailAddress,
				sendTestEmail,
				isSendingTest,
				testResult,
				dnsDomain,
				verifyDns,
				isVerifyingDns,
				dnsResults,
				emailQueueCount,
				flushEmailQueue,
				isFlushingQueue,
				viewEmail,
				resendEmail,
				clearEmailLogs,
				emailModal,
				toast
			};
		}
	}).mount('#og-wp-app');
})();
</script>
