<?php

namespace TenUp\PostForking\Posts;

use TenUp\PostForking\Helpers;
use TenUp\PostForking\Posts\Statuses\DraftForkStatus;
use TenUp\PostForking\Posts\Statuses\PendingForkStatus;
use TenUp\PostForking\Posts\Statuses\ArchivedForkStatus;

/**
 * Class to manage custom post statuses.
 */
class Statuses {

	/**
	 * Instance of the DraftForkStatus class
	 *
	 * @var \TenUp\PostForking\Posts\Statuses\DraftForkStatus
	 */
	protected $draft_status;

	/**
	 * Instance of the PendingForkStatus class
	 *
	 * @var \TenUp\PostForking\Posts\Statuses\PendingForkStatus
	 */
	protected $pending_status;

	/**
	 * Instance of the ArchivedForkStatus class
	 *
	 * @var \TenUp\PostForking\Posts\Statuses\ArchivedForkStatus
	 */
	protected $archived_status;

	public function __construct() {
		$this->draft_status    = new DraftForkStatus();
		$this->pending_status  = new PendingForkStatus();
		$this->archived_status = new ArchivedForkStatus();
	}

	public function register() {
		$this->draft_status->register();
		$this->pending_status->register();
		$this->archived_status->register();

		add_filter(
			'post_forking_filter_insert_post_data',
			[ $this, 'filter_draft_fork_post_data' ],
			10, 2
		);
	}

	public static function get_valid_fork_post_statuses() {
		return array(
			DraftForkStatus::get_name(),
			PendingForkStatus::get_name(),
			ArchivedForkStatus::get_name(),
		);
	}

	/**
	 * Filter post data when saving a draft of a fork. This keeps the post status the same instead of applying the default "pending" status for drafts.
	 *
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 * @return array
	 */
	public function filter_draft_fork_post_data( $data, $postarr ) {
		global $post;

		if ( true !== Helpers\is_post( $post ) ) {
			return $data;
		}

		if ( empty( $postarr['post_status'] ) ) {
			return $data;
		}

		// If saving a draft of a fork, keep the same post status.
		$draft_fork_post_status = DraftForkStatus::get_name();
		if (
			'pending' === $postarr['post_status'] &&
			$draft_fork_post_status === $post->post_status
		 ) {
			$data['post_status'] = $draft_fork_post_status;
		}

		return $data;
	}
}
