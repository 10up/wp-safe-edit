<?php
define( 'PHPUNIT_RUNNER', true );

if ( ! file_exists( __DIR__ . '/../../vendor/autoload.php' ) ) {
	throw new PHPUnit_Framework_Exception(
		'ERROR' . PHP_EOL . PHP_EOL .
		'You must use Composer to install the test suite\'s dependencies!' . PHP_EOL
	);
}

require_once __DIR__ . '/../../vendor/autoload.php';

// This loads WP, but we don't want to so we can use WP_Mock:
// https://github.com/10up/wp_mock/issues/47#issuecomment-141786198
//
// $_tests_dir = getenv( 'WP_TESTS_DIR' );
// if ( ! $_tests_dir ) {
// 	$_tests_dir = '/tmp/wordpress-tests-lib';
// }
// require_once $_tests_dir . '/includes/functions.php';
// require_once $_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/test-tools/TestCase.php';

if ( ! defined( 'PROJECT' ) ) {
	define( 'PROJECT', __DIR__ . '/includes/' );
}

if ( ! defined( 'WP_SAFE_EDIT_DIR' ) ) {
	define( 'WP_SAFE_EDIT_DIR', __DIR__ . '/' );
}

// Place any additional bootstrapping requirements here for PHP Unit.
if ( ! defined( 'WP_LANG_DIR' ) ) {
	define( 'WP_LANG_DIR', 'lang_dir' );
}

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();
WP_Mock::tearDown();
