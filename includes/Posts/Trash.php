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

	public function register() {
		add_action(
			'trashed_post',
			array( $this, 'handle_trashed_post' )
		);

		add_action(
			'untrashed_post',
			array( $this, 'handle_untrashed_post' )
		);

		add_filter(
			'the_title',
			array( $this, 'filter_admin_post_list_title' ),
			10, 2
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
	 * @param  int $post_id The ID of the post to untrash the forks for.
	 */
	public function untrash_forks( $post_id ) {
		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return;
		}
	}

	/**
	 * Alter the post title for forks shown in the admin trash post list.
	 *
	 * @param  string $title The psot title
	 * @param  int $id The post ID
	 * @return string The post title
	 */
	public function filter_admin_post_list_title( $title, $id ) {
		global $pagenow;

		if (
			! is_admin() ||
			'edit.php' !== $pagenow ||
			'trash' !== sanitize_text_field( filter_input( INPUT_GET, 'post_status' ) ) // Only alter the post title when viewing the trash, since forks to not show up on the other list views.
		) {
			return $title;
		}

		$prefix = '';
		$previous_status = get_post_meta( $id, '_wp_trash_meta_status', true );

		switch ( $previous_status ) {
			case DraftForkStatus::get_name():
				$prefix = __( 'draft fork', 'wp-safe-edit' );
				break;

			case PendingForkStatus::get_name():
				$prefix = __( 'pending fork', 'wp-safe-edit' );
				break;

			case ArchivedForkStatus::get_name():
				$prefix = __( 'archived fork', 'wp-safe-edit' );
				break;

			default:
				$prefix = '';
				break;
		}

		$prefix = apply_filters( 'post_forking_admin_post_title_prefix', $prefix, $title, $id );

		if ( empty( $prefix ) ) {
			return $title;
		}

		$title = sprintf(
			'%s (%s)',
			$title,
			$prefix
		);

		return $title;
	}
}
