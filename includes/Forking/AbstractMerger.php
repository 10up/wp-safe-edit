<?php
namespace TenUp\PostForking\Forking;

/**
 * Abstract class object mergers can extend.
 */
abstract class AbstractMerger {

	abstract function merge( $object );

	abstract function can_merge( $object );

}
