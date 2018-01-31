<?php
namespace TenUp\WPSafeEdit;

use \TenUp\WPSafeEdit\API\ForkPostController;
use \TenUp\WPSafeEdit\API\MergePostController;

/**
 * Class to manage API endpoints.
 */
class API {

	public $fork_post_controller;
	public $merge_post_controller;

	public function __construct() {
		$this->fork_post_controller = new ForkPostController();
		$this->merge_post_controller = new MergePostController();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->fork_post_controller->register();
		$this->merge_post_controller->register();
	}
}
