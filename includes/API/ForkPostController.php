<?php
namespace TenUp\PostForking\API;

use \Exception;
use \WP_Error;

use \TenUp\PostForking\Users;
use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Forking\PostForker;

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
				$this->handle_fork_success( $result, $post_id );
			} else {
				$this->handle_fork_failure( $post_id, $result );
			}

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			$result = new WP_Error(
				'post_forker',
				$e->getMessage()
			);

			$this->handle_fork_failure( $post_id, $result );
		}
	}

	/**
	 * Handle a successful fork post request.
	 *
	 * @param  int $fork_post_id The post ID of the post fork.
	 * @param  int $source_post_id The post ID of the post that was forked.
	 */
	public function handle_fork_success( $fork_post_id, $source_post_id ) {
		do_action( 'post_forking_post_fork_success', $fork_post_id, $source_post_id );

		if ( true !== $this->should_redirect() ) {
			return;
		}

		$url = get_edit_post_link( $fork_post_id, 'nodisplay' );
		$url = apply_filters( 'post_forking_post_fork_success_redirect_url', $url, $fork_post_id, $source_post_id );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Handle an unsuccessful fork post request.
	 *
	 * @param  int $source_post_id The post ID of the post we attempted to fork.
	 * @param  \WP_Error|mixed $result The result from the fork request, usually a WP_Error.
	 */
	public function handle_fork_failure( $source_post_id, $result ) {
		do_action( 'post_forking_post_fork_failure', $source_post_id, $result );

		if ( true !== $this->should_redirect() ) {
			return;
		}

		$message = $this->get_post_forking_failure_message_from_result( $result );

		$url = get_edit_post_link( $source_post_id, 'nodisplay' );
		$url = add_query_arg( array(
			'pf_message' => rawurlencode( $message ),
		), $url );

		$url = apply_filters( 'post_forking_post_fork_failure_redirect_url', $url, $source_post_id, $result );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Get the feedback message for a user when a post could not be forked.
	 *
	 * @param  \WP_Error|mixed $result The result from the fork request, usually a WP_Error.
	 * @return string
	 */
	public function get_post_forking_failure_message_from_result( $result ) {
		$message = 'Post could not be forked.';

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		}

		return $message;
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

			if ( true !== \TenUp\PostForking\Posts\post_can_be_forked( $post_id ) ) {
				throw new Exception(
					'Post could not be forked because the post specified in the request was not forkable.'
				);
			}

			return true;

		} catch ( Exception $e ) {
			\TenUp\PostForking\Logging\log_exception( $e );

			return false;
		}
	}

	/**
	 * Get the URL used to fork a post.
	 *
	 * @param  int|\WP_Post $post The post to fork
	 * @return string
	 */
	// public static function get_fork_post_action_url( $post ) {
	// 	$post_id = 0;

	// 	if ( Helpers\is_post( $post ) ) {
	// 		$post_id = $post->ID;
	// 	} elseif ( Helpers\is_valid_post_id( $post ) ) {
	// 		$post_id = absint( $post );
	// 	} else {
	// 		return '';
	// 	}

	// 	$url = admin_url( 'admin-post.php' );
	// 	$url = add_query_arg( array(
	// 		'action'  => rawurlencode( static::NONCE_ACTION ),
	// 		'post_id' => absint( $post_id ),
	// 		'nonce'   => rawurlencode( wp_create_nonce( static::NONCE_ACTION ) ),
	// 	), $url );

	// 	return $url;
	// }
}
