<?php
namespace TenUp\PostForking;

use TenUp\PostForking as Base;

class PluginTests extends Base\TestCase {

	public function test_get_instance() {
		$instance = Plugin::get_instance();

		$this->assertTrue( $instance instanceof Plugin );
	}

	public function test_register() {
		\WP_Mock::expectAction( 'post_forking_loaded' );

		$instance = Plugin::get_instance();

		\WP_Mock::expectActionAdded( 'init', array( $instance, 'i18n' ) );
		\WP_Mock::expectActionAdded( 'init', array( $instance, 'init' ) );

		$instance->register();

		$this->assertConditionsMet();
	}

	public function test_init() {
		\WP_Mock::expectAction( 'post_forking_init' );

		$instance = Plugin::get_instance();
		$instance->init();
	}
}
