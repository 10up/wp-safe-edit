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
		$this->render_fork_post_button();
		$this->render_merge_post_button();
	}

	/**
	 * Render the "Fork Post" publishing button.
	 */
	function render_fork_post_button() {
		global $post;

		if ( true !== $this->post_can_be_forked( $post ) ) {
			return;
		}

		$button_label = $this->get_fork_post_button_label(); ?>

		<div class="pf-fork-post-button">
			<a
				href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"
				class="revisions-fork button-primary button">
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

		if ( true !== $this->post_can_be_merged( $post ) ) {
			return;
		}

		$button_label = $this->get_merge_post_button_label(); ?>

		<div class="pf-merge-post-button">
			<a
				href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"
				class="revisions-merge button-primary button">
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

	function post_can_be_forked( $post ) {
		$post = Helpers\get_post( $post );

		if (
			true === Helpers\is_post( $post ) &&
			true === PostTypeSupport::post_supports_forking( $post ) &&
			true === Users::current_user_can_fork_post( $post )
		) {
			return true;
		}

		return false;
	}

	function post_can_be_merged( $post ) {
		$post = Helpers\get_post( $post );

		if (
			true === Helpers\is_post( $post ) &&
			true === PostTypeSupport::post_supports_forking( $post )
		) {
			return true;
		}

		return false;
	}
}
