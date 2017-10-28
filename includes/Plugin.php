<?php
namespace TenUp\PostForking;

use TenUp\PostForking\Posts;
use TenUp\PostForking\Posts\Statuses;

class Plugin {

	/**
	 * The instance of the plugin class if it's been instantiated.
	 *
	 * @var \TenUp\PostForking\Plugin
	 */
	protected static $instance;

	/**
	 * The instance of the Statuses class.
	 *
	 * @var TenUp\PostForking\Posts\Statuses
	 */
	public $statuses;

	/**
	 * The instance of the Posts class.
	 *
	 * @var TenUp\PostForking\Posts
	 */
	public $posts;

	public function __construct() {
		$this->statuses = new Statuses();
		$this->posts    = new Posts();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->statuses->register();
		$this->posts->register();

		add_action(
			'init',
			[ $this, 'i18n' ]
		);

		add_action(
			'init',
			[ $this, 'init' ]
		);

		do_action( 'post_forking_loaded' );

		// FOR TESTING REMOVE ME
		add_action( 'template_redirect', function() {
			// $forker = new \TenUp\PostForking\Forking\PostForker();
			// $forker->fork( get_post( 1 ) );
			//
			//echo '<pre>'; var_dump( \TenUp\PostForking\Posts\post_can_be_merged(1) ); echo '</pre>';
		} );
	}

	/**
	 * Get the current instance of the plugin, or instantiate it if needed.
	 *
	 * @return \TenUp\PostForking\Plugin
	 */
	public static function get_instance() {
		if ( true !== self::$instance instanceof TenUp\PostForking\Plugin ) {
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
		$locale = apply_filters( 'plugin_locale', get_locale(), 'forkit' );
		load_textdomain( 'forkit', WP_LANG_DIR . '/forkit/forkit-' . $locale . '.mo' );
		load_plugin_textdomain( 'forkit', false, plugin_basename( FORKIT_PATH ) . '/languages/' );
	}

	/**
	 * Initializes the plugin and fires an action other plugins can hook into.
	 *
	 * @uses do_action()
	 *
	 * @return void
	 */
	function init() {
		do_action( 'post_forking_init' );
	}
}
