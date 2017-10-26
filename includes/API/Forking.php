<?php
namespace TenUp\PostForking\API;

use \WP_REST_Controller;
use \WP_REST_Server;

use \TenUp\PostForking\Users;
use \TenUp\PostForking\Helpers;

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
						'validate_callback' => '\TenUp\PostForking\Helpers\is_valid_post_id'
					),
				),
			)
		);
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

		return true === Users::current_user_can_fork_post( $post_id );
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
