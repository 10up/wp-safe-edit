<?php
namespace TenUp\WPSafeEdit;

use \Exception;
use \InvalidArgumentException;

use \TenUp\WPSafeEdit\Posts\PublishingButtons;
use \TenUp\WPSafeEdit\Posts\Statuses;
use \TenUp\WPSafeEdit\Posts\Notices;
use \TenUp\WPSafeEdit\Posts\ArchivedForks;
use \TenUp\WPSafeEdit\Posts\Trash;

/**
 * Class to manage post integrations.
 */
class Posts {

	const ORIGINAL_POST_ID_META_KEY = 'post-forking-original-post-id';

	/**
	 * Instance of the PublishingButtons class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\PublishingButtons
	 */
	public $publishing_buttons;

	/**
	 * Instance of the Statuses class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Statuses
	 */
	public $statuses;

	/**
	 * Instance of the Notices class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Notices
	 */
	public $notices;

	/**
	 * Instance of the ArchivedForks class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\ArchivedForks
	 */
	public $archived_forks;

	/**
	 * Instance of the Trash class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Trash
	 */
	public $trash;

	public function __construct() {
		$this->publishing_buttons = new PublishingButtons();
		$this->statuses           = new Statuses();
		$this->notices            = new Notices();
		$this->archived_forks     = new ArchivedForks();
		$this->trash              = new Trash();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->publishing_buttons->register();
		$this->statuses->register();
		$this->notices->register();
		$this->archived_forks->register();
		$this->trash->register();

		add_filter(
			'wp_insert_post_data',
			[ $this, 'filter_insert_post_data' ],
			999, 2
		);
	}

	/**
	 * Filter post data before it is saved to the database.
	 *
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 * @return array
	 */
	public function filter_insert_post_data( $data, $postarr ) {
		global $post;

		if ( true !== Helpers\is_post( $post ) ) {
			return $data;
		}

		$valid_statuses = (array) Statuses::get_valid_fork_post_statuses();

		// Bail out if this post isn't a fork.
		if ( empty( $valid_statuses ) || ! in_array( $post->post_status, $valid_statuses ) ) {
			return $data;
		}

		$data = apply_filters( 'post_forking_filter_insert_post_data', $data, $postarr );

		return $data;
	}
}
