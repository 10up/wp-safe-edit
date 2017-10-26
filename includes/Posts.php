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

	/**
	 * Determine if a post can be forked.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean
	 */
	public static function post_can_be_forked( $post ) {
		if ( true !== Helpers\is_post_or_post_id( $post ) ) {
			throw new InvalidArgumentException(
				'Post could not be forked because it is not a valid post object or post ID.'
			);
		}

		if ( true === static::post_has_fork( $post ) ) {
			throw new Exception(
				'Post could not be forked because a previous fork is still being edited.'
			);
		}

		return apply_filters( 'post_forking_post_can_be_forked', true, $post );
	}

	/**
	 * Determine if a post has been forked.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean
	 */
	public static function post_has_fork( $post ) {
		die( var_dump( 'Implement has_fork()' ) );
		return false;
	}
}
