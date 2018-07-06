<?php
namespace TenUp\WPSafeEdit\API;

use \Exception;
use \WP_Error;

use \TenUp\WPSafeEdit\Helpers;
use \TenUp\WPSafeEdit\Forking\PostMerger;

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
		add_action( 'rest_api_init', function () {
			register_rest_route( 'wp-safe-edit/v1', '/merge/(?P<id>\d+)', array(
				'methods' => 'GET',
				'callback' => 'TenUp\WPSafeEdit\API\MergePostController::handle_merge_post_api_request',
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'        => array(
						'required'    => true,
						'description' => esc_html__( 'Id of post that is being forked.', 'wp-safe-edit' ),
						'type'        => 'integer',
					),
					'nonce'     => array(
						'required'    => true,
						'description' => esc_html__( 'Action nonce.', 'wp-safe-edit' ),
						'type'        => 'string',
					),
				),

			) );
		} );
	}

	// Handle REST API based forking requests.
	public static function handle_merge_post_api_request( $request ) {

		$post_id = absint( $request['id'] );

		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			wp_send_json_error(
				esc_html__( 'Post could not be merged because the request did not provide a valid post ID.', 'wp-safe-edit' )
			);
		}

		try {
			$_POST            = (array) get_post( $post_id );
			$_POST['post_ID'] = $post_id;
			$merger           = new PostMerger();
			$result           = $merger->merge( $post_id );

			if ( true === Helpers\is_valid_post_id( $result ) ) {
				$message = self::get_post_merge_success_message( $result, $post_id );
				$url = get_edit_post_link( $post_id, 'nodisplay' );
				$url = add_query_arg( array(
					'pf_success_message' => rawurlencode( $message ),
				), $url );

				$url = apply_filters( 'safe_edit_post_merge_success_redirect_url', $url, $result, $post_id );

				// Stay in the classic editor when forking from the classic editor.
				if ( isset( $_REQUEST[ 'classic-editor' ] ) ) {
					$url = add_query_arg( array(
						'classic-editor' => true,
					), $url );
				}
				$data = array(
					'shouldRedirect' => self::should_redirect(),
					'redirectUrl'    => $url,
					'message'		 => $message,
				);
				wp_send_json_success( $data );
			} else {
				$message = self::get_post_merge_failure_message_from_result( $result );
				wp_send_json_error(
					$message
				);
			}
		} catch ( Exception $e ) {
			\TenUp\WPSafeEdit\Logging\log_exception( $e );

			$result = new WP_Error(
				'post_merger',
				$e->getMessage()
			);

			$message = self::get_post_merge_failure_message_from_result( $result );
			wp_send_json_error(
				$message
			);
		}
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
			\TenUp\WPSafeEdit\Logging\log_exception( $e );

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
		do_action( 'safe_edit_post_merge_success', $fork_post_id, $source_post_id );

		if ( true !== self::should_redirect() ) {
			return;
		}

		$message = self::get_post_merge_success_message( $source_post_id, $fork_post_id );

		$url = get_edit_post_link( $source_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_success_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'safe_edit_post_merge_success_redirect_url', $url, $fork_post_id, $source_post_id );

		// Stay in the classic editor when forking from the classic editor.
		if ( isset( $_REQUEST[ 'classic-editor' ] ) ) {
			$url = add_query_arg( array(
				'classic-editor' => true,
			), $url );
		}

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
		do_action( 'safe_edit_post_fork_failure', $fork_post_id, $result );

		if ( true !== self::should_redirect() ) {
			return;
		}

		$message = self::get_post_merge_failure_message_from_result( $result );

		$url = get_edit_post_link( $fork_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_error_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'safe_edit_post_merge_failure_redirect_url', $url, $fork_post_id, $result );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Get the feedback message for a user when a post could not be merged.
	 *
	 * @param  \WP_Error|mixed $result The result from the merge request, usually a WP_Error.
	 * @return string
	 */
	public static function get_post_merge_failure_message_from_result( $result ) {
		$message = __( 'The draft changes could not be published.', 'wp-safe-edit' );

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		}

		return apply_filters( 'safe_edit_merge_failure_message', $message, $result );
	}

	/**
	 * Get the feedback message for a user when a fork was merged into its source post.
	 *
	 * @param  int|\WP_Post $source_post The post the fork was merged into
	 * @param  int|\WP_Post $fork The fork that was merged into its source post
	 * @return string
	 */
	public static function get_post_merge_success_message( $source_post, $fork ) {
		$message = __( 'The draft changes have been published.', 'wp-safe-edit' );

		return apply_filters( 'safe_edit_merge_success_message', $message, $source_post, $fork );
	}

	/**
	 * Determine if the current request should be redirected after success or failure.
	 *
	 * @return boolean
	 */
	public static function should_redirect() {
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

			if ( true !== \TenUp\WPSafeEdit\Posts\post_can_be_merged( $post_id ) ) {
				throw new Exception(
					'Post could not be merged because the post specified in the request was not mergable.'
				);
			}

			return true;

		} catch ( Exception $e ) {
			\TenUp\WPSafeEdit\Logging\log_exception( $e );

			return false;
		}
	}
}
