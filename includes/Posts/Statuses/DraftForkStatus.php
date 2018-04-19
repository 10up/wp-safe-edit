<?php

namespace TenUp\WPSafeEdit\Posts\Statuses;

/**
 * Class to manage the status for draft forks.
 */
class DraftForkStatus extends AbstractStatus {

	const NAME  = 'wpse-draft';
	const LABEL = 'Draft Fork';

	/**
	 * Get the options to use when registering the post status.
	 *
	 * @return array
	 */
	public function get_options() {
		$options = parent::get_options();

		$options['show_in_admin_status_list'] = true;

		$label_value                          = __( 'Draft Revisions <span class="count">(%s)</span>', 'wp-safe-edit' );
		$options['label_count']               = _n_noop( $label_value, $label_value );

		return $options;
	}

}
