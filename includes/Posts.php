<?php
namespace TenUp\PostForking;

use \Exception;
use \InvalidArgumentException;

use \TenUp\PostForking\Posts\PublishingButtons;

/**
 * Class to manage post integrations.
 */
class Posts {

	/**
	 * Instance of the PublishingButtons class;
	 *
	 * @var \TenUp\PostForking\Posts\PublishingButtons
	 */
	public $publishing_buttons;

	public function __construct() {
		$this->publishing_buttons = new PublishingButtons();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->publishing_buttons->register();
	}
}
