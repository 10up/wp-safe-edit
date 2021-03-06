import { Component } from 'react';
const { data, apiRequest, element } = wp;


if ( wp.editPost && 'undefined' !== typeof wp.editPost.PluginSidebarMoreMenuItem ) {
	const { __, setLocaleData } = wp.i18n;
	const { PluginSidebarMoreMenuItem } = wp.editPost;
	const { registerPlugin } = wp.plugins;
	const WP_SAFE_EDIT_NOTICE_ID = 'wp-safe-edit-notice';
	const WP_SAFE_EDIT_STATUS_ID = 'wp-safe-edit-status';

	class WPSafeEditSidebar extends Component {

		constructor( props ) {
			super( props );

			// Set up translations.
			setLocaleData( wpSafeEditGutenbergData.locale, 'wp-safe-edit' );
		}
		async forkPost( e ) {
			e.preventDefault();
			const id = document.getElementById( 'post_ID' ).value;
			const request = {
				path: 'wp-safe-edit/v1/fork/' + id,
				data: {
					nonce: wpSafeEditGutenbergData.forknonce,
				},
				nonce: wpSafeEditGutenbergData.forknonce,
				type: 'GET',
				dataType: 'json',
			}
			const result = await apiRequest( request );
			if ( result.data && result.data.shouldRedirect ) {
				document.location = result.data.redirectUrl;
			}
		}

		async mergeFork( e ) {
			const id = document.getElementById( 'post_ID' ).value;
			const request = {
				path: 'wp-safe-edit/v1/merge/' + id,
				data: {
					nonce: wpSafeEditGutenbergData.forknonce,
				},
				nonce: wpSafeEditGutenbergData.forknonce,
				type: 'GET',
				dataType: 'json',
			}

			const result = await apiRequest( request );
			if ( result.data && result.data.shouldRedirect ) {
				document.location = result.data.redirectUrl;
			}
		}

		componentDidMount() {
			const { subscribe } = data;

			const initialPostStatus = data.select( 'core/editor' ).getEditedPostAttribute( 'status' );

			if ( 'wpse-draft' === initialPostStatus || 'wpse-pending' === initialPostStatus ) {
				// Watch for the publish event.
				const unssubscribe = subscribe( ( e ) => {
					const currentPostStatus = data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
					if ( 'publish' === currentPostStatus ) {
						unssubscribe();
						setTimeout( () => {
							// Merge the fork.
							this.mergeFork();
						}, 300 );
					}
				} );
			} else {

				// Display any message except on for editing page
				if ( wpSafeEditGutenbergData.message ) {
					data.dispatch( 'core/notices' ).createSuccessNotice(
						wpSafeEditGutenbergData.message,
						{
							id: WP_SAFE_EDIT_NOTICE_ID,
						}
					);
				} else {
					// Remove any previous notice.
					data.dispatch( 'core/notices' ).removeNotice( WP_SAFE_EDIT_NOTICE_ID );
				}
			}

			// Remove any previous notice.
			data.dispatch( 'core/notices' ).removeNotice( WP_SAFE_EDIT_STATUS_ID );

			// Display a notice to inform the user if this is a safe draft.
			var postStatus = data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
			if ( 'wpse-draft' === postStatus  ) {
				const message = __( 'A draft has been created and you can edit it below. Publish your changes to make them live.', 'wp-safe-edit' );
				data.dispatch( 'core/notices' ).createSuccessNotice(
					message,
					{
						id: WP_SAFE_EDIT_STATUS_ID,
						isDismissible: false,
					}
				);
			}
		}

		render() {
			// Only show the button if the post is published and its not a safe edit draft already.
			var postStatus = data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
			var isPublished = data.select( 'core/editor' ).isCurrentPostPublished();
			if ( ! isPublished || 'wpse-draft' === postStatus ) {
				return null;
			}
			return (
				<PluginSidebarMoreMenuItem>
						<span
							type="button"
							className="components-button components-icon-button components-menu-item__button"
							id="gutenberg-wpse-fork-post-button"
							value={ __( 'Save as Draft', 'wp-safe-edit' ) }
							onClick= { this.forkPost }
						>{ __( 'Save as Draft', 'wp-safe-edit' ) }</span>
				</PluginSidebarMoreMenuItem>
			);
		}
	};

	// Set up the plugin fills.
	registerPlugin( 'wp-safe-edit', {
		render: WPSafeEditSidebar,
		icon: null,
	} );
}
