<?php
namespace TenUp\WPSafeEdit\Logging;

use \Exception;

/**
 * Log an exception.
 *
 * @param  \Exception $e The exception to log
 * @param  string $message Additional information to log
 */
function log_exception( Exception $e, $message = '' ) {
	if ( method_exists( $e, 'getMessage' ) ) {
		if ( ! empty( $message ) ) {
			$message = sprintf(
				'%s | %s',
				$e->getMessage(),
				$message
			);
		} else {
			$message = $e->getMessage();
		}

		error_log( $message );
	}
}
