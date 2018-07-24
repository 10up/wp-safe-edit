<?php

namespace TenUp\WPSafeEdit\Posts;

/**
 * Class to manage custom post notices.
 */
class Notices {

	/**
	 * Add needed hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action(
			'admin_notices',
			array( $this, 'render_notices' )
		);
	}

	/**
	 * Render our notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		$this->render_success_notices();
		$this->render_error_notices();
	}

	/**
	 * Render the success notices.
	 *
	 * @return void
	 */
	public function render_success_notices() {
		$notice = sanitize_text_field(
			rawurldecode(
				filter_input( INPUT_GET, 'pf_success_message' )
			)
		);

		if ( empty( $notice ) ) {
			return;
		} ?>

		<div class="notice notice-success">
			<p><?php echo wp_kses_post( $notice ); ?></p>
		</div>

	<?php
	}

	/**
	 * Render the error notices.
	 *
	 * @return void
	 */
	public function render_error_notices() {
		$notice = sanitize_text_field(
			rawurldecode(
				filter_input( INPUT_GET, 'pf_error_message' )
			)
		);

		if ( empty( $notice ) ) {
			return;
		} ?>

		<div class="notice notice-error">
			<p><?php echo wp_kses_post( $notice ); ?></p>
		</div>

	<?php
	}
}
