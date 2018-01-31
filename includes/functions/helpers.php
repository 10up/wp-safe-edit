<?php
namespace TenUp\WPSafeEdit\Helpers;

/**
 * Get a post object, but only if the ID is not zero. Use this instead of WordPress' get_post( $post ) when you don't want the global post returned when a valid post or post ID isn't given.
 *
 * @param  int|\WP_Post $post The post ID or object
 * @return \WP_Post|null
 */
function get_post( $post ) {
	if ( is_valid_post_id( $post )  ) {
		$post = \get_post( $post );
	}

	if ( true !== is_post( $post )  ) {
		return null;
	}

	return $post;
}

/**
 * Determine if the object is a post object.
 *
 * @param  \WP_Post $post The post object
 * @return boolean
 */
function is_post( $post ) {
	return true === ( $post instanceof \WP_Post );
}

/**
 * Determine if the object is a post or the post ID for an existing post.
 *
 * @param  int|\WP_Post $post The post ID or object
 * @return boolean
 */
function is_post_or_post_id( $post ) {
	if ( true === is_post( $post ) ) {
		return true;
	}

	$post = get_post( $post );

	if ( true === is_post( $post )  ) {
		return true;
	}

	return false;
}

/**
 * Determine if a post ID is valid.
 *
 * @param  int|string $post_id
 * @return boolean
 */
function is_valid_post_id( $post_id ) {
	return ( ! empty( $post_id ) && is_numeric( $post_id ) );
}

/**
 * Get a property from either an object or an array.
 *
 * @param  string       $key        The name of the property to retrieve.
 * @param  array|object $data The object to retrieve the property for.
 * @param  mixed        $default     The default if the property is empty or not found.
 * @return mixed
 */
function get_property( $key, $data, $default = null ) {
	$value = null;

	if ( is_array( $data ) ) {
		$value = get_array_property( $key, $data, $default );
	} elseif ( is_object( $data ) ) {
		$value = get_object_property( $key, $data, $default );
	}

	return $value;
}

/**
 * Get a property from an array.
 *
 * @param string $key     The name of the property to retrieve.
 * @param array  $data    The array to retrieve the property for.
 * @param mixed  $default The default if the property is empty or not found.
 * @return mixed
 */
function get_array_property( $key, $data, $default = null ) {
	if ( ! isset( $data[ $key ] ) ) {
		return $default;
	}

	return $data[ $key ];
}

/**
 * Get a property from an object.
 *
 * @param string $key     The name of the property to retrieve.
 * @param object $data    The object to retrieve the property for.
 * @param mixed  $default The default if the property is empty or not found.
 * @return mixed
 */
function get_object_property( $key, $data, $default = null ) {
	if ( ! isset( $data->$key ) ) {
		return $default;
	}

	return $data->$key;
}
