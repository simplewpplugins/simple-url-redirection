<?php
/**
 * Plugin Name:       Simple URL Redirection
 * Plugin URI:        https://wordpress.org/plugins/simple-url-redirection/
 * Description:       Manage URL redirects, import and export CSV mappings, monitor 404 errors, and review suggested destination matches.
 * Version:           1.0.0
 * Author:            Simple Plugins
 * Author URI:        https://github.com/simplewpplugins
 * License:           GPL v2 or later
 * Text Domain:       simple-url-redirection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'SIMPLE_URL_REDIRECTION_VERSION', '1.0.0' );
define( 'SIMPLE_URL_REDIRECTION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_URL_REDIRECTION_URL', plugin_dir_url( __FILE__ ) );

require_once SIMPLE_URL_REDIRECTION_PATH . 'includes/class-simple-url-redirection-db.php';
require_once SIMPLE_URL_REDIRECTION_PATH . 'includes/class-simple-url-redirection-importer.php';
require_once SIMPLE_URL_REDIRECTION_PATH . 'includes/class-simple-url-redirection-matcher.php';
require_once SIMPLE_URL_REDIRECTION_PATH . 'includes/class-simple-url-redirection-redirector.php';
require_once SIMPLE_URL_REDIRECTION_PATH . 'includes/class-simple-url-redirection-admin.php';

/**
 * Activation: create custom tables.
 */
function sur_redirects_activate() {
	SUR_DB::create_tables();
	if ( false === get_option( 'sur_redirects_settings' ) ) {
		add_option(
			'sur_redirects_settings',
			array(
				'canonical_host'    => '', // auto-detected from home_url() if empty
				'force_https'       => 0,
				'enable_fuzzy'      => 0,
				'fuzzy_threshold'   => 55, // percent similarity 0-100
				'fuzzy_redirect_status' => 302, // suggested matches use 302 until approved
			)
		);
	}
}
register_activation_hook( __FILE__, 'sur_redirects_activate' );

/**
 * Boot the plugin.
 */
function sur_redirects_init() {
	new SUR_Redirector();
	if ( is_admin() ) {
		new SUR_Admin();
	}
}
add_action( 'plugins_loaded', 'sur_redirects_init' );
