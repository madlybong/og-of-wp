<?php
/**
 * Plugin Name: OG of WP
 * Plugin URI:  https://astrake.com/baddies/og-of-wp
 * Description: A complete, lightweight, and modular multi-purpose plugin for WordPress.
 * Version:     1.0.1
 * Author:      Astrake
 * Author URI:  https://astrake.com/baddies/og-of-wp
 * License:     GPLv2 or later
 * Text Domain: og-of-wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OG_WP_VERSION', '1.0.1' );
define( 'OG_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OG_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once OG_WP_PLUGIN_DIR . 'includes/class-og-wp.php';

function og_wp_activate() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'og_wp_audit_log';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		user_id bigint(20) DEFAULT 0 NOT NULL,
		ip_address varchar(45) NOT NULL,
		action varchar(255) NOT NULL,
		details text,
		PRIMARY KEY  (id)
	) $charset_collate;";

	$email_table = $wpdb->prefix . 'og_wp_email_logs';
	$email_sql = "CREATE TABLE IF NOT EXISTS $email_table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		to_email text NOT NULL,
		subject text NOT NULL,
		message longtext NOT NULL,
		headers text,
		attachments text,
		status varchar(50) NOT NULL DEFAULT 'queued',
		provider varchar(100) NOT NULL DEFAULT 'default',
		error_details text,
		retry_count int(11) NOT NULL DEFAULT 0,
		opened_at datetime DEFAULT NULL,
		open_count int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY created_at (created_at)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	dbDelta( $email_sql );

	// Migration: copy old options if they exist and new options don't
	$old_options = get_option( 'lumora_wp_secure_options' );
	$new_options = get_option( 'og_wp_options' );
	if ( $old_options !== false && $new_options === false ) {
		update_option( 'og_wp_options', $old_options );
	}
}
register_activation_hook( __FILE__, 'og_wp_activate' );

function run_og_wp() {
	$plugin = new OG_WP();
	$plugin->run();
}
run_og_wp();
