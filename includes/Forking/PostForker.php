<?php
namespace TenUp\PostForking\Forking;

use \Exception;
use \InvalidArgumentException;
use \WP_Error;

use \TenUp\PostForking\Posts;
use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Posts\Statuses\DraftForkStatus;

/**
 * Class to manage post forking.
 */
class PostForker {

	/**
	 * Fork a post along with it's meta data and taxonomy associations.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean|\WP_Error
	 */
	public function fork( $post ) {
		try {
			if ( true !== $this->can_fork( $post ) ) {
				throw new Exception(
					'Post could not be forked.'
				);
			}

			$forked_post_id = $this->fork_post( $post );

			if ( true !== Helpers\is_valid_post_id( $forked_post_id ) ) {
				throw new Exception(
					'Post could not be forked.'
				);
			}

			do_action( 'post_forking_post_forked', $forked_post_id, $post );

			return $forked_post_id;

		} catch ( Exception $e ) {
			return new WP_Error(
				'post_forker',
				$e->getMessage()
			);
		}
	}

	/**
	 * Copy the post meta from the original post to the forked post.
	 *
	 * @param  int|\WP_Post $post The original post ID or object
	 * @param  int|\WP_Post $forked_post The forked post ID or object
	 * @return int|boolean The number of post meta rows copied if successful; false if not.
	 */
	public function copy_post_meta( $post, $forked_post ) {
		$post        = Helpers\get_post( $post );
		$forked_post = Helpers\get_post( $forked_post );

		if (
			true !== Helpers\is_post( $post ) ||
			true !== Helpers\is_post( $forked_post )
		) {
			throw new InvalidArgumentException(
				'Could not fork post meta because the posts given were not valid.'
			);
		}

		$result = Helpers\clear_post_meta( $forked_post ); // Clear any existing meta data first to prevent duplicate rows for the same meta keys.

		$result = Helpers\copy_post_meta( $post, $forked_post );

		return $result;
	}

	/**
	 * Copy the taxonomy terms from the original post to the forked post.
	 *
	 * @param  int|\WP_Post $post The original post ID or object
	 * @param  int|\WP_Post $forked_post The forked post ID or object
	 * @return int|boolean The number of taxonomy terms copied to the destination post if successful; false if not.
	 */
	public function copy_post_terms( $post, $forked_post ) {
		$post        = Helpers\get_post( $post );
		$forked_post = Helpers\get_post( $forked_post );

		if (
			true !== Helpers\is_post( $post ) ||
			true !== Helpers\is_post( $forked_post )
		) {
			throw new InvalidArgumentException(
				'Could not fork post terms because the posts given were not valid.'
			);
		}

		$post_type  = get_post_type( $post );
		$taxonomies = get_object_taxonomies( $post_type, 'names' );
		$count      = 0;

		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return false;
		}

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms(
				$post->ID,
				$taxonomy,
				array( 'fields' => 'ids' )
			);

			if ( empty( $terms ) ) {
				continue;
			}

			wp_set_object_terms( $forked_post->ID, $terms, $taxonomy, false );

			$count += count( $terms );
		}

		return $count;
	}

	/**
	 * Fork post data.
	 *
	 * @param  int|\WP_Post $post
	 * @return int|\WP_Error The forked post ID, if successful.
	 */
	public function fork_post( $post ) {
		$post = Helpers\get_post( $post );

		if ( true !== Helpers\is_post_or_post_id( $post ) ) {
			throw new InvalidArgumentException(
				'Post could not be forked because it is not a valid post object or post ID.'
			);
		}

		$forked_post = $this->prepare_forked_post_data( $post );
		$forked_post_id = wp_insert_post( $forked_post, true );

		if ( is_wp_error( $forked_post_id ) ) {
			throw new Exception(
				'Post could not be forked: ' . $forked_post_id->get_error_message()
			);
		}

		if ( true !== Helpers\is_valid_post_id( $forked_post_id ) ) {
			throw new Exception(
				'Post could not be forked.'
			);
		}

		$this->copy_post_meta( $post, $forked_post_id );
		$this->copy_post_terms( $post, $forked_post_id );

		return $forked_post_id;

	}

	/**
	 * Prepare the post data for a forked post.
	 *
	 * @param  int|\WP_Post $post The post ID or object we're forking.
	 * @return \WP_Post The post data for the forked post.
	 */
	public function prepare_forked_post_data( $post ) {
		$post = Helpers\get_post( $post );

		if ( true !== Helpers\is_post_or_post_id( $post ) ) {
			throw new InvalidArgumentException(
				'Could not prepare the forked post data because the original post is not a valid post object or post ID.'
			);
		}

		$post_status = $this->get_draft_fork_post_status();
		if ( empty( $post_status ) ) {
			throw new Exception(
				'Could not prepare the forked post data because the correct post status could not be determined.'
			);
		}

		$forked_post = $post;

		$excluded_columns = $this->get_columns_to_exclude();
		foreach ( (array) $excluded_columns as $column ) {
			if ( array_key_exists( $column, $forked_post ) ) {
				unset( $forked_post[ $column ] );
			}
		}

		$forked_post['post_parent'] = $post->id;
		$forked_post['post_status'] = $post_status;

		return $forked_post;
	}

	public function get_draft_fork_post_status() {
		return DraftForkStatus::get_name();
	}

	/**
	 * Get the columns that should be ignored when forking a post.
	 *
	 * @return array
	 */
	public function get_columns_to_exclude() {
		return array(
			'ID',
			'post_date',
			'post_date_gmt',
			'post_parent',
			'post_modified',
			'post_modified_gmt',
			'guid',
			'post_category',
			'tags_input',
			'tax_input',
		);
	}

	/**
	 * Determine if a post can be forked.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean
	 */
	public function can_fork( $post ) {
		return true === Posts::post_can_be_forked( $post );
	}
}
