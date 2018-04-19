<?php
namespace TenUp\WPSafeEdit;

use \Exception;
use \InvalidArgumentException;

use \TenUp\WPSafeEdit\Posts\PublishingButtons;
use \TenUp\WPSafeEdit\Posts\Statuses;
use \TenUp\WPSafeEdit\Posts\Notices;
use \TenUp\WPSafeEdit\Posts\ArchivedForks;
use \TenUp\WPSafeEdit\Posts\Trash;
use \TenUp\WPSafeEdit\Posts\PostTypeSupport;
use TenUp\WPSafeEdit\Posts;
use TenUp\WPSafeEdit\Helpers;

/**
 * Class to manage post integrations.
 */
class Posts {

	const ORIGINAL_POST_ID_META_KEY = 'post-forking-original-post-id';

	/**
	 * Instance of the PublishingButtons class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\PublishingButtons
	 */
	public $publishing_buttons;

	/**
	 * Instance of the Statuses class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Statuses
	 */
	public $statuses;

	/**
	 * Instance of the Notices class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Notices
	 */
	public $notices;

	/**
	 * Instance of the ArchivedForks class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\ArchivedForks
	 */
	public $archived_forks;

	/**
	 * Instance of the Trash class;
	 *
	 * @var \TenUp\WPSafeEdit\Posts\Trash
	 */
	public $trash;

	public function __construct() {
		$this->publishing_buttons = new PublishingButtons();
		$this->statuses           = new Statuses();
		$this->notices            = new Notices();
		$this->archived_forks     = new ArchivedForks();
		$this->trash              = new Trash();
	}

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		$this->publishing_buttons->register();
		$this->statuses->register();
		$this->notices->register();
		$this->archived_forks->register();
		$this->trash->register();

		add_filter(
			'wp_insert_post_data',
			[ $this, 'filter_insert_post_data' ],
			999, 2
		);

		add_action(
			'safe_edit_add_post_type_support',
			[ $this, 'add_post_type_support' ]
		);

		add_filter( 
			'post_row_actions', 
			[ $this, 'modify_list_row_actions' ], 
			10, 2 
		);

		add_filter( 
			'page_row_actions', 
			[ $this, 'modify_list_row_actions' ], 
			10, 2 
		);
	}

	/**
	 * Filter post data before it is saved to the database.
	 *
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 * @return array
	 */
	public function filter_insert_post_data( $data, $postarr ) {
		global $post;

		if ( true !== Helpers\is_post( $post ) ) {
			return $data;
		}

		$valid_statuses = (array) Statuses::get_valid_fork_post_statuses();

		// Bail out if this post isn't a fork.
		if ( empty( $valid_statuses ) || ! in_array( $post->post_status, $valid_statuses ) ) {
			return $data;
		}

		$data = apply_filters( 'safe_edit_filter_insert_post_data', $data, $postarr );

		return $data;
	}

	/**
	 * Add forking support for one or more post types.
	 *
	 * @param string|array $post_types The post types to add support to.
	 * @return void
	 */
	public function add_post_type_support( $post_types ) {
		if ( is_array( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, PostTypeSupport::FORKING_FEATURE_NAME );
			}
		} elseif( is_string( $post_types ) ) {
			add_post_type_support( $post_types, PostTypeSupport::FORKING_FEATURE_NAME );
		}
	}
	/**
	 * Modify the action links for post lists.
	 *
	 * @param array    $actions The current action links.
	 * @param \WP_Post $post    The post the links are for.
	 * @return array The modified action links.
	 */
	public function modify_list_row_actions( $actions, $post ) {
		if ( 
			true !== Posts\post_type_supports_forking( $post ) ||
			true !== Posts\current_user_can_edit_fork( $post ) ||
			true !== Posts\post_has_open_fork( $post )
		) {
			return $actions;
		}

		$fork = Posts\get_open_fork_for_post( $post );

		if ( true !== Helpers\is_post( $post ) ) {
			return $actions;
		}

		$edit_draft_revision_action = array( 'draft_revision' => sprintf( 
			'<a href="%1$s">%2$s</a>',
			get_edit_post_link( $post->ID ),
			esc_html( __( 'Edit Draft Revision', 'wp-safe-edit' ) )
		) );

		// Insert the Edit Draft Revision link after the Edit link.
		$pos     = array_search( 'edit', array_keys( $actions ), true ) + 1;
		$actions = array_merge(
			array_slice( $actions, 0, $pos ),
			$edit_draft_revision_action,
			array_slice( $actions, $pos )
		);
		
		// Remove the edit link since further edits need to be done on the open draft revision.
		unset( $actions['edit'] );

		// Remove the quick edit link since further edits need to be done on the open draft revision.
		unset( $actions['inline hide-if-no-js'] );
	 
		return $actions;
	}
}
