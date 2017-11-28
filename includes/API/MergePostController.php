<?php
namespace TenUp\PostForking\API;

use \Exception;
use \WP_Error;

use \TenUp\PostForking\Users;
use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Forking\PostMerger;

/**
 * Class to manage requests for merging posts.
 */
class MergePostController {

	const NONCE_NAME   = 'merge_post_nonce';
	const NONCE_ACTION = 'merge_post';

	public function register() {
		add_action(
			'post_action_merge_post',
			array( $this, 'handle_merge_post_request' )
		);
	}

	/**
	 * Handle request to merge a post.
	 */
	public function handle_merge_post_request() {
		try {
			$post_id = $this->get_post_id_from_request();

			if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
				throw new Exception(
					'Post could not be merged because the request did not provide a valid post ID.'
				);
			}

			if ( true !== $this->is_request_valid() ) {
				throw new Exception(
					'Post could not be merged because the request was invalid.'
				);
			}

			$merger = new PostMerger();
			$result = $merger->merge( $post_id );

			if ( true === Helpers\is_valid_post_id( $result ) ) {
				$this->handle_merge_success( $result, $post_id );
			} else {
				$this->handle_merge_failure( $post_id, $result );
			}

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			$result = new WP_Error(
				'post_merger',
				$e->getMessage()
			);

			$this->handle_merge_failure( $post_id, $result );
		}
	}

	/**
	 * Handle a successful merge post request.
	 *
	 * @param  int $source_post_id The post ID of the post that the fork was merged into.
	 * @param  int $fork_post_id The post ID of the fork that was merged into the source post.
	 */
	public function handle_merge_success( $source_post_id, $fork_post_id ) {
		do_action( 'post_forking_post_merge_success', $fork_post_id, $source_post_id );

		if ( true !== $this->should_redirect() ) {
			return;
		}

		$message = $this->get_post_merge_success_message( $source_post_id, $fork_post_id );

		$url = get_edit_post_link( $source_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_success_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'post_forking_post_merge_success_redirect_url', $url, $fork_post_id, $source_post_id );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Handle an unsuccessful merge post request.
	 *
	 * @param  int $fork_post_id The post ID of the post we attempted to merge into its source post.
	 * @param  \WP_Error|mixed $result The result from the merge request, usually a WP_Error.
	 */
	public function handle_merge_failure( $fork_post_id, $result ) {
		do_action( 'post_forking_post_fork_failure', $fork_post_id, $result );

		if ( true !== $this->should_redirect() ) {
			return;
		}

		$message = $this->get_post_merge_failure_message_from_result( $result );

		$url = get_edit_post_link( $fork_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_error_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'post_forking_post_merge_failure_redirect_url', $url, $fork_post_id, $result );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Get the feedback message for a user when a post could not be merged.
	 *
	 * @param  \WP_Error|mixed $result The result from the merge request, usually a WP_Error.
	 * @return string
	 */
	public function get_post_merge_failure_message_from_result( $result ) {
		$message = __( 'Fork could not be published.', 'forkit' );

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		}

		return apply_filters( 'post_forking_merge_failure_message', $message, $result );
	}

	/**
	 * Get the feedback message for a user when a fork was merged into its source post.
	 *
	 * @param  int|\WP_Post $source_post The post the fork was merged into
	 * @param  int|\WP_Post $fork The fork that was merged into its source post
	 * @return string
	 */
	public function get_post_merge_success_message( $source_post, $fork ) {
		$message = __( 'Fork published successfully.', 'forkit' );

		return apply_filters( 'post_forking_merge_success_message', $message, $source_post, $fork );
	}

	/**
	 * Determine if the current request should be redirected after success or failure.
	 *
	 * @return boolean
	 */
	public function should_redirect() {
		if ( defined( 'PHPUNIT_RUNNER' ) || defined( 'WP_CLI' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the post ID from a request.
	 *
	 * @return int
	 */
	public function get_post_id_from_request() {
		return absint( filter_input( INPUT_POST, 'post_ID' ) );
	}

	/**
	 * Get the nonce a request.
	 *
	 * @return int
	 */
	public function get_nonce_from_request() {
		return sanitize_text_field( filter_input( INPUT_POST, static::NONCE_NAME ) );
	}

	/**
	 * Determine if the request to merge a post is valid.
	 *
	 * @return boolean
	 */
	public function is_request_valid() {
		try {
			$post_id = $this->get_post_id_from_request();
			$nonce   = $this->get_nonce_from_request();

			if ( false === wp_verify_nonce( $nonce, static::NONCE_ACTION ) ) {
				throw new Exception(
					'Post could not be merged because the request nonce was invalid.'
				);
			}

			if ( true !== \TenUp\PostForking\Posts\post_can_be_merged( $post_id ) ) {
				throw new Exception(
					'Post could not be merged because the post specified in the request was not mergable.'
				);
			}

			return true;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return false;
		}
	}
}
