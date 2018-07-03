import { Component } from 'react';
const { data, element, piRequest } = wp;


if ( wp.editPost && 'undefined' !== typeof wp.editPost.PluginSidebarMoreMenuItem ) {
	const { __ } = wp.i18n;
	const { PluginSidebarMoreMenuItem } = wp.editPost;
	const { registerPlugin } = wp.plugins;
	const WP_SAFE_EDIT_NOTICE_ID = 'wp-safe-edit-notice';

	class WPSafeEditSidebar extends Component {

		async forkPost( e ) {
			console.log( e );
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
			console.log( result );
		}

		render() {
			// Only show the button if the post is published and its not a safe edit draft already.
			var postStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'postStatus' );
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
