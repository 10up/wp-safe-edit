<?php

namespace TenUp\PostForking\Posts;

use TenUp\PostForking\Helpers;
use TenUp\PostForking\Posts;

/**
 * Class to manage trashing/deleting.
 */
class Trash {

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
	 * @param  int $post_id The ID of the post trashed
	 */
	public function handle_trashed_post( $post_id ) {
		$this->trash_forks( $post_id );
	}

	/**
	 * Handle cleanup when a post is untrashed.
	 *
	 * @param  int $post_id The ID of the post untrashed
	 */
	public function handle_untrashed_post( $post_id ) {
		$this->untrash_forks( $post_id );
	}

	/**
	 * Trash all forks for a post.
	 *
	 * @param  int $post_id The ID of the post to trash the forks for.
	 */
	public function trash_forks( $post_id ) {
		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return;
		}

		$forks_query = Posts\get_all_forks_for_post(
			$post_id,
			array(
				'posts_per_page' => 500 // An adequite, but hopefully safe, max
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
	 * @param  int $post_id The ID of the post to untrash the forks for.
	 */
	public function untrash_forks( $post_id ) {
		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return;
		}
	}
}
