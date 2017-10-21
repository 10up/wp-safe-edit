<?php
namespace TenUp\PostForking\Forking;

use \Exception;
use \InvalidArgumentException;
use \WP_Error;
use \TenUp\PostForking\Helpers;

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

		} catch ( Exception $e ) {
			return new WP_Error(
				'post_forker',
				$e->getMessage()
			);
		}
	}

	/**
	 * Determine if a post can be forked.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean
	 */
	public function can_fork( $post ) {
		if ( true !== Helpers\is_post_or_post_id( $post ) ) {
			throw new InvalidArgumentException(
				'Post could not be forked because it is not a valid post object or post ID.'
			);
		}

		if ( true === $this->has_fork( $post ) ) {
			throw new Exception(
				'Post could not be forked because a previous fork is still being edited.'
			);
		}

		return true;
	}

	/**
	 * Determine if a post has been forked.
	 *
	 * @param  int|\WP_Post $post
	 * @return boolean
	 */
	public function has_fork( $post ) {
		die( var_dump( 'Implement has_fork()' ) );
		return false;
	}
}
