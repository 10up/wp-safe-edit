<?php

namespace TenUp\PostForking\Posts;

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
		$this->draft_status = new DraftForkStatus();
		$this->pending_status = new DraftForkStatus();
		$this->archived_status = new DraftForkStatus();
	}

	public function register() {
		$this->draft_status->register();
		$this->pending_status->register();
		$this->archived_status->register();
	}
}
