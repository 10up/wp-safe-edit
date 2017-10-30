<?php
namespace TenUp\PostForking;

use \TenUp\PostForking\API\ForkPostController;

/**
 * Class to manage API endpoints.
 */
class API {

	public $fork_post_controller;

	public function __construct() {
		$this->fork_post_controller = new ForkPostController();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->fork_post_controller->register();
	}
}
