<?php

namespace TenUp\PostForking\Posts;

/**
 * Class to manage post type support.
 */
class PostTypeSupport {

	const FORKING_FEATURE_NAME = 'forking';

	public static function post_supports_forking( $post ) {
		$post_type = get_post_type( $post );
		return true === post_type_supports( $post_type, static::FORKING_FEATURE_NAME );
	}
}
