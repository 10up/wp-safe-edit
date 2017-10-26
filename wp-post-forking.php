<?php
/**
 * Plugin Name: WP Post Forking
 * Plugin URI:  https://github.com/10up/post-forking
 * Description: A WordPress plugin that enables the ability to edit a published post behind the scene, and publish the changes when ready.
 * Version:     0.1.0
 * Author:      Michael Phillips
 * Author URI:
 * Text Domain: forkit
 * Domain Path: /languages
 * License:     MIT
 */

// Useful global constants
define( 'FORKIT_VERSION',     '0.1.0' );
define( 'FORKIT_URL',         plugin_dir_url( __FILE__ ) );
define( 'FORKIT_PATH',        dirname( __FILE__ ) . '/' );
define( 'FORKIT_INC',         FORKIT_PATH . 'includes/' );

// Include the autoloader.
require_once __DIR__ . '/vendor/autoload.php';

try {
	register_activation_hook( __FILE__, '\TenUp\PostForking\Plugin::activate' );
	register_deactivation_hook( __FILE__, '\\TenUp\PostForking\Plugin::deactivate' );

	// Set up the plugin.
	\TenUp\PostForking\Plugin::get_instance();
} catch ( Exception $e ) {
	// Log all uncaught exceptions.
	trigger_error( $e->getMessage(), E_USER_WARNING );
}
