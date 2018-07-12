<?php
namespace TenUp\WPSafeEdit\API;

use \Exception;
use \WP_Error;

use \TenUp\WPSafeEdit\Helpers;
use \TenUp\WPSafeEdit\Forking\PostForker;

/**
 * Class to manage requests for forking posts.
 */
class ForkPostController {

	const NONCE_NAME   = 'fork_post_nonce';
	const NONCE_ACTION = 'fork_post';

	public function register() {
		add_action(
			'post_action_fork_post',
			array( $this, 'handle_fork_post_request' )
		);
		add_action( 'rest_api_init', function () {
			register_rest_route( 'wp-safe-edit/v1', '/fork/(?P<id>\d+)', array(
				'methods' => 'GET',
				'callback' => 'TenUp\WPSafeEdit\API\ForkPostController::handle_fork_post_api_request',
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
	public static function handle_fork_post_api_request( $request ) {
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'post-fork' ) ) {
			return new \WP_Error(
				'rest_cannot_create',
				esc_html__( 'Sorry, you are not allowed to fork posts.', 'wp-safe-edit' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$post_id = absint( $request['id'] );

		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			wp_send_json_error(
				esc_html__( 'Post could not be forked because the request did not provide a valid post ID.', 'wp-safe-edit' )
			);
		}

		$forker = new PostForker();
		$fork_post_id = $forker->fork( $post_id );

		if ( true === Helpers\is_valid_post_id( $fork_post_id ) ) {
			do_action( 'safe_edit_post_fork_success', $fork_post_id, $post_id );

			$message = self::get_post_forking_success_message( $fork_post_id, $post_id );

			$url = get_edit_post_link( $fork_post_id, 'nodisplay' );
			$url = add_query_arg( array(
				'pf_success_message' => rawurlencode( $message ),
			), $url );
			$url = apply_filters( 'safe_edit_post_fork_success_redirect_url', $url, $fork_post_id, $post_id );

			$data = array(
				'shouldRedirect' => self::should_redirect(),
				'redirectUrl'    => $url,
			);
			wp_send_json_success( $data );

		} else {
			do_action( 'safe_edit_post_fork_failure', $post_id, $fork_post_id );
			wp_send_json_error( self::get_post_forking_failure_message_from_result( $fork_post_id ) );
		}
	}

	/**
	 * Handle request to fork a post.
	 */
	public function handle_fork_post_request() {
		try {
			$post_id = $this->get_post_id_from_request();

			if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
				throw new Exception(
					'Post could not be forked because the request did not provide a valid post ID.'
				);
			}

			if ( true !== $this->is_request_valid() ) {
				throw new Exception(
					'Post could not be forked because the request was invalid.'
				);
			}

			$forker = new PostForker();
			$result = $forker->fork( $post_id );

			if ( true === Helpers\is_valid_post_id( $result ) ) {
				self::handle_fork_success( $result, $post_id );
			} else {
				self::handle_fork_failure( $post_id, $result );
			}

		} catch ( Exception $e ) {
			\TenUp\WPSafeEdit\Logging\log_exception( $e );

			$result = new WP_Error(
				'post_forker',
				$e->getMessage()
			);

			self::handle_fork_failure( $post_id, $result );
		}
	}

	/**
	 * Handle a successful fork post request.
	 *
	 * @param  int $fork_post_id The post ID of the post fork.
	 * @param  int $source_post_id The post ID of the post that was forked.
	 */
	public static function handle_fork_success( $fork_post_id, $source_post_id ) {
		do_action( 'safe_edit_post_fork_success', $fork_post_id, $source_post_id );

		if ( true !== self::should_redirect() ) {
			return;
		}

		$message = self::get_post_forking_success_message( $fork_post_id, $source_post_id );

		$url = get_edit_post_link( $fork_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_success_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'safe_edit_post_fork_success_redirect_url', $url, $fork_post_id, $source_post_id );

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
	 * Handle an unsuccessful fork post request.
	 *
	 * @param  int $source_post_id The post ID of the post we attempted to fork.
	 * @param  \WP_Error|mixed $result The result from the fork request, usually a WP_Error.
	 */
	public static function handle_fork_failure( $source_post_id, $result ) {
		do_action( 'safe_edit_post_fork_failure', $source_post_id, $result );

		if ( true !== self::should_redirect() ) {
			return;
		}

		$message = self::get_post_forking_failure_message_from_result( $result );

		$url = get_edit_post_link( $source_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_error_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'safe_edit_post_fork_failure_redirect_url', $url, $source_post_id, $result );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Get the feedback message for a user when a post could not be forked.
	 *
	 * @param  \WP_Error|mixed $result The result from the fork request, usually a WP_Error.
	 * @return string
	 */
	public static function get_post_forking_failure_message_from_result( $result ) {
		$message = __( 'Post could not be saved as a draft.', 'wp-safe-edit' );

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		}

		return apply_filters( 'safe_edit_fork_failure_message', $message, $result );
	}

	/**
	 * Get the feedback message for a user when a post was forked.
	 *
	 * @param  int|\WP_Post $fork The fork created
	 * @param  int|\WP_Post $source_post The post the fork was created from
	 * @return string
	 */
	public static function get_post_forking_success_message( $fork, $source_post ) {
		$message = __( 'A draft has been created and you can edit it below. Publish your changes to make them live.', 'wp-safe-edit' );

		return apply_filters( 'safe_edit_fork_success_message', $message, $fork, $source_post );
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
	 * Determine if the request to fork a post is valid.
	 *
	 * @return boolean
	 */
	public function is_request_valid() {
		try {
			$post_id = $this->get_post_id_from_request();
			$nonce   = $this->get_nonce_from_request();

			if ( false === wp_verify_nonce( $nonce, static::NONCE_ACTION ) ) {
				throw new Exception(
					'Post could not be forked because the request nonce was invalid.'
				);
			}

			if ( true !== \TenUp\WPSafeEdit\Posts\post_can_be_forked( $post_id ) ) {
				throw new Exception(
					'Post could not be forked because the post specified in the request was not forkable.'
				);
			}

			return true;

		} catch ( Exception $e ) {
			\TenUp\WPSafeEdit\Logging\log_exception( $e );

			return false;
		}
	}
}
