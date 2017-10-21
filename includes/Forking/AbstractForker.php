<?php
namespace TenUp\PostForking\Forking;

/**
 * Abstract class object forkers can extend.
 */
abstract class AbstractForker {

	abstract function fork( $object );

	abstract function can_fork( $object );

	abstract function has_fork( $object );

	/**
	 * Get the instance of the wpdb class.
	 *
	 * @return \wpdb
	 */
	public function get_db() {
		global $wpdb;
		return $wpdb;
	}
}
