<?php

require_once WP_SAFE_EDIT_INC . 'functions/helpers.php';
require_once WP_SAFE_EDIT_INC . 'functions/db-helpers.php';
require_once WP_SAFE_EDIT_INC . 'functions/post-helpers.php';
require_once WP_SAFE_EDIT_INC . 'functions/log-helpers.php';

$composer_autoloader = WP_SAFE_EDIT_PATH . '/vendor/autoload.php';

if ( file_exists( $composer_autoloader ) ) {
	require_once $composer_autoloader;
} else {
	spl_autoload_register(
		function( $class ) {
				// The project-specific namespace prefix.
				$prefix = 'TenUp\\WPSafeEdit\\';

				// The base directory for the namespace prefix.
				$base_dir = __DIR__ . '/includes/';

				// does the class use the namespace prefix?
				$len = strlen( $prefix );

			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, $len );

			$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			// If the file exists, require it.
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);	
}
