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
define( 'FORKIT_PLUGIN_FILE', __FILE__ );

// Include the autoloader.
require_once __DIR__ . '/vendor/autoload.php';

// Set up the plugin.
\TenUp\PostForking\Plugin::get_instance();
