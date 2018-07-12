<?php
namespace TenUp\WPSafeEdit;

use TenUp\WPSafeEdit\API;
use TenUp\WPSafeEdit\Posts;
use TenUp\WPSafeEdit\Posts\Statuses;

class Plugin {

	/**
	 * The instance of the plugin class if it's been instantiated.
	 *
	 * @var \TenUp\WPSafeEdit\Plugin
	 */
	protected static $instance;

	/**
	 * The instance of the Statuses class.
	 *
	 * @var TenUp\WPSafeEdit\Posts\Statuses
	 */
	public $statuses;

	/**
	 * The instance of the Posts class.
	 *
	 * @var TenUp\WPSafeEdit\Posts
	 */
	public $posts;

	/**
	 * The instance of the Posts class.
	 *
	 * @var TenUp\WPSafeEdit\API
	 */
	public $api;

	public function __construct() {
		$this->posts = new Posts();
		$this->api   = new API();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->posts->register();
		$this->api->register();

		add_action(
			'init',
			array( $this, 'i18n' )
		);

		add_action(
			'init',
			array( $this, 'init' )
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_scripts' )
		);

		add_action(
			'enqueue_block_editor_assets',
			array( $this, 'enqueue_gutenberg_edit_scripts' )
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_styles' )
		);

		add_filter(
			'admin_body_class',
			array( $this, 'admin_body_class' )
		);

		do_action( 'safe_edit_loaded' );
	}

	/**
	 * Get the current instance of the plugin, or instantiate it if needed.
	 *
	 * @return \TenUp\WPSafeEdit\Plugin
	 */
	public static function get_instance() {
		if ( true !== self::$instance instanceof TenUp\WPSafeEdit\Plugin ) {
			self::$instance = new self();
			self::$instance->register();
		}

		return self::$instance;
	}

	/**
	 * Perform plugin activation tasks.
	 */
	public static function activate() {
		flush_rewrite_rules();
	}

	/**
	 * Perform plugin deactivation tasks.
	 */
	public static function deactivate() {

	}

	/**
	 * Registers the default textdomain.
	 *
	 * @uses apply_filters()
	 * @uses get_locale()
	 * @uses load_textdomain()
	 * @uses load_plugin_textdomain()
	 * @uses plugin_basename()
	 *
	 * @return void
	 */
	function i18n() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-safe-edit' );
		load_textdomain( 'wp-safe-edit', WP_LANG_DIR . '/wp-safe-edit/wp-safe-edit-' . $locale . '.mo' );
		load_plugin_textdomain( 'wp-safe-edit', false, plugin_basename( WP_SAFE_EDIT_PATH ) . '/languages/' );
	}

	/**
	 * Initializes the plugin and fires an action other plugins can hook into.
	 *
	 * @uses do_action()
	 *
	 * @return void
	 */
	function init() {
		do_action( 'safe_edit_init' );
	}

	function enqueue_admin_scripts() {
		$min     = '.min';
		$version = WP_SAFE_EDIT_VERSION;

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min     = '';
			$version = time();
		}

		wp_enqueue_script(
			'wp_safe_edit_admin',
			trailingslashit( WP_SAFE_EDIT_URL ) . "assets/js/wp-post-forking{$min}.js",
			array( 'jquery' ),
			$version,
			true
		);
	}

	// Enable Gutenberg support.
	function enqueue_gutenberg_edit_scripts() {
		wp_enqueue_script(
			'wp_safe_edit_gutrnberg_admin',
			trailingslashit( WP_SAFE_EDIT_URL ) . "dist/main.js",
			array( 'wp-blocks' ),
			WP_SAFE_EDIT_VERSION,
			true
		);
		wp_localize_script(
			'wp_safe_edit_gutrnberg_admin',
			'gutenbergData',
			array(
				'id'        => get_the_ID(),
				'forknonce' => wp_create_nonce( 'post-fork' ),
				'message'   => isset( $_GET['pf_success_message'] ) ?
					sanitize_text_field( $_GET['pf_success_message'] ) :
					false,
			)
		);
	}

	function enqueue_admin_styles() {
		$min     = '.min';
		$version = WP_SAFE_EDIT_VERSION;

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min     = '';
			$version = time();
		}

		wp_enqueue_style(
			'wp_safe_edit_admin',
			trailingslashit( WP_SAFE_EDIT_URL ) . "assets/css/wp-post-forking{$min}.css",
			array(),
			$version
		);
	}

	function admin_body_class( $classes ) {
		global $post;

		if ( ! $post ) {
			return $classes;
		}

		if ( 'wpse-draft' === $post->post_status ) {
			$classes .= ' wpse-draft ';
		}

		return $classes;
	}
}
