<?php

namespace TenUp\WPSafeEdit\Posts;

use TenUp\WPSafeEdit\Helpers;
use TenUp\WPSafeEdit\Posts;
use TenUp\WPSafeEdit\Posts\Statuses\DraftForkStatus;
use TenUp\WPSafeEdit\Posts\Statuses\PendingForkStatus;
use TenUp\WPSafeEdit\Posts\Statuses\ArchivedForkStatus;

/**
 * Class to manage trashing/deleting.
 */
class Trash {

	/**
	 * Register needed hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action(
			'trashed_post',
			array( $this, 'handle_trashed_post' )
		);

		add_action(
			'untrashed_post',
			array( $this, 'handle_untrashed_post' )
		);
	}

	/**
	 * Handle cleanup when a post is moved to the trash.
	 *
	 * @param int $post_id The ID of the post trashed
	 * @return void
	 */
	public function handle_trashed_post( $post_id ) {
		$this->trash_forks( $post_id );
	}

	/**
	 * Handle cleanup when a post is untrashed.
	 *
	 * @param int $post_id The ID of the post untrashed
	 * @return void
	 */
	public function handle_untrashed_post( $post_id ) {
		$this->untrash_forks( $post_id );
	}

	/**
	 * Trash all forks for a post.
	 *
	 * @param int $post_id The ID of the post to trash the forks for.
	 * @return void
	 */
	public function trash_forks( $post_id ) {
		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return;
		}

		$forks_query = Posts\get_all_forks_for_post(
			$post_id,
			array(
				'posts_per_page' => 500 // A safe, but hopefully adequate max.
			)
		);

		if ( true !== $forks_query->have_posts() ) {
			return;
		}

		foreach ( $forks_query->posts as $fork ) {
			wp_trash_post( $fork->ID );
		}
	}

	/**
	 * Untrash all forks for a post.
	 *
	 * @param int $post_id The ID of the post to untrash the forks for.
	 * @return void
	 */
	public function untrash_forks( $post_id ) {
		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return;
		}
	}
}
