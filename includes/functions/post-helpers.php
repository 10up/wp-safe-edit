<?php
namespace TenUp\PostForking\Posts;

use \Exception;
use \InvalidArgumentException;

use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Posts\Statuses\PendingForkStatus;
use \TenUp\PostForking\Posts\Statuses\DraftForkStatus;

/**
 * Determine if a post can be forked.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function post_can_be_forked( $post ) {
	$post = Helpers\get_post( $post );

	try {
		if ( true !== Helpers\is_post( $post ) ) {
			throw new InvalidArgumentException(
				'Post cannot be forked because it is not a valid post object or post ID.'
			);
		}

		if ( true !== post_supports_forking( $post ) ) {
			throw new Exception(
				'Post cannot be forked because the post type does not support forking.'
			);
		}

		if ( true !== in_array( $post->post_status, array( 'publish', 'private' ) ) ) {
			throw new Exception(
				'Post cannot be forked because the post status is not supported.'
			);
		}

		if ( true === is_open_fork( $post ) ) {
			throw new Exception(
				'Post cannot be forked because it is already a fork.'
			);
		}

		if ( true === post_has_open_fork( $post ) ) {
			throw new Exception(
				'Post cannot be forked because a previous fork that is still open.'
			);
		}

		if ( true !== current_user_can_fork_post( $post ) ) {
			throw new Exception(
				'Post cannot be forked because the current user does not have permission.'
			);
		}

		return apply_filters( 'post_forking_post_can_be_forked', true, $post );

	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Determine if a post can be merged.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function post_can_be_merged( $post ) {
	$post = Helpers\get_post( $post );

	try {
		if ( true !== Helpers\is_post( $post ) ) {
			throw new InvalidArgumentException(
				'Post cannot be merged because it is not a valid post object or post ID.'
			);
		}

		if ( true !== post_supports_forking( $post ) ) {
			throw new Exception(
				'Post cannot be merged because the post type does not support forking.'
			);
		}

		if ( true !== is_open_fork( $post ) ) {
			throw new Exception(
				'Post cannot be merged because it is not an open fork.'
			);
		}

		if ( true !== fork_has_source_post( $post ) ) {
			throw new Exception(
				'Post cannot be merged because the source post cannot be found.'
			);
		}

		if ( true !== current_user_can_merge_post( $post ) ) {
			throw new Exception(
				'Post cannot be merged because the current user does not have permission.'
			);
		}

		return apply_filters( 'post_forking_post_can_be_merged', true, $post );

	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Determine if a post has a currently open fork.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function post_has_open_fork( $post ) {
	$fork = get_open_fork_for_post( $post );

	if ( true === Helpers\is_post( $fork ) ) {
		return true;
	}

	return false;
}

/**
 * Determine if a fork has a source post.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function fork_has_source_post( $post ) {
	$source = get_source_post_for_fork( $post );

	if ( true === Helpers\is_post( $source ) ) {
		return true;
	}

	return false;
}

/**
 * Get the current forked version of a post.
 *
 * @param  int|\WP_Post $post
 * @return \WP_Post|null
 */
function get_open_fork_for_post( $post ) {
	$post_id = 0;

	if ( Helpers\is_post( $post ) ) {
		$post_id = $post->ID;
	} elseif ( Helpers\is_valid_post_id( $post ) ) {
		$post_id = absint( $post );
	}

	if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
		return null;
	}

	$args = array(
		'post_type'              => 'any',
		'posts_per_page'         => 1,
		'post_parent'            => $post_id,
		'order'                  => 'DESC',
		'post_status'            => (array) get_open_fork_post_statuses(),
		'no_found_rows'          => true,
	);

	$fork_query = new \WP_Query( $args );

	if ( $fork_query->have_posts() ) {
		return $fork_query->posts[0];
	}

	return null;
}

/**
 * Get the source post for a fork.
 *
 * @param  int|\WP_Post $post
 * @return \WP_Post|null
 */
function get_source_post_for_fork( $post ) {
	$post = Helpers\get_post( $post );

	if ( true !== Helpers\is_post( $post ) ) {
		return null;
	}

	$args = array(
		'p'              => $post->post_parent,
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	);

	$source_query = new \WP_Query( $args );

	if ( $source_query->have_posts() ) {
		return $source_query->posts[0];
	}

	return null;
}

/**
 * Determine if a post supports forking.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function post_supports_forking( $post ) {
	$post_type = get_post_type( $post );

	return true === post_type_supports( $post_type, \TenUp\PostForking\Posts\PostTypeSupport::FORKING_FEATURE_NAME );
}

/**
 * Determine if the current user can fork a post.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function current_user_can_fork_post( $post ) {
	$post = Helpers\get_post( $post );

	if ( true !== Helpers\is_post( $post ) ) {
		return false;
	}

	$post_type = get_post_type_object( $post->post_type );
	$privilege = $post_type->cap->edit_posts;

	$value = current_user_can( $privilege );
	return true === apply_filters( 'post_forking_current_user_can_merge_post', $value, $post );
}

/**
 * Determine if the current user can merge a post.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function current_user_can_merge_post( $post ) {
	$post = Helpers\get_post( $post );

	if ( true !== Helpers\is_post( $post ) ) {
		return false;
	}

	$post_type = get_post_type_object( $post->post_type );
	$privilege = $post_type->cap->publish_posts;

	$value = current_user_can( $privilege );
	return true === apply_filters( 'post_forking_current_user_can_merge_post', $value, $post );
}

/**
 * Get an array of post statuses for forks that have not yet been published or archived.
 *
 * @return array
 */
function get_open_fork_post_statuses() {
	return array(
		DraftForkStatus::NAME,
		PendingForkStatus::NAME,
	);
}

/**
 * Determine if a post is an open fork.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function is_open_fork( $post ) {
	$status        = get_post_status( $post );
	$open_statuses = get_open_fork_post_statuses();

	return in_array( $status, $open_statuses );
}
