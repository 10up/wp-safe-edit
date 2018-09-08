<?php
namespace TenUp\WPSafeEdit\Posts;

use \Exception;
use \InvalidArgumentException;

use \TenUp\WPSafeEdit\Helpers;
use \TenUp\WPSafeEdit\Posts;
use \TenUp\WPSafeEdit\Posts\Statuses;
use \TenUp\WPSafeEdit\Posts\PostTypeSupport;
use \TenUp\WPSafeEdit\Posts\Statuses\PendingForkStatus;
use \TenUp\WPSafeEdit\Posts\Statuses\DraftForkStatus;
use TenUp\WPSafeEdit\Posts\Statuses\ArchivedForkStatus;

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
				esc_html__( 'Post cannot be forked because it is not a valid post object or post ID.', 'wp-safe-edit' )
			);
		}

		if ( true !== post_type_supports_forking( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be forked because the post type does not support forking.', 'wp-safe-edit' )
			);
		}

		if ( true !== in_array( $post->post_status, array( 'publish', 'private' ) ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be forked because the post status is not supported.', 'wp-safe-edit' )
			);
		}

		if ( true === is_open_fork( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be forked because it is already a fork.', 'wp-safe-edit' )
			);
		}

		if ( true === post_has_open_fork( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be forked because a previous fork that is still open.', 'wp-safe-edit' )
			);
		}

		if ( true !== current_user_can_fork_post( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be forked because the current user does not have permission.', 'wp-safe-edit' )
			);
		}

		return apply_filters( 'safe_edit_post_can_be_forked', true, $post );

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
				esc_html__( 'Post cannot be merged because it is not a valid post object or post ID.', 'wp-safe-edit' )
			);
		}

		if ( true !== post_type_supports_forking( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be merged because the post type does not support forking.', 'wp-safe-edit' )
			);
		}

		if ( true !== is_open_fork( $post ) && 'publish' !== $post->post_status ) {
			throw new Exception(
				esc_html__( 'Post cannot be merged because it is not an open fork.', 'wp-safe-edit' )
			);
		}

		if ( true !== fork_has_source_post( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be merged because the source post cannot be found.', 'wp-safe-edit' )
			);
		}

		if ( true !== current_user_can_merge_post( $post ) ) {
			throw new Exception(
				esc_html__( 'Post cannot be merged because the current user does not have permission.', 'wp-safe-edit' )
			);
		}

		return apply_filters( 'safe_edit_post_can_be_merged', true, $post );

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
		'order'                  => 'DESC',
		'orderby'                => 'modified', // Important to order by the modified date because the published date won't change when a post is updated.
		'post_status'            => (array) get_open_fork_post_statuses(),
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'meta_query'             => array(
			array(
				'key'   => Posts::ORIGINAL_POST_ID_META_KEY,
				'value' => $post_id,
			),
		),
	);

	$fork_query = new \WP_Query( $args );

	if ( $fork_query->have_posts() ) {
		return $fork_query->posts[0];
	}

	return null;
}

/**
 * Get the WP_Query object for all forks (open and archived) for a post.
 *
 * @param  int|\WP_Post $post
 * @param  array $query_args Args to pass to WP_Query
 * @return \WP_Query|null
 */
function get_all_forks_for_post( $post, $query_args = array() ) {
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
		'post_status'            => (array) Statuses::get_valid_fork_post_statuses(),
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'meta_query'             => array(
			array(
				'key'   => Posts::ORIGINAL_POST_ID_META_KEY,
				'value' => $post_id,
			),
		),
	);

	if ( ! empty( $query_args ) && is_array( $query_args ) ) {
		$args = array_merge( $args, $query_args );
	}

	$fork_query = new \WP_Query( $args );

	return $fork_query;
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

	$original_post_id = get_original_post_id_for_fork( $post );

	if ( true !== Helpers\is_valid_post_id( $original_post_id ) ) {
		return null;
	}

	$args = array(
		'p'                   => absint( $original_post_id ),
		'post_type'           => 'any',
		'posts_per_page'      => 1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
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
function post_type_supports_forking( $post ) {
	$post_type = get_post_type( $post );

	return true === post_type_supports( $post_type, \TenUp\WPSafeEdit\Posts\PostTypeSupport::FORKING_FEATURE_NAME );
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
	
	// First determine if the user can edit published posts.
	$edit_published_privilege = $post_type->cap->edit_published_posts;
	$value                    = current_user_can( $edit_published_privilege );

	// If the user can edit published posts, also determine if the user can edit the fork post by ID.
	if ( true === $value ) {
		$edit_post_privilege = $post_type->cap->edit_post;
		$value               = current_user_can( $edit_post_privilege, $post->ID );
	}

	return true === apply_filters( 'safe_edit_current_user_can_fork_post', $value, $post );
}

/**
 * Determine if the current user can edit a fork.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function current_user_can_edit_fork( $post ) {
	$value = current_user_can_fork_post( $post );
	return true === apply_filters( 'safe_edit_current_user_can_edit_fork', $value, $post );
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
	
	// First determine if the user can publish posts.
	$published_privilege = $post_type->cap->publish_posts;
	$value               = current_user_can( $published_privilege );

	// As an extra level of security, also determine if the user can edit the post by ID.
	if ( true === $value ) {
		$edit_post_privilege = $post_type->cap->edit_post;
		$value               = current_user_can( $edit_post_privilege, $post->ID );
	}

	return true === apply_filters( 'safe_edit_current_user_can_merge_post', $value, $post );
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

/**
 * Determine if a post is an archived fork.
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function is_archived_fork( $post ) {
	$status= get_post_status( $post );

	return $status === ArchivedForkStatus::get_name();
}

/**
 * Determine if a post is a fork (any valid fork status).
 *
 * @param  int|\WP_Post $post
 * @return boolean
 */
function is_fork( $post ) {
	$status         = get_post_status( $post );
	$valid_statuses = (array) Statuses::get_valid_fork_post_statuses();

	return in_array( $post->post_status, $valid_statuses );
}

/**
 * Save the original post ID for a fork.
 *
 * @param int|\WP_Post $forked_post The fork
 * @param int|\WP_Post $original_post The original post
 */
function set_original_post_id_for_fork( $forked_post, $original_post ) {
	try {
		$forked_post_id   = $forked_post;
		$original_post_id = $original_post;

		if ( true === Helpers\is_post( $forked_post ) ) {
			$forked_post_id = $forked_post->ID;
		}

		if ( true === Helpers\is_post( $original_post ) ) {
			$original_post_id = $original_post->ID;
		}

		if (
			true !== Helpers\is_valid_post_id( $forked_post_id ) ||
			true !== Helpers\is_valid_post_id( $original_post_id )
		) {
			throw new Exception(
				esc_html__( 'Could not set the original post ID for a fork because the fork or original post were invalid.', 'wp-safe-edit' )
			);
		}

		add_post_meta(
			absint( $forked_post_id ),
			Posts::ORIGINAL_POST_ID_META_KEY,
			absint( $original_post_id ),
			true
		);

	} catch ( \Exception $e ) {
		return false;
	}
}

/**
 * Get the original post ID for a fork.
 *
 * @param  int|\WP_Post $forked_post The fork
 *
 * @return int
 */
function get_original_post_id_for_fork( $forked_post ) {
	try {
		if ( true === Helpers\is_post( $forked_post ) ) {
			$forked_post = $forked_post->ID;
		}

		if ( true !== Helpers\is_valid_post_id( $forked_post ) ) {
			throw new Exception(
				esc_html__( 'Could not get the original post ID for a fork because the fork was invalid.', 'wp-safe-edit' )
			);
		}

		return get_post_meta(
			absint( $forked_post ),
			Posts::ORIGINAL_POST_ID_META_KEY,
			true
		);

	} catch ( \Exception $e ) {
		return false;
	}
}

/**
 * Get an array of post types that support forking.
 *
 * @return array
 */
function get_forkable_post_types() {
	return get_post_types_by_support( PostTypeSupport::FORKING_FEATURE_NAME );
}

/**
 * Get the archived forks query for a post.
 *
 * @param int|\WP_Post $post The post to get the archived forks for
 * @param array $query_args Array of query args
 *
 * @return \WP_Query|null
 */
function get_archived_forks_query( $post, $query_args = array() ) {
	$post = Helpers\get_post( $post );

	if ( true !== Helpers\is_post( $post ) ) {
		return null;
	}

	$args = array(
		'post_type'           => $post->post_type,
		'posts_per_page'      => 10,
		'order'               => 'DESC',
		'orderby'             => 'modified', // Important to order by the modified date because the published date won't change when a post is updated.
		'post_status'         => ArchivedForkStatus::NAME,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			array(
				'key'   => Posts::ORIGINAL_POST_ID_META_KEY,
				'value' => $post->ID,
			),
		),
	);

	if ( is_array( $query_args ) || ! empty( $query_args ) ) {
		$args = array_merge( $args, $query_args );
	}

	return new \WP_Query( $args );
}

/**
 * Determine if a post has at least one archived fork.
 *
 * @param int|\WP_Post $post The post to get the archived forks for
 * @return boolean
 */
function post_has_archived_forks( $post ) {
	$archived_forks_query = get_archived_forks_query( $post, array( 'posts_per_page' => 1 ) );

	if ( true === $archived_forks_query->have_posts() ) {
		return true;
	}

	return false;
}
