<?php

namespace TenUp\WPSafeEdit\Posts;

use \TenUp\WPSafeEdit\Helpers;
use \TenUp\WPSafeEdit\Posts;
use \TenUp\WPSafeEdit\Posts\PostTypeSupport;
use \TenUp\WPSafeEdit\API\ForkPostController;
use \TenUp\WPSafeEdit\API\MergePostController;
use \TenUp\WPSafeEdit\Posts\Statuses\DraftForkStatus;

/**
 * Class to manage the publishing buttons to fork and merge posts.
 */
class PublishingButtons {

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		add_action(
			'post_submitbox_start',
			array( $this, 'render_publishing_buttons' )
		);

		add_action(
			'admin_footer',
			array( $this, 'render_lock_dialog' )
		);
	}

	/**
	 * Render needed items.
	 *
	 * @return void
	 */
	public function render_publishing_buttons() {
		$this->render_open_fork_message();
		$this->render_archived_fork_message();
		$this->render_view_source_post_message();

		$this->render_fork_post_button();
		$this->render_merge_post_button();

		$this->alter_publishing_buttons();
		$this->alter_publishing_fields();
	}

	/**
	 * Render a message letting the user know the post has an open fork pending.
	 *
	 * @return void
	 */
	function render_open_fork_message() {
		global $post;

		if ( true !== Posts\post_type_supports_forking( $post ) ) {
			return;
		}

		$fork = Posts\get_open_fork_for_post( $post );

		if ( true !== Helpers\is_post( $fork ) ) {
			return;
		}

		$message    = $this->get_fork_exists_message();
		$link_label = $this->get_edit_fork_label(); ?>

		<div class="wpse-fork-exists-message">
			<?php echo esc_html( $message ); ?>

			<a
				href="<?php echo esc_url( get_edit_post_link( $fork->ID ) ); ?>"
				class="edit-fork-link">
				<?php echo esc_html( $link_label ); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render a message letting the user know they're editing a fork and can view the source post.
	 *
	 * @return void
	 */
	function render_view_source_post_message() {
		global $post;

		if ( true !== Posts\is_open_fork( $post ) ) {
			return;
		}

		$source_post = Posts\get_source_post_for_fork( $post );

		if ( true !== Helpers\is_post( $source_post ) ) {
			return;
		}

		$message = $this->get_editing_fork_message();

		$link = sprintf(
			'<a
				href="%s"
				class="view-source-post-link"
				target="_blank"
				rel="noopener noreferrer">%s</a>',
			esc_url( get_permalink( $source_post->ID ) ),
			esc_html( get_the_title( $source_post ) )
		);

		$message = sprintf( $message, $link ); ?>

		<div class="wpse-view-source-post-message">
			<?php echo wp_kses_post( $message ); ?>
		</div>
	<?php
	}

	/**
	 * Render a message letting the user know they're viewing an archived fork and can view the source post.
	 *
	 * @return void
	 */
	function render_archived_fork_message() {
		global $post;

		if ( true !== Posts\is_archived_fork( $post ) ) {
			return;
		}

		$source_post = Posts\get_source_post_for_fork( $post );

		if ( true !== Helpers\is_post( $source_post ) ) {
			return;
		}

		$message    = $this->get_viewing_archived_fork_message();
		$link_label = $this->get_edit_source_post_label(); ?>

		<div class="wpse-viewing-archived-fork-message">
			<?php echo esc_html( $message ); ?>

			<a
				href="<?php echo esc_url( get_edit_post_link( $source_post->ID ) ); ?>"
				class="view-source-post-link">
				<?php echo esc_html( $link_label ); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render the "Fork Post" publishing button.
	 *
	 * @return void
	 */
	function render_fork_post_button() {
		global $post;

		if ( true !== Posts\post_can_be_forked( $post ) ) {
			return;
		}

		$button_label = $this->get_fork_post_button_label(); ?>

		<div class="wpse-actions wpse-fork-post-button-wrapper">
			<span class="wpse-spinner spinner"></span>
			<input
				type="submit"
				class="button button-large"
				id="wpse-fork-post-button"
				value="<?php echo esc_html( $button_label ); ?>"
			>
			<input
				type="hidden"
				id="classic-editor"
				value="1"
			/>
			<?php
			wp_nonce_field( ForkPostController::NONCE_ACTION, ForkPostController::NONCE_NAME ); ?>
		</div>
	<?php
	}

	/**
	 * Render the "Merge Post" publishing button.
	 *
	 * @return void
	 */
	function render_merge_post_button() {
		global $post;

		if ( true !== Posts\post_can_be_merged( $post ) ) {
			return;
		}

		$button_label = $this->get_merge_post_button_label(); ?>

		<div class="wpse-actions wpse-merge-post-button-wrapper">
			<span class="wpse-spinner spinner"></span>
			<input type="hidden" name="post_status" value="<?php echo esc_attr( DraftForkStatus::get_name() ); ?>" />
			<input
				type="submit"
				class="button button-primary button-large"
				id="wpse-merge-post-button"
				value="<?php echo esc_html( $button_label ) ?>"
			>

			<?php
			wp_nonce_field( MergePostController::NONCE_ACTION, MergePostController::NONCE_NAME ); ?>
		</div>
	<?php
	}

	/**
	 * Hide the publishing button.
	 *
	 * @return void
	 */
	public function alter_publishing_buttons() {
		if ( true !== $this->should_hide_wp_publish_buttons() ) {
			return;
		} ?>

		<style>
			#publishing-action {
				display: none !important;
			}
		</style>

	<?php
	}

	/**
	 * Hide publishing fields.
	 *
	 * @return void
	 */
	public function alter_publishing_fields() {
		global $post;

		$this->alter_status_field();

		if ( Posts\is_archived_fork( $post ) ) { ?>
			<style>
				#publishing-action,
				#misc-publishing-actions,
				#minor-publishing-actions {
					display: none !important;
				}
			</style>
		<?php
		}
	}

	/**
	 * Hide the status field.
	 *
	 * @return void
	 */
	public function alter_status_field() {
		if ( true !== $this->should_hide_wp_status_field() ) {
			return;
		} ?>

		<style>
			.misc-pub-post-status {
				display: none !important;
			}
		</style>

	<?php
	}

	/**
	 * Determine if we should hide the publish button.
	 *
	 * @return bool
	 */
	public function should_hide_wp_publish_buttons() {
		global $post;

		$value = false;

		if (
			true !== Helpers\is_post( $post ) ||
			true !== Posts\post_type_supports_forking( $post )
		) {
			return false;
		}

		if (
			Posts\post_has_open_fork( $post ) ||
			Posts\is_fork( $post )
		) {
			$value = true;
		}

		return $value;
	}

	/**
	 * Determine if we should hide the status field.
	 *
	 * @return bool
	 */
	public function should_hide_wp_status_field() {
		global $post;

		$value = false;

		if (
			true !== Helpers\is_post( $post ) ||
			true !== Posts\post_type_supports_forking( $post )
		) {
			return false;
		}

		if ( Posts\is_fork( $post ) ) {
			$value = true;
		}

		return $value;
	}

	/**
	 * Render a dialog letting the user know the post is locked because of an open fork exists.
	 *
	 * @return void
	 */
	function render_lock_dialog() {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return;
		}

		// For some reason, the global $post variable isn't available in the admin_footer action, so we need to get it from the query string.
		$post_id = 0;
		if ( isset( $_GET['post'] ) ) {
			$post_id = absint( $_GET['post'] );
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = absint( $_POST['post_ID'] );
		}

		$post = Helpers\get_post( $post_id );

		if ( true !== Posts\post_type_supports_forking( $post ) ) {
			return;
		}

		$fork = Posts\get_open_fork_for_post( $post );

		if ( true !== Helpers\is_post( $fork ) ) {
			return;
		}

		$message    = $this->get_fork_exists_message();
		$link_label = $this->get_edit_fork_label(); ?>

		<div id="wpse-lock-dialog" class="notification-dialog-wrap">
			<div class="notification-dialog-background"></div>
			<div class="notification-dialog">
				<div class="post-locked-message">
					<p class="currently-editing wp-tab-first" tabindex="0">
						<?php echo esc_html( $message ); ?>
					</p>

					<p>
						<a class="button" href="<?php echo esc_url( wp_get_referer() ) ?>"><?php esc_html_e( 'Go back', 'wp-safe-edit' ) ?></a>

						<a class="button button-primary wp-tab-last" href="<?php echo esc_url( get_edit_post_link( $fork->ID ) ); ?>"><?php echo esc_html( $link_label ); ?></a>
					</p>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Get the fork button label.
	 *
	 * @return string
	 */
	function get_fork_post_button_label() {
		$value = __( 'Save as Draft', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_fork_post_button_label', $value );
	}

	/**
	 * Get the merge button label.
	 *
	 * @return string
	 */
	function get_merge_post_button_label() {
		$value = __( 'Publish Changes', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_merge_post_button_label', $value );
	}

	/**
	 * Get the fork exists message.
	 *
	 * @return string
	 */
	function get_fork_exists_message() {
		$value = __( 'A draft version of this post has been created. Changes must be made on the draft until it\'s published.', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_fork_exists_message', $value );
	}

	/**
	 * Get the editing fork message.
	 *
	 * @return string
	 */
	function get_editing_fork_message() {
		$value = __( 'You\'re editing a draft of %s. Publish your changes to make them live.', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_editing_fork_message', $value );
	}

	/**
	 * Get the viewing archived fork message.
	 *
	 * @return string
	 */
	function get_viewing_archived_fork_message() {
		$value = __( 'You\'re viewing an archived draft revision. This draft can no longer be edited.', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_viewing_archived_fork_message', $value );
	}

	/**
	 * Get the edit fork label.
	 *
	 * @return string
	 */
	function get_edit_fork_label() {
		$value = __( 'Edit Draft', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_edit_fork_link_label', $value );
	}

	/**
	 * Get the edit source label.
	 *
	 * @return string
	 */
	function get_edit_source_post_label() {
		$value = __( 'Edit the published version', 'wp-safe-edit' );
		return apply_filters( 'safe_edit_edit_source_post_link_label', $value );
	}
}
