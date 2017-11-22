<?php

namespace TenUp\PostForking\Posts;

/**
 * Class to manage custom post notices.
 */
class Notices {

	public function register() {
		add_action(
			'admin_notices',
			array( $this, 'render_notices' )
		);
	}

	public function render_notices() {
		$this->render_success_notices();
		$this->render_error_notices();
	}

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
