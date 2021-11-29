<?php
/**
 * Plugin Name: WP Safe Edit
 * Plugin URI:  https://github.com/10up/WP-Safe-Edit
 * Description: Edit published posts safely behind the scenes and publish the changes when ready.
 * Version:     0.1.0
 * Author:      Michael Phillips
 * Author URI:
 * Text Domain: wp-safe-edit
 * Domain Path: /languages
 * License:     MIT
 * Update URI:  https://github.com/10up/wp-safe-edit
 * 
 */

// Useful global constants
define( 'WP_SAFE_EDIT_VERSION',     '0.1.0' );
define( 'WP_SAFE_EDIT_URL',         plugin_dir_url( __FILE__ ) );
define( 'WP_SAFE_EDIT_PATH',        dirname( __FILE__ ) . '/' );
define( 'WP_SAFE_EDIT_INC',         WP_SAFE_EDIT_PATH . 'includes/' );

// Include the autoloader.
require_once WP_SAFE_EDIT_PATH . '/autoload.php';

try {
	register_activation_hook( __FILE__, '\\TenUp\WPSafeEdit\Plugin::activate' );
	register_deactivation_hook( __FILE__, '\\TenUp\WPSafeEdit\Plugin::deactivate' );

	// Set up the plugin.
	\TenUp\WPSafeEdit\Plugin::get_instance();
} catch ( Exception $e ) {
	// Log all uncaught exceptions.
	\TenUp\WPSafeEdit\Logging\log_exception( $e );
}
