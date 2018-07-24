<?php

namespace TenUp\WPSafeEdit\Posts;

use TenUp\WPSafeEdit\Helpers;
use TenUp\WPSafeEdit\Posts;
use TenUp\WPSafeEdit\Posts\Statuses\DraftForkStatus;
use TenUp\WPSafeEdit\Posts\Statuses\PendingForkStatus;
use TenUp\WPSafeEdit\Posts\Statuses\ArchivedForkStatus;

/**
 * Class to manage custom post statuses.
 */
class Statuses {

	/**
	 * Instance of the DraftForkStatus class
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Statuses\DraftForkStatus
	 */
	protected $draft_status;

	/**
	 * Instance of the PendingForkStatus class
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Statuses\PendingForkStatus
	 */
	protected $pending_status;

	/**
	 * Instance of the ArchivedForkStatus class
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Statuses\ArchivedForkStatus
	 */
	protected $archived_status;

	public function __construct() {
		$this->draft_status    = new DraftForkStatus();
		$this->pending_status  = new PendingForkStatus();
		$this->archived_status = new ArchivedForkStatus();
	}

	/**
	 * Register needed hooks.
	 *
	 * @return void
	 */
	public function register() {
		$this->draft_status->register();
		$this->pending_status->register();
		$this->archived_status->register();

		add_filter(
			'safe_edit_filter_insert_post_data',
			[ $this, 'filter_draft_fork_post_data' ],
			10, 2
		);

		add_filter(
			'the_title',
			array( $this, 'filter_admin_post_list_title' ),
			10, 2
		);
	}

	/**
	 * Get our valid statuses.
	 *
	 * @return array
	 */
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

	/**
	 * Alter the post title for forks shown in the dashboard post lists.
	 *
	 * @param  string $title The post title
	 * @param  int    $id    The post ID
	 * @return string The post title
	 */
	public function filter_admin_post_list_title( $title, $id ) {
		global $pagenow;

		if (
			! is_admin() ||
			'edit.php' !== $pagenow ||
			true !== Posts\post_type_supports_forking( $id )
		) {
			return $title;
		}

		$suffix = '';
		$status = '';

		if ( 'trash' === sanitize_text_field( filter_input( INPUT_GET, 'post_status' ) ) ) {
			$status = get_post_meta( $id, '_wp_trash_meta_status', true );
		} else {
			$status = get_post_status( $id );
		}

		switch ( $status ) {
			case DraftForkStatus::get_name():
				$suffix = esc_html__( '— Draft Revision', 'wp-safe-edit' );
				break;

			case PendingForkStatus::get_name():
				$suffix = esc_html__( '— Pending Draft Revision', 'wp-safe-edit' );
				break;

			case ArchivedForkStatus::get_name():
				$suffix = esc_html__( '— Archived Draft Revision', 'wp-safe-edit' );
				break;

			case 'publish':
				if ( true === Posts\post_has_open_fork( $id ) ) {
					$suffix = esc_html__( '— Draft Revision Pending', 'wp-safe-edit' );
				}

				break;

			default:
				$suffix = '';
				break;
		}

		$suffix = apply_filters( 'safe_edit_admin_post_title_suffix', $suffix, $title, $id );

		if ( empty( $suffix ) ) {
			return $title;
		}

		$title = sprintf(
			'%s %s',
			$title,
			$suffix
		);

		return $title;
	}
}
