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

	/**
	 * Add needed hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action(
			'add_meta_boxes',
			[ $this, 'register_meta_boxes' ]
		);
	}

	/**
	 * Register archived draft meta boxes.
	 *
	 * @return void
	 */
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

	/**
	 * Add the archived draft meta box.
	 *
	 * @return void
	 */
	public function register_archived_forks_meta_box() {
		add_meta_box(
			'post-forking-archived-forks',
			esc_html__( 'Archived Draft Revisions', 'wp-safe-edit' ),
			[ $this, 'render_archived_forks_meta_box' ],
			(array) Posts\get_forkable_post_types()
		);
	}

	/**
	 * Render the archived draft meta box.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
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
					<span class="date"><?php echo esc_html( get_the_date() ); ?></span>
				</p>
			<?php
			}

			wp_reset_query();

		} else { ?>
			<p><?php esc_html_e( 'No draft revisions have been created yet.', 'wp-safe-edit' ); ?></p>
		<?php
		}
	}
}
