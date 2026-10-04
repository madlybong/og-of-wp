<?php
/**
 * GitHub Auto-Updater for OG of WP
 *
 * Checks for updates against the GitHub Releases API and integrates
 * with the native WordPress update transient.
 *
 * @package OG_OF_WP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Updater {

	/**
	 * The plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * The plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * GitHub repository.
	 *
	 * @var string
	 */
	private $github_repo = 'madlybong/og-of-wp';

	/**
	 * Transient key for caching the release data.
	 *
	 * @var string
	 */
	private $cache_key = 'og_wp_github_release_cache';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_file The main plugin file path.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );
	}

	/**
	 * Fetch the latest release from GitHub API.
	 *
	 * @return object|false Release data on success, false on failure.
	 */
	private function get_latest_release() {
		$release = get_site_transient( $this->cache_key );

		if ( false === $release ) {
			$url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';

			$args = array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			);

			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$release = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! empty( $release ) ) {
				set_site_transient( $this->cache_key, $release, 12 * HOUR_IN_SECONDS );
			}
		}

		return $release;
	}

	/**
	 * Check for updates and modify the update transient.
	 *
	 * @param object $transient The update transient.
	 * @return object Modified transient.
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( ! $release ) {
			return $transient;
		}

		// GitHub tags usually have 'v' prefix (e.g., v1.0.2). We remove it for comparison.
		$latest_version = ltrim( $release->tag_name, 'v' );

		if ( version_compare( OG_WP_VERSION, $latest_version, '<' ) ) {
			// Find the zip asset in the release.
			$download_url = '';
			if ( ! empty( $release->assets ) ) {
				foreach ( $release->assets as $asset ) {
					if ( strpos( $asset->name, '.zip' ) !== false ) {
						$download_url = $asset->browser_download_url;
						break;
					}
				}
			}

			// Fallback to source zip if no specific asset is found.
			if ( empty( $download_url ) ) {
				$download_url = $release->zipball_url;
			}

			$plugin_data = array(
				'id'            => $this->plugin_slug,
				'slug'          => dirname( $this->plugin_slug ),
				'plugin'        => $this->plugin_slug,
				'new_version'   => $latest_version,
				'url'           => $release->html_url,
				'package'       => $download_url,
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '',
				'requires_php'  => '',
				'compatibility' => new stdClass(),
			);

			$transient->response[ $this->plugin_slug ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Provide plugin information for the "View version details" modal.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Installation API.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Plugin info object or false.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( empty( $args->slug ) || $args->slug !== dirname( $this->plugin_slug ) ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( ! $release ) {
			return $result;
		}

		$latest_version = ltrim( $release->tag_name, 'v' );
		
		$download_url = '';
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( strpos( $asset->name, '.zip' ) !== false ) {
					$download_url = $asset->browser_download_url;
					break;
				}
			}
		}

		if ( empty( $download_url ) ) {
			$download_url = $release->zipball_url;
		}

		$plugin_info = array(
			'name'              => 'OG of WP',
			'slug'              => dirname( $this->plugin_slug ),
			'version'           => $latest_version,
			'author'            => '<a href="https://astrake.com/">Astrake</a>',
			'homepage'          => $release->html_url,
			'requires'          => '5.0',
			'tested'            => '6.4',
			'requires_php'      => '7.4',
			'last_updated'      => $release->published_at,
			'sections'          => array(
				'description' => 'A complete, lightweight, and modular multi-purpose plugin for WordPress.',
				'changelog'   => wpautop( $release->body ),
			),
			'download_link'     => $download_url,
			'banners'           => array(),
		);

		return (object) $plugin_info;
	}

	/**
	 * Rename the extracted folder to the correct plugin slug.
	 * 
	 * GitHub zipballs extract into a folder like `madlybong-og-of-wp-1a2b3c4`.
	 * This hooks into the upgrader to rename it to `og-of-wp` so WP doesn't deactivate it.
	 */
	public function upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		// Ensure we are updating our plugin
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug ) {
			
			// $source is the unzipped directory name (e.g. madlybong-og-of-wp-...)
			// $remote_source is the parent directory holding the $source
			
			$new_source = trailingslashit( $remote_source ) . dirname( $this->plugin_slug );
			
			// Rename the directory to our exact plugin slug
			if ( $source !== $new_source ) {
				$wp_filesystem->move( $source, $new_source, true );
			}
			return trailingslashit( $new_source );
		}
		
		return $source;
	}
}
