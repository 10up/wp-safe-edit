import { Component } from 'react';
const { data, apiRequest } = wp;


if ( wp.editPost && 'undefined' !== typeof wp.editPost.PluginSidebarMoreMenuItem ) {
	const { __ } = wp.i18n;
	const { PluginSidebarMoreMenuItem } = wp.editPost;
	const { registerPlugin } = wp.plugins;
	const WP_SAFE_EDIT_NOTICE_ID = 'wp-safe-edit-notice';
	const WP_SAFE_EDIT_STATUS_ID = 'wp-safe-edit-status';

	class WPSafeEditSidebar extends Component {

		async forkPost( e ) {
			console.log( e );
			e.preventDefault();
			const id = document.getElementById( 'post_ID' ).value;
			const request = {
				path: 'wp-safe-edit/v1/fork/' + id,
				data: {
					nonce: gutenbergData.forknonce,
				},
				nonce: gutenbergData.forknonce,
				type: 'GET',
				dataType: 'json',
			}
			const result = await apiRequest( request );
			if ( result.data && result.data.shouldRedirect ) {
				document.location = result.data.redirectUrl;
			}
			console.log( result );
		}

		async mergeFork( e ) {
			const id = document.getElementById( 'post_ID' ).value;
			const request = {
				path: 'wp-safe-edit/v1/merge/' + id,
				data: {
					nonce: gutenbergData.forknonce,
				},
				nonce: gutenbergData.forknonce,
				type: 'GET',
				dataType: 'json',
			}

			const result = await apiRequest( request );
			if ( result.data && result.data.shouldRedirect ) {
				document.location = result.data.redirectUrl;
			}
		}

		componentDidMount() {
			const { subscribe } = wp.data;

			const initialPostStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );

			if ( 'wpse-draft' === initialPostStatus ) {
				// Watch for the publish event.
				const unssubscribe = subscribe( ( e ) => {
					const currentPostStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
					if ( 'publish' === currentPostStatus ) {
						unssubscribe();
						console.log('publish');
						setTimeout( () => {
							// Merge the fork.
							this.mergeFork();
						}, 300 );
					}
				} );
			}

			// Remove any previous notice.
			wp.data.dispatch( 'core/editor' ).removeNotice( WP_SAFE_EDIT_NOTICE_ID );

			// Display a notice to inform the user if this is a safe draft.
			var postStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
			if ( 'wpse-draft' === postStatus  ) {
				const message = __( 'A draft has been created and you can edit it below. Publish your changes to make them live.', 'wp-safe-edit' );
				wp.data.dispatch( 'core/editor' ).createSuccessNotice(
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
			var postStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
			var isPublished = wp.data.select( 'core/editor' ).isCurrentPostPublished();
			if ( ! isPublished || 'wpse-draft' === postStatus ) {
				return null;
			}
			return (
				<PluginSidebarMoreMenuItem>
						<span
							type="button"
							className="components-button components-icon-button components-menu-item__button"
							id="gutenberg-wpse-fork-post-button"
							value={ __( 'Save as Draft' ) }
							onClick= { this.forkPost }
						>{ __( 'Save as Draft' ) }</span>
				</PluginSidebarMoreMenuItem>
			);
		}
	};

	// Set up the plugin fills.
	registerPlugin( 'wp-safe-edit', {
		render: WPSafeEditSidebar,
		icon: null,
	} );

	// Display any message.
	if ( gutenbergData.message ) {
		data.dispatch( 'core/editor' ).createSuccessNotice( gutenbergData.message, {
			id: WP_SAFE_EDIT_NOTICE_ID,
		} );
	} else {
		// Remove any previous notice.
		wp.data.dispatch( 'core/editor' ).removeNotice( WP_SAFE_EDIT_NOTICE_ID );
	}
}
