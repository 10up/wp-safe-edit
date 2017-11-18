/**
 * WP Post Forking
 * https://github.com/10up/post-forking
 *
 * Copyright (c) 2017 Michael Phillips
 * Licensed under the MIT license.
 */

( function( $, window, undefined ) {
	'use strict';
	var form            = null,
	    formActionField = null,
	    postID          = 0,
	    blogID          = 0;

	function getPostID() {
		if ( ! postID ) {
			postID = $( document.getElementById('post_ID') ).val() || 0;
		}

		return postID;
	}

	function getblogID() {
		if ( ! blogID ) {
			blogID = typeof window.autosaveL10n !== 'undefined' && window.autosaveL10n.blog_id;
		}

		return blogID;
	}

	function getPostForm() {
		if ( ! form ) {
			form = document.querySelector('form#post');
		}

		return form;
	}

	function getPostFormActionField() {
		if ( ! formActionField ) {
			var form = getPostForm();

			formActionField = form.querySelector('input[name=action]');
		}

		return formActionField;
	}

	/**
	 * Clear the stored session data in the browser for a post.
	 */
	function clearStoredPostData() {
		var postID     = getPostID(),
		    storedData = getStoredPostData();

		if ( ! postID || ! storedData ) {
			return;
		}

		storedData = JSON.parse( storedData );

		if ( ! storedData.hasOwnProperty( 'post_' + postID ) ) {
			return;
		}

		delete storedData[ 'post_' + postID ];

		saveStoredPostData( storedData );
	}

	function getStoredPostData() {
		var blogID = getblogID();

		if (
			! window.sessionStorage ||
			! blogID
		) {
			return;
		}

		return window.sessionStorage.getItem( 'wp-autosave-' + blogID );
	}

	function saveStoredPostData( data ) {
		var blogID = getblogID();

		if (
			! window.sessionStorage ||
			! blogID
		) {
			return;
		}

		var key = 'wp-autosave-' + blogID;
		window.sessionStorage.setItem( key, JSON.stringify( data ) );
	}

	var ForkPostSupport = function () {
		this.forkButton = null;
	};

	var MergePostSupport = function () {
		this.mergeButton = null;
	};

	ForkPostSupport.prototype = {
		init: function () {
			this.setupEvents();
		},

		setupEvents: function() {
			this.forkButton = this.getForkButton();

			if ( this.forkButton ) {
				$(this.forkButton).on('click', $.proxy(
					this.didClickForkButton, this
				));
			}
		},

		getForkButton: function() {
			if ( ! this.forkButton ) {
				this.forkButton = document.getElementById('pf-fork-post-button');
			}

			return this.forkButton;
		},

		didClickForkButton: function (event) {
			var form            = getPostForm(),
			    formActionField = getPostFormActionField();

			if ( ! form || ! formActionField ) {
				event.preventDefault();
				return false;
			}

			// Change the action sent to post.php
			formActionField.setAttribute('value', 'fork_post');

			// Clear the stored session data for this post to prevent the "The backup of this post in your browser is different from the version below" notice from showing after you fork a post.
			clearStoredPostData();
		},
	};

	MergePostSupport.prototype = {
		init: function () {
			this.setupEvents();
		},

		setupEvents: function() {
			this.mergeButton = this.getMergeButton();

			if ( this.mergeButton ) {
				$(this.mergeButton).on('click', $.proxy(
					this.didClickMergeButton, this
				));
			}
		},

		getMergeButton: function() {
			if ( ! this.mergeButton ) {
				this.mergeButton = document.getElementById('pf-merge-post-button');
			}

			return this.mergeButton;
		},

		didClickMergeButton: function (event) {
			var form            = getPostForm(),
			    formActionField = getPostFormActionField();

			if ( ! form || ! formActionField ) {
				event.preventDefault();
				return false;
			}

			// Change the action sent to post.php
			formActionField.setAttribute('value', 'merge_post');

			// Clear the stored session data for this post to prevent the "The backup of this post in your browser is different from the version below" notice from showing after you merge a post.
			clearStoredPostData();
		},
	};

	$(document).ready(function () {
		var forkPostSupport = new ForkPostSupport();
		forkPostSupport.init();

		var mergePostSupport = new MergePostSupport();
		mergePostSupport.init();
	});

} )( jQuery, this );
