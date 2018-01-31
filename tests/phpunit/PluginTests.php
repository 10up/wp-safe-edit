<?php
namespace TenUp\WPSafeEdit;

use TenUp\WPSafeEdit as Base;

class PluginTests extends Base\TestCase {

	public function test_get_instance() {
		$instance = Plugin::get_instance();

		$this->assertTrue( $instance instanceof Plugin );
	}

	public function test_register() {
		\WP_Mock::expectAction( 'safe_edit_loaded' );

		$instance = Plugin::get_instance();

		\WP_Mock::expectActionAdded( 'init', array( $instance, 'i18n' ) );
		\WP_Mock::expectActionAdded( 'init', array( $instance, 'init' ) );

		$instance->register();

		$this->assertConditionsMet();
	}

	public function test_init() {
		\WP_Mock::expectAction( 'safe_edit_init' );

		$instance = Plugin::get_instance();
		$instance->init();
	}
}
