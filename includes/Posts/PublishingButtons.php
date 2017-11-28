<?php

namespace TenUp\PostForking\Posts;

use \TenUp\PostForking\Helpers;
use \TenUp\PostForking\Users;
use \TenUp\PostForking\Posts;
use \TenUp\PostForking\Posts\PostTypeSupport;
use \TenUp\PostForking\API\ForkPostController;
use \TenUp\PostForking\API\MergePostController;

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
		$this->render_archived_fork_message();
		$this->render_view_source_post_message();

		$this->render_fork_post_button();
		$this->render_merge_post_button();

		$this->alter_publishing_buttons();
		$this->alter_publishing_fields();
	}

	/**
	 * Render a message letting the user know the post has an open fork pending.
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
	 * Render a message letting the user know they're editing a fork and can view the source post.
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

		$message    = $this->get_editing_fork_message();
		$link_label = $this->get_view_source_post_label(); ?>

		<div class="pf-view-source-post-message">
			<?php esc_html_e( $message, 'forkit' ); ?>

			<a
				href="<?php echo esc_url( get_edit_post_link( $source_post->ID ) ); ?>"
				class="view-source-post-link">
				<?php esc_html_e( $link_label, 'forkit' ); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render a message letting the user know they're viewing an archived fork and can view the source post.
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
		$link_label = $this->get_view_source_post_label(); ?>

		<div class="pf-viewing-archived-fork-message">
			<?php esc_html_e( $message, 'forkit' ); ?>

			<a
				href="<?php echo esc_url( get_edit_post_link( $source_post->ID ) ); ?>"
				class="view-source-post-link">
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

		if ( true !== Posts\post_can_be_forked( $post ) ) {
			return;
		}

		$button_label = $this->get_fork_post_button_label(); ?>

		<div class="pf-fork-post-button-wrapper">
			<input
				type="submit"
				class="button button-primary button-large"
				id="pf-fork-post-button"
				value="<?php esc_html_e( $button_label, 'forkit' ) ?>"
			>

			<?php
			wp_nonce_field( ForkPostController::NONCE_ACTION, ForkPostController::NONCE_NAME ); ?>
		</div>
	<?php
	}

	/**
	 * Render the "Merge Post" publishing button.
	 */
	function render_merge_post_button() {
		global $post;

		if ( true !== Posts\post_can_be_merged( $post ) ) {
			return;
		}

		$button_label = $this->get_merge_post_button_label(); ?>

		<div class="pf-merge-post-button-wrapper">
			<input
				type="submit"
				class="button button-primary button-large"
				id="pf-merge-post-button"
				value="<?php esc_html_e( $button_label, 'forkit' ) ?>"
			>

			<?php
			wp_nonce_field( MergePostController::NONCE_ACTION, MergePostController::NONCE_NAME ); ?>
		</div>
	<?php
	}

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

	public function alter_publishing_fields() {
		global $post;

		$this->alter_status_field();

		if ( Posts\is_archived_fork( $post ) ) { ?>
			<style>
				#delete-action,
				#publishing-action,
				#misc-publishing-actions,
				#minor-publishing-actions {
					display: none !important;
				}
			</style>
		<?php
		}
	}

	public function alter_status_field() {
		if ( true == $this->should_hide_wp_status_field() ) {
			return;
		} ?>

		<style>
			.misc-pub-post-status {
				display: none !important;
			}
		</style>

	<?php
	}

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

	function get_fork_post_button_label() {
		$value = 'Fork';
		return apply_filters( 'post_forking_fork_post_button_label', $value );
	}

	function get_merge_post_button_label() {
		$value = 'Publish';
		return apply_filters( 'post_forking_merge_post_button_label', $value );
	}

	function get_fork_exists_message() {
		$value = 'A fork of this post has been created. Further edits must be made on the forked version or they will be overwritten when it\'s published.';
		return apply_filters( 'post_forking_fork_exists_message', $value );
	}

	function get_editing_fork_message() {
		$value = 'You\'re viewing a fork created from another post. Changes you make here will be reflected on the source post when you publish.';
		return apply_filters( 'post_forking_editing_fork_message', $value );
	}

	function get_viewing_archived_fork_message() {
		$value = 'You\'re viewing an archived fork created from another post. Further changes must be made on the source post.';
		return apply_filters( 'post_forking_viewing_archived_fork_message', $value );
	}

	function get_edit_fork_label() {
		$value = 'Edit fork';
		return apply_filters( 'post_forking_edit_fork_link_label', $value );
	}

	function get_view_source_post_label() {
		$value = 'View source post';
		return apply_filters( 'post_forking_view_source_post_link_label', $value );
	}
}
