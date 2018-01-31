<?php
namespace TenUp\WPSafeEdit\Forking;

/**
 * Abstract class object forkers can extend.
 */
abstract class AbstractForker {

	abstract function fork( $object );

	abstract function can_fork( $object );

	abstract function has_fork( $object );

}
