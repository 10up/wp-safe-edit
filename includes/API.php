<?php
namespace TenUp\PostForking;

/**
 * Class to manage API endpoints.
 */
class API {

	public function __construct() {

	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		add_action(
			'rest_api_init',
			array( $this, 'register_endpoints' )
		);
	}
}
