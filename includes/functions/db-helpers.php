<?php
namespace TenUp\PostForking\Helpers;

use TenUp\PostForking\Helpers;

/**
 * Delete all post meta data for a post.
 *
 * @param  int|\WP_Post $post
 * @return int|boolean The number of rows deleted if successful, false if not.
 */
function clear_post_meta( $post ) {
	global $wpdb;

	$post = Helpers\get_post( $post );

	if ( true !== Helpers\is_post( $post ) ) {
		return false;
	}

	$table = get_postmeta_table_name();

	if ( empty( $table ) ) {
		return false;
	}

	$query = <<<SQL
		DELETE FROM {$table}
		WHERE post_id = %d;
SQL;

	$query   = $wpdb->prepare( $query, absint( $post->ID ) );
	$results = $wpdb->query( $query );

	return $results;
}

/**
 * Copy meta data from one post to another.
 *
 * @param  int|\WP_Post $source_post The post to copy the meta data from
 * @param  int|\WP_Post $destination_post The post to copy the meta data to
 * @return int|boolean The number of rows inserted if successful; false if not.
 */
function copy_post_meta( $source_post, $destination_post ) {
	global $wpdb;

	$source_post      = Helpers\get_post( $source_post );
	$destination_post = Helpers\get_post( $destination_post );

	if (
		true !== Helpers\is_post( $source_post ) ||
		true !== Helpers\is_post( $destination_post )
	) {
		return false;
	}

	$query = get_copy_meta_data_insert_sql( $source_post->ID, $destination_post->ID );

	if ( empty( $query ) ) {
		return false;
	}

	$result = $wpdb->query( $query );
	return $result;
}

/**
 * Copy the taxonomy terms from the original post to the forked post.
 *
 * @param  int|\WP_Post $source_post The post ID or object to copy the terms from
 * @param  int|\WP_Post $destination_post The post ID or object to copy the terms to
 * @return int|boolean The number of taxonomy terms copied to the destination post if successful; false if not.
 */
function copy_post_terms( $source_post, $destination_post ) {
	$source_post        = Helpers\get_post( $source_post );
	$destination_post = Helpers\get_post( $destination_post );

	if (
		true !== Helpers\is_post( $source_post ) ||
		true !== Helpers\is_post( $destination_post )
	) {
		return false;
	}

	$post_type  = get_post_type( $source_post );
	$taxonomies = get_object_taxonomies( $post_type, 'names' );
	$count      = 0;

	if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
		return false;
	}

	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_object_terms(
			$source_post->ID,
			$taxonomy,
			array( 'fields' => 'ids' )
		);

		if ( empty( $terms ) ) {
			continue;
		}

		wp_set_object_terms( $destination_post->ID, $terms, $taxonomy, false );

		$count += count( $terms );
	}

	return $count;
}

/**
 * Get the SQL statement to insert post meta fields copied from another post.
 *
 * @param  int $source_post_id The post id to copy the meta data from
 * @param  int $destination_post_id The post id to copy the meta data to
 * @return string|boolean The SQL statement if successful; false if not.
 */
function get_copy_meta_data_insert_sql( $source_post_id, $destination_post_id  ) {
	global $wpdb;

	if (
		true !== Helpers\is_valid_post_id( $source_post_id ) ||
		true !== Helpers\is_valid_post_id( $destination_post_id )
	) {
		return false;
	}

	$table = get_postmeta_table_name();
	if ( empty( $table ) ) {
		return false;
	}

	$values    = '';
	$meta_data = get_all_post_meta_data( $source_post_id );

	if ( empty( $meta_data ) || ! is_array( $meta_data ) ) {
		return false;
	}

	foreach ( $meta_data as $field ) {
		$meta_key = Helpers\get_property( 'meta_key', $field );

		if ( empty( $meta_key ) ) {
			continue;
		}

		$meta_value = Helpers\get_property( 'meta_value', $field );

		$fragment = '(%d, %s, %s),';
		$fragment = $wpdb->prepare(
			$fragment,
			absint( $destination_post_id ),
			$meta_key,
			$meta_value
		);

		$values .= $fragment;
	}

	if ( empty( $values ) ) {
		return '';
	}

	$values = rtrim( $values, ',' );
	$query  = <<<SQL
		INSERT INTO {$table}
			(post_id, meta_key, meta_value)
		VALUES {$values};
SQL;

	return $query;
}

/**
 * Return an array of all the post meta data rows for a post.
 *
 * @param  int $post_id The post ID
 * @return array|boolean Array if successful, false if not.
 */
function get_all_post_meta_data( $post_id ) {
	global $wpdb;

	if ( true !== Helpers\is_valid_post_id( $post_id ) ) {
		return false;
	}

	$table = get_postmeta_table_name();
	if ( empty( $table ) ) {
		return false;
	}

	$query = <<<SQL
		SELECT *
		FROM $table
		WHERE post_id = %d;
SQL;

	$query   = $wpdb->prepare( $query, absint( $post_id ) );
	$results = $wpdb->get_results( $query, ARRAY_A );

	return $results;
}

function get_postmeta_table_name() {
	global $wpdb;
	return $wpdb->postmeta;
}
