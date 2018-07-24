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
	function register() {
		$this->register_post_status();
	}

	/**
	 * Register post status.
	 *
	 * @return void
	 */
	function register_post_status() {
		register_post_status( $this->get_name(), $this->get_options() );
	}

	/**
	 * Get the options to use when registering the post status.
	 *
	 * @return array
	 */
	function get_options() {
		return array(
			'label'                  => $this->get_label(),
			'internal'               => true,
			'exclude_from_search'    => true,
			'show_in_admin_all_list' => false,
			'protected'              => true,
		);
	}
}
