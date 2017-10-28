<?php
namespace TenUp\PostForking\API;

use \WP_REST_Controller;
use \WP_REST_Server;

use \TenUp\PostForking\Users;
use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Forking\PostForker;

/**
 * Class to manage forking API endpoints.
 */
class Forking extends WP_REST_Controller {

	const ENDPOINT_NAMESPACE = 'post-forking/v1';
	const FORK_POST_ROUTE    = 'fork-post';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		$this->register_fork_post_route();
	}

	public function register_fork_post_route() {
		register_rest_route(
			static::ENDPOINT_NAMESPACE,
			static::FORK_POST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_fork_post_request' ),
				'permission_callback' => array( $this, 'validate_fork_post_request' ),
				'args'                => array(
					'post_id' => array(
						'required' 			=> true,
						'validate_callback' => '\TenUp\PostForking\Helpers\is_valid_post_id',
					),
				),
			)
		);
	}

	/**
	 * Handle API request to fork a post.
	 *
	 * @param WP_REST_Request $request The API request.
	 */
	public function handle_approve_post_request( \WP_REST_Request $request ) {
		$parameters = $request->get_params();
		$post_id    = Helpers\get_property( 'post_id', $parameters );

		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return new \WP_Error(
				'missing_parameters',
				'A valid post ID was not provided in the request.',
				array( 'status' => 400 )
			);
		}

		$forker = new PostForker();
		$result = $forker->fork( $post_id );

		if ( true === Helpers\is_valid_post_id( $result ) ) {
			$response = array(
				'source_post_id' => absint( $post_id ),
				'fork_post_id'   => absint( $result ),
			);

			wp_send_json_success( $response );
		} else {
			$message = 'Post could not be forked.';

			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
			}

			$response = array(
				'message' => $message,
			);

			wp_send_json_error( $response );
		}
	}

	/**
	 * Validate the API request to fork a post.
	 *
	 * @param WP_REST_Request $request The API request.
	 */
	public function validate_fork_post_request( \WP_REST_Request $request ) {
		$parameters = $request->get_params();
		$post_id    = Helpers\get_property( 'post_id', $parameters );

		if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
			return false;
		}

		return true === \TenUp\PostForking\Posts\post_can_be_forked( $post_id );
	}

	/**
	 * Get the REST API URL to fork a post.
	 *
	 * @return string
	 */
	public static function get_endpoint_url() {
		return set_url_scheme( rest_url( sprintf(
			'%s/%s',
			static::ENDPOINT_NAMESPACE,
			static::FORK_POST_ROUTE
		) ) );
	}
}
