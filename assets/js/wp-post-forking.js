/*! WP Post Forking - v0.1.0
 * https://github.com/10up/post-forking
 * Copyright (c) 2017; * Licensed MIT */
( function( $, window, undefined ) {
	'use strict';
	var form            = null,
	    formActionField = null;

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

	var ForkSupport = function () {
		this.forkButton      = null;
	};

	ForkSupport.prototype = {
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
		},
	};

	$(document).ready(function () {
		var forkSupport = new ForkSupport();
		forkSupport.init();
	});

} )( jQuery, this );
