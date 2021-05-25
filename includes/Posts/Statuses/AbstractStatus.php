<?php

namespace TenUp\WPSafeEdit\Posts\Statuses;

/**
 * Abstract class to manage custom post statuses.
 */
abstract class AbstractStatus {

	const NAME  = '';
	const LABEL = '';

	/**
	 * Returns the post status name.
	 *
	 * @return string
	 */
	public static function get_name() {
		$value = static::NAME;
		return apply_filters( "post_forking_{$value}_post_status_name", $value );
	}

	/**
	 * Returns the post status label.
	 *
	 * @return string
	 */
	public static function get_label() {
		$value = static::LABEL;
		return apply_filters( "post_forking_{$value}_post_status_label", $value );
	}

	/**
	 * Run needed hooks/functions.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_post_status();
	}

	/**
	 * Register post status.
	 *
	 * @return void
	 */
	public function register_post_status() {
		register_post_status( $this->get_name(), $this->get_options() );
	}

	/**
	 * Get the options to use when registering the post status.
	 *
	 * @return array
	 */
	public function get_options() {

		$args = array(
			'label'                     => $this->get_label(),
			'exclude_from_search'       => true,
			'show_in_admin_status_list' => false,
			'show_in_admin_all_list'    => false,
			'protected'                 => true,
			'public'                    => false,
			'publicly_queryable'        => false,
			'private'                   => true,
		);

		/**
		 * Filter post status registration arguments.
		 *
		 * @param array  $args        Array or string of post status arguments.
		 * @param string $post_status Name of the post status.
		 */
		return apply_filters( 'safe_edit_register_post_status_args', $args, $this->get_name() );
	}
}
