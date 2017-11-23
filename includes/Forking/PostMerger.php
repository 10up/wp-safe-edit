<?php
namespace TenUp\PostForking\Forking;

use \Exception;
use \InvalidArgumentException;
use \WP_Error;

use \TenUp\PostForking\Posts;
use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Forking\AbstractMerger;
use \TenUp\PostForking\Posts\Statuses\ArchivedForkStatus;

/**
 * Class to manage post merging.
 */
class PostMerger extends AbstractMerger  {

	/**
	 * Update a fork's source post with the post data and terms of the fork.
	 *
	 * @param  int|\WP_Post $fork The fork to merge into the source post.
	 * @return boolean|\WP_Error
	 */
	public function merge( $fork ) {
		try {
			if ( true !== $this->can_merge( $fork ) ) {
				throw new Exception(
					'Post could not be merged.'
				);
			}

			$result = $this->merge_post( $fork );

			if ( true !== Helpers\is_valid_post_id( $result ) ) {
				throw new Exception(
					'Post could not be merged.'
				);
			}

			return $result;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return new WP_Error(
				'post_merger',
				$e->getMessage()
			);
		}
	}

	/**
	 * Merge post data.
	 *
	 * @param  int|\WP_Post $fork
	 * @return int|\WP_Error The forked post ID, if successful.
	 */
	public function merge_post( $fork ) {
		try {
			$fork = Helpers\get_post( $fork );

			if ( true !== Helpers\is_post( $fork ) ) {
				throw new InvalidArgumentException(
					'Post could not be merged because it is not a valid post object or post ID.'
				);
			}

			$source_post = Posts\get_source_post_for_fork( $fork );

			if ( true !== Helpers\is_post( $source_post ) ) {
				throw new Exception(
					'Post could not be merged because the source post could not be found.'
				);
			}

			do_action( 'post_forking_before_merge_post', $fork, $source_post );

			$original_post_data   = $_POST;
			$original_post_data   = $this->prepare_post_data( $post_data );

			if ( ! is_array( $original_post_data ) || empty( $original_post_data ) ) {
				throw new Exception(
					'Post could not be merged because the post data was invalid.'
				);
			}

			// Update the source post with the data from the forked post.
			$new_source_post_data = $this->prepare_post_data_for_merge( $fork, $source_post, $original_post_data );
			$merge_post_id = wp_update_post( $new_source_post_data, true );

			if ( is_wp_error( $merge_post_id ) ) {
				throw new Exception(
					'Post could not be merged: ' . $merge_post_id->get_error_message()
				);
			}

			if ( true !== Helpers\is_valid_post_id( $merge_post_id ) ) {
				throw new Exception(
					'Post could not be merged.'
				);
			}

			$this->copy_post_meta( $fork, $merge_post_id );
			$this->copy_post_terms( $fork, $merge_post_id );

			$this->archive_forked_post( $fork->ID, $original_post_data );

			clean_post_cache( $source_post->ID );

			do_action( 'post_forking_after_merge_post', $fork, $source_post );

			return $merge_post_id;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return new WP_Error(
				'post_merger',
				$e->getMessage()
			);
		}
	}

	/**
	 * Prepare an array of post data so it can be saved to the database.
	 *
	 * @param  array $post_data Array of post data to prepare.
	 * @return array The prepared post data.
	 */
	public function prepare_post_data( $post_data ) {
		try {
			// Make sure the post data contains the correct keys for the DB post columns. This is needed in case $_POST data is used where the form fields don't all match the DB columns.
			$post_data = _wp_translate_postdata( false, $post_data );

			if ( empty( $post_data ) || ! is_array( $post_data ) ) {
				throw new InvalidArgumentException(
					'Could not prepare the post data to merging because it was invalid.'
				);
			}

			return $post_data;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return array();
		}
	}

	/**
	 * Prepare the fork's post data to be merged into its source post.
	 *
	 * @param  int|\WP_Post $fork The post ID or object we're merging.
	 * @param  int|\WP_Post $source_post The post ID or object of the fork's source post.
	 * @param  array $post_data Array of post data to use for the merge.
	 * @return array The post data for the merged post.
	 */
	public function prepare_post_data_for_merge( $fork, $source_post, $post_data ) {
		try {
			$fork = Helpers\get_post( $fork );

			if ( true !== Helpers\is_post( $fork ) ) {
				throw new InvalidArgumentException(
					'Could not prepare the forked post data to merge because the fork is not a valid post object or post ID.'
				);
			}

			$source_post = Helpers\get_post( $source_post );

			if ( true !== Helpers\is_post( $source_post ) ) {
				throw new InvalidArgumentException(
					'Could not prepare the forked post data to merge because the source post is not a valid post object or post ID.'
				);
			}

			$post_data = $this->prepare_post_data( $post_data );

			$excluded_columns = $this->get_columns_to_exclude();
			foreach ( (array) $excluded_columns as $column ) {
				if ( array_key_exists( $column, $post_data ) ) {
					unset( $post_data[ $column ] );
				}
			}

			$post_data['ID']          = $source_post->ID;
			$post_data['post_status'] = Helpers\get_property( 'post_status', $source_post );

			return $post_data;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return array();
		}
	}

	/**
	 * Copy the post meta from the forked post to the source post.
	 *
	 * @param  int|\WP_Post $forked_post The forked post ID or object
	 * @param  int|\WP_Post $source_post The original post ID or object
	 * @return int|\WP_Error The number of post meta rows copied if successful.
	 */
	public function copy_post_meta( $forked_post, $source_post ) {
		try {
			$forked_post = Helpers\get_post( $forked_post );

			$source_post = Helpers\get_post( $source_post );

			if (
				true !== Helpers\is_post( $source_post ) ||
				true !== Helpers\is_post( $forked_post )
			) {
				throw new InvalidArgumentException(
					'Could not merge post meta because the posts given were not valid.'
				);
			}

			$result = Helpers\clear_post_meta( $source_post ); // Clear any existing meta data first to prevent duplicate rows for the same meta keys.

			do_action( 'post_forking_before_merge_post_meta', $source_post, $forked_post );

			$excluded_keys = $this->get_meta_keys_to_exclude();
			$result = Helpers\copy_post_meta( $forked_post, $source_post, $excluded_keys );

			do_action( 'post_forking_after_merge_post_meta', $source_post, $forked_post, $result );

			return $result;
		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return new WP_Error(
				'post_merger',
				$e->getMessage()
			);
		}
	}

	/**
	 * Copy the taxonomy terms from the forked post to the source post.
	 *
	 * @param  int|\WP_Post $forked_post The forked post ID or object
	 * @param  int|\WP_Post $source_post The original post ID or object
	 *
	 * @return int|\WP_Error The number of taxonomy terms copied to the destination post if successful.
	 */
	public function copy_post_terms( $forked_post, $source_post ) {
		try {
			$source_post = Helpers\get_post( $source_post );
			$forked_post = Helpers\get_post( $forked_post );

			if (
				true !== Helpers\is_post( $source_post ) ||
				true !== Helpers\is_post( $forked_post )
			) {
				throw new InvalidArgumentException(
					'Could not merge post terms because the posts given were not valid.'
				);
			}

			do_action( 'post_forking_before_merge_post_terms', $source_post, $forked_post );

			$result = Helpers\copy_post_terms( $forked_post, $source_post );

			do_action( 'post_forking_after_merge_post_terms', $source_post, $forked_post );

			return $result;
		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return new WP_Error(
				'post_merger',
				$e->getMessage()
			);
		}
	}

	/**
	 * Archive a forked post after it's been merged.
	 *
	 * @param  int $post_id The post ID to archive.
	 * @param  array $post_data Array of post data to save for the archived fork.
	 * @return boolean|\WP_Error
	 */
	public function archive_forked_post( $post_id, $post_data ) {
		try {
			if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
				throw new Exception(
					'Forked post could not be archived because the supplied post ID was not valid.'
				);
			}

			$post_data = $this->prepare_post_data( $post_data );

			if ( empty( $post_data ) || ! is_array( $post_data ) ) {
				throw new Exception(
					'Forked post could not be archived because the post data was invalid.'
				);
			}

			$post_data['ID']          = absint( $post_id );
			$post_data['post_status'] = $this->get_archived_fork_post_status();

			$result = wp_update_post( $post_data, true );

			if ( true !== Helpers\is_valid_post_id( $result ) ) {
				throw new Exception(
					'Forked post could not be archived.'
				);
			}

			return true;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return new WP_Error(
				'post_merger',
				$e->getMessage()
			);
		}
	}

	public function get_archived_fork_post_status() {
		return ArchivedForkStatus::get_name();
	}

	/**
	 * Get the columns that should be ignored when merging a post.
	 *
	 * @return array
	 */
	public function get_columns_to_exclude() {
		return array(
			'ID',
			'post_ID', // ID may be specified with this field alternatively.
			'post_status',
			'post_name',
			'guid',
		);
	}

	/**
	 * Get the meta keys to exclude when copying meta data from the fork to the source post.
	 *
	 * @return array
	 */
	public function get_meta_keys_to_exclude() {
		return array(
			Posts::ORIGINAL_POST_ID_META_KEY
		);
	}

	/**
	 * Determine if a fork can be merged back into it's source post.
	 *
	 * @param  int|\WP_Post $fork
	 * @return boolean
	 */
	public function can_merge( $fork ) {
		return true === \TenUp\PostForking\Posts\post_can_be_merged( $fork );
	}
}
