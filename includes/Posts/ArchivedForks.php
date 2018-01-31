<?php

namespace TenUp\WPSafeEdit\Posts;

use \TenUp\WPSafeEdit\Helpers;
use \TenUp\WPSafeEdit\Posts;
use \TenUp\WPSafeEdit\Posts\Statuses;
use \TenUp\WPSafeEdit\Posts\Statuses\ArchivedForkStatus;

/**
 * Class to manage archived forks.
 */
class ArchivedForks {

	public function register() {
		add_action(
			'add_meta_boxes',
			[ $this, 'register_meta_boxes' ]
		);
	}

	public function register_meta_boxes() {
		if ( true === $this->should_show_archived_forks_meta_box() ) {
			$this->register_archived_forks_meta_box();
		}
	}

	/**
	 * Determine if the archived forks meta box should be shown.
	 *
	 * @return bool
	 */
	function should_show_archived_forks_meta_box() {
		global $post;

		$value = false;

		if (
			true === Helpers\is_post( $post ) &&
			post_type_supports( $post->post_type, PostTypeSupport::FORKING_FEATURE_NAME ) &&
			false === Posts\is_fork( $post )
		) {
			$value = true;
		}

		return apply_filters( 'safe_edit_should_show_archived_forks_meta_box', $value, $post );
	}

	public function register_archived_forks_meta_box() {
		add_meta_box(
			'post-forking-archived-forks',
			__( 'Archived Forks', 'wp-safe-edit' ),
			[ $this, 'render_archived_forks_meta_box' ],
			(array) Posts\get_forkable_post_types()
		);
	}

	public function render_archived_forks_meta_box( $post ) {
		if ( true !== Helpers\is_post( $post ) ) {
			return;
		}

		$query = Posts\get_archived_forks_query( $post );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post(); ?>
				<p>
					<?php
					printf(
						'<a href="%s">%s</a>',
						esc_url( get_edit_post_link( absint( get_the_ID() ) ) ),
						get_the_title()
					); ?>
					<br>
					<span class="date"><?php echo get_the_date(); ?></span>
				</p>
			<?php
			}

			wp_reset_query();

		} else { ?>
			<p><?php echo esc_html_e( 'No archived forks available', 'wp-safe-edit' ); ?></p>
		<?php
		}
	}
}
