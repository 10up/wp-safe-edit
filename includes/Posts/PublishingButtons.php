<?php

namespace TenUp\PostForking\Posts;

use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Users;
use \TenUp\PostForking\Posts\PostTypeSupport;

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
	}

	public function render_publishing_buttons() {
		$this->render_open_fork_message();
		$this->render_fork_post_button();
		$this->render_merge_post_button();
	}

	/**
	 * Render a message letting the user know the post has an open fork pending.
	 */
	function render_open_fork_message() {
		global $post;

		if ( true !== \TenUp\PostForking\Posts\post_supports_forking( $post ) ) {
			return;
		}

		$fork = \TenUp\PostForking\Posts\get_open_fork_for_post( $post );

		if ( true !== Helpers\is_post( $fork ) ) {
			return;
		}

		$message    = $this->get_fork_exists_message();
		$link_label = $this->get_edit_fork_label(); ?>

		<div class="pf-fork-exists-message">
			<?php esc_html_e( $message, 'forkit' ); ?>

			<a
				href="<?php echo esc_url( get_edit_post_link( $fork->ID ) ); ?>"
				class="edit-fork-link">
				<?php esc_html_e( $link_label, 'forkit' ); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render the "Fork Post" publishing button.
	 */
	function render_fork_post_button() {
		global $post;

		if ( true !== \TenUp\PostForking\Posts\post_can_be_forked( $post ) ) {
			return;
		}

		$button_label = $this->get_fork_post_button_label(); ?>

		<div class="pf-fork-post-button-wrapper">
			<a
				href="#"
				class="pf-fork-post-button button-primary button">
				<?php esc_html_e( $button_label, 'forkit' ) ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render the "Merge Post" publishing button.
	 */
	function render_merge_post_button() {
		global $post;

		if ( true !== \TenUp\PostForking\Posts\post_can_be_merged( $post ) ) {
			return;
		}

		$button_label = $this->get_merge_post_button_label(); ?>

		<div class="pf-merge-post-button">
			<a
				href="#"
				class="pf-merge-post-button button-primary button">
				<?php esc_html_e( $button_label, 'forkit' ) ?>
			</a>
		</div>
	<?php
	}

	function get_fork_post_button_label() {
		$value = 'Fork';
		return apply_filters( 'post_forking_fork_post_button_label', $value );
	}

	function get_merge_post_button_label() {
		$value = 'Merge & Publish';
		return apply_filters( 'post_forking_merge_post_button_label', $value );
	}

	function get_fork_exists_message() {
		$value = 'A fork of this post has been created. Further edits must be made on the forked version or they will be overwritten when it\'s published.';
		return apply_filters( 'post_forking_fork_exists_message', $value );
	}

	function get_edit_fork_label() {
		$value = 'Edit Fork';
		return apply_filters( 'post_forking_edit_fork_link_label', $value );
	}
}
