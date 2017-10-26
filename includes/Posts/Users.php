<?php

namespace TenUp\PostForking;

/**
 * Class to manage permissions.
 */
class Users {

	/**
	 * Determine if the current user can fork a post.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean
	 */
	public static function current_user_can_fork_post( $post ) {
		$post = Helpers\get_post( $post );

		if ( true !== Helpers\is_post( $post ) ) {
			return false;
		}

		$post_type  = get_post_type_object( $post->post_type );
		$privilege = $post_type->cap->edit_posts;

		return true === current_user_can( $privilege );
	}
}
