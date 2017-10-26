<?php
namespace TenUp\PostForking\Helpers;

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

function copy_post_meta( $source_post, $destination_post ) {
	die( 'ARGH' );
	$source_fields = $this->get_source_fields( $source_post->ID );

	if ( ! empty( $source_fields ) ) {
		$db    = $this->get_db();
		$query = $this->get_meta_insert_query(
			$source_fields, $destination_post->ID
		);

		if ( ! empty( $query ) ) {
			$result = $db->query( $query, ARRAY_A );

			return $result;
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}

function get_postmeta_table_name() {
	global $wpdb;
	return $wpdb->postmeta;
}
