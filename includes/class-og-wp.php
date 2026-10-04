<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP {
	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'upgrade_database' ) );
		$this->plugin_name = 'og-of-wp';
		$this->version = OG_WP_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->load_modules();

		// Setup cron
		add_action( 'init', array( $this, 'setup_cron' ) );
	}

	private function load_dependencies() {
		require_once OG_WP_PLUGIN_DIR . 'includes/class-og-wp-settings.php';
	}

	private function set_locale() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

		public function upgrade_database() {
		$version = get_option('og_wp_db_version', '0.0.0');
		if (version_compare($version, OG_WP_VERSION, '<')) {
			$options = get_option('og_wp_options', []);
			
			require_once OG_WP_PLUGIN_DIR . 'includes/modules/class-og-wp-audit.php';
			$audit = new OG_WP_Audit($options);
			$audit->create_table();

			require_once OG_WP_PLUGIN_DIR . 'includes/modules/class-og-wp-email.php';
			$email = new OG_WP_Email($options);
			$email->ensure_table_exists();

			if (file_exists(OG_WP_PLUGIN_DIR . 'includes/modules/class-og-wp-cf7.php')) {
				require_once OG_WP_PLUGIN_DIR . 'includes/modules/class-og-wp-cf7.php';
				$cf7 = new OG_WP_CF7($options);
				$cf7->ensure_tables_exist();
			}

			update_option('og_wp_db_version', OG_WP_VERSION);
		}
	}

	public function setup_cron() {
		if ( ! wp_next_scheduled( 'og_wp_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'og_wp_daily_cron' );
		}
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'og-of-wp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	private function define_admin_hooks() {
		$settings = new OG_WP_Settings();
		add_action( 'admin_menu', array( $settings, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $settings, 'register_settings' ) );
	}

	private function load_modules() {
		$options = get_option('og_wp_options', []);
		
		// Map options to class files and init them if enabled
		$modules = [
			'auth'      => 'class-og-wp-auth.php',
			'waf'       => 'class-og-wp-waf.php',
			'files'     => 'class-og-wp-files.php',
			'scanner'   => 'class-og-wp-scanner.php',
			'spam'      => 'class-og-wp-spam.php',
			'headers'   => 'class-og-wp-headers.php',
			'audit'     => 'class-og-wp-audit.php',
			'ssl'       => 'class-og-wp-ssl.php',
			'db'        => 'class-og-wp-db.php',
			'user'      => 'class-og-wp-user.php',
			'hardening' => 'class-og-wp-hardening.php',
			'duplicator'=> 'class-og-wp-duplicator.php',
			'porter'    => 'class-og-wp-porter.php',
			'media_cleaner' => 'class-og-wp-media-cleaner.php',
			'email'     => 'class-og-wp-email.php',
			'branding'  => 'class-og-wp-branding.php',
			'cf7'       => 'class-og-wp-cf7.php',
		];

		foreach ( $modules as $key => $file ) {
			if ( ! empty( $options["enable_module_{$key}"] ) ) {
				$path = OG_WP_PLUGIN_DIR . 'includes/modules/' . $file;
				if ( file_exists( $path ) ) {
					require_once $path;
					
					// Convert snake_case to Camel_Case for class names
					$class_name_part = str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $key ) ) );
					$class_name = 'OG_WP_' . $class_name_part;
					
					if ( class_exists( $class_name ) ) {
						new $class_name( $options );
					}
				}
			}
		}

		// Always load branding
		require_once OG_WP_PLUGIN_DIR . 'includes/modules/class-og-wp-branding.php';
		new OG_WP_Branding( $options );

		// In admin area, ensure utility and email modules are loaded for settings rendering and AJAX tools
		if ( is_admin() ) {
			$admin_modules = [
				'email'         => 'class-og-wp-email.php',
				'scanner'       => 'class-og-wp-scanner.php',
				'cf7'           => 'class-og-wp-cf7.php',
				'duplicator'    => 'class-og-wp-duplicator.php',
				'porter'        => 'class-og-wp-porter.php',
				'media_cleaner' => 'class-og-wp-media-cleaner.php',
			];
			foreach ( $admin_modules as $mod_key => $mod_file ) {
				if ( empty( $options[ "enable_module_{$mod_key}" ] ) ) {
					$fpath = OG_WP_PLUGIN_DIR . 'includes/modules/' . $mod_file;
					if ( file_exists( $fpath ) ) {
						require_once $fpath;
						$cname = 'OG_WP_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $mod_key ) ) );
						if ( class_exists( $cname ) ) {
							new $cname( $options );
						}
					}
				}
			}
		}
	}

	public function run() {
		// Hooks are registered in constructor/modules
	}
}



