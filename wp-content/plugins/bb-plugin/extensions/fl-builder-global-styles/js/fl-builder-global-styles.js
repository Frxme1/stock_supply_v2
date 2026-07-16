(function($){

	/**
	 * The builder interface class.
	 *
	 * @since 2.8
	 * @class FLBuilder
	 */
	FLBuilderGlobalStyles = {
		/**
		 * Initializes the builder interface.
		 *
		 * @since 2.8
		 * @access private
		 * @method _init
		 */
		_init: function()
		{
			$('body', window.parent.document).on('click', '.fl-builder-global-styles .fl-builder-settings-save', FLBuilderGlobalStyles._onClickSave);
			$('body', window.parent.document).on('click', '.fl-builder-global-styles .fl-builder-settings-reset', FLBuilderGlobalStyles._onClickReset);
			$('body', window.parent.document).on('click', '.fl-builder-global-styles .fl-builder-settings-cancel', FLBuilderGlobalStyles._onClickCancel);
			$('body', window.parent.document).on('click', '.fl-builder-settings-tabs a', FLBuilderGlobalStyles._settingsTabClicked);
			$('body', window.parent.document).on('click', '.fl-global-color-copy', FLBuilderGlobalStyles._copyColorCode);

			// window storage
			window.flNotifications = [];
		},

		/**
		 * Generate random string compatible with css.
		 *
		 * @since 2.8
		 * @access private
		 * @method _randStr
		 */
		_randStr: function() {
			return Math.random().toString(36).replace(/[^a-z]+/g, '').substring(2, 12);
		},

		/**
		 * Converts an object into an array of objects.
		 *
		 * @since 2.8
		 * @access private
		 * @method _convertObjectToArray
		 */
		_convertObjectToArray: function(obj) {
			const arr = [];
			for (const key in obj) {
				if (obj.hasOwnProperty(key)) {
					const index = parseInt(key, 10); // Parse the numeric key as an integer.
					arr[index] = obj[key]; // Add the object at the specified index in the array.
				}
			}
			return arr;
		},

		/**
		 * Compares two arrays of objects for equality.
		 *
		 * @since 2.8
		 * @access private
		 * @method _compareArraysOfObjects
		 */
		_compareArraysOfObjects: function(arr1, arr2) {
			if (arr1.length !== arr2.length) {
				return false; // Different lengths, arrays are not equal.
			}

			for (let i = 0; i < arr1.length; i++) {
				if ( ! FLBuilderGlobalStyles._compareObjects(arr1[i], arr2[i]) ) {
					return false; // At least one pair of objects is not equal.
				}
			}
			return true; // All pairs of objects are equal.
		},

		/**
		 * Recursively compares two objects for equality.
		 *
		 * @since 2.8
		 * @access private
		 * @method _compareObjects
		 */
		_compareObjects: function(obj1, obj2) {
			for (const key in obj1) {
				if (obj1.hasOwnProperty(key) && obj2.hasOwnProperty(key)) {
					if (typeof obj1[key] === 'object' && typeof obj2[key] === 'object') {
						// If both values are objects, recursively compare them.
						if (!FLBuilderGlobalStyles._compareObjects(obj1[key], obj2[key])) {
							return false; // Sub-objects are not equal.
						}
					} else if (obj1[key] !== obj2[key]) {
						return false; // Values are not equal.
					}
				} else {
					return false; // Keys do not match.
				}
			}
			return true; // All keys and values are equal.
		},

		/**
		 * Check if global colors tab has any changes.
		 *
		 * @since 2.8
		 * @access private
		 * @method _settingsTabClicked
		 */
		_settingsTabClicked: function(e, esc)
		{
			var tab        = $( this ),
				form       = tab.closest( '.fl-builder-settings' ),
				id         = tab.attr( 'href' ).split( '#' ).pop(),
				settings   = FLBuilder._getSettings( form ),
				nextColors = FLBuilderGlobalStyles._convertObjectToArray( settings.colors ),
				prevColors = FLBuilderConfig.styles.colors;

			// If the clicked tab is already active, do nothing
			if ( tab.hasClass( 'fl-active' ) ) {
				return;
			}

			// If the 'esc' parameter is true, exit the function
			if ( true === esc ) {
				return;
			}

			// Check if the clicked tab is the 'Elements' tab
			if ( 'fl-builder-settings-tab-elements' == id ) {
				// Compare the previous colors with the current colors
				if ( ! FLBuilderGlobalStyles._compareArraysOfObjects( prevColors, nextColors ) ) {
					FLBuilder.confirm( {
						cssClass: ' fl-builder-reset-lightbox',
						message: FLBuilderStrings.saveGlobalColorsWarning,
						ok: function() {
							// Clear any global preview timeout
							FLBuilder.triggerHook( 'clearGlobalPreviewTimeout' );

							// Validate the form
							var valid = form.validate().form();

							// If the form is valid, save the global styles
							if (valid) {
								var colors = Object.values( nextColors );

								if ( colors.length > 0 ) {
									colors.forEach( ( color ) => {
										let qualifiedColor = '';

										if ( ! color.color.match( /^(var|rgb|hs(l|v))a?\(/ ) && ! color.color.startsWith( '#' ) ) {
											qualifiedColor = '#' + color.color;
										} else {
											qualifiedColor = FLBuilderColor( color.color ).toDisplay();
										}

										FLBuilderConfig.globalColorLabels[ 'global_color_' + color.uid ] = '<span class=\"prefix\">' + 'Global -' + '</span>' + color.label + '<span class=\"swatch\" style=\"background-color:' + qualifiedColor + ';\"></span>';
									} );
								}

								settings.colors = Object.values( colors );
								FLBuilderConfig.styles.colors = Object.values( colors );

								FL.Builder.data.getLayoutActions().saveGlobalStyles( settings );
								FLBuilder.addHook( 'didSaveGlobalStylesComplete', FLBuilderGlobalStyles._reloadPanel );
							}
						},
						strings: {
							ok: FLBuilderStrings.yesPlease,
							cancel: FLBuilderStrings.cancel
						}
					} );
				}
			}
		},

		/**
		 * Shows the global styles lightbox when the global
		 * style tab switched.
		 *
		 * @since 2.8
		 * @access private
		 * @method _reloadPanel
		 */
		_reloadPanel: function()
		{
			FLBuilder.removeHook( 'didSaveGlobalStylesComplete', FLBuilderGlobalStyles._reloadPanel );

			var interval = setInterval( function() {
				var menuClosed = $( '.fl-builder-settings.fl-builder-global-styles', window.parent.document ).length === 0;

				if ( menuClosed ) {
					clearInterval( interval );
					FLBuilderGlobalStyles._showPanel();
				}
			}, 250 );
		},

		/**
		 * Shows the global styles lightbox when the global
		 * style button is clicked.
		 *
		 * @since 2.8
		 * @access private
		 * @method _showPanel
		 */
		_showPanel: function()
		{
			FLBuilderSettingsForms.render( {
				id        : 'styles',
				className : 'fl-builder-global-styles',
				settings  : FLBuilderConfig.styles
			} );
		},

		/**
		 * Saves the global styles when the save button is clicked.
		 *
		 * @since 2.8
		 * @access private
		 * @method _onClickSave
		 */
		_onClickSave: function()
		{
			FLBuilder.triggerHook( 'clearGlobalPreviewTimeout' );

			var form     = $(this).closest('.fl-builder-settings'),
				valid    = form.validate().form(),
				settings = FLBuilder._getSettings( form );

			if ( valid ) {
				var colors = Object.values( settings.colors );

				if ( colors.length > 0 ) {
					colors.forEach( ( color ) => {
						let qualifiedColor = '';

						if ( ! color.color.match( /^(var|rgb|hs(l|v))a?\(/ ) && ! color.color.startsWith( '#' ) ) {
							qualifiedColor = '#' + color.color;
						} else {
							qualifiedColor = FLBuilderColor( color.color ).toDisplay();
						}

						FLBuilderConfig.globalColorLabels[ 'global_color_' + color.uid ] = '<span class=\"prefix\">' + 'Global -' + '</span>' + color.label + '<span class=\"swatch\" style=\"background-color:' + qualifiedColor + ';\"></span>';
					} );
				}

				FLBuilderConfig.styles.colors = Object.values( colors );
				FL.Builder.data.getLayoutActions().saveGlobalStyles( settings );
			}
		},

		/**
		 * Saves the global styles when the save button is clicked.
		 *
		 * @since 2.8
		 * @access private
		 * @method _onSaveComplete
		 * @param {String} response
		 */
		_onSaveComplete: function( response )
		{
			FLBuilder.triggerHook( 'didSaveGlobalStylesComplete', FLBuilder._jsonParse( response ) );
			FLBuilderGlobalStylesPreview.destroy();
			FLBuilderGlobalStylesPreview.renderCSS(null, true);
		},

		/**
		 * Reset the global styles when the reset button is clicked.
		 *
		 * @since 2.8
		 * @access private
		 * @method _onClickReset
		 */
		_onClickReset: function( e )
		{
			e.stopImmediatePropagation();

			FLBuilder.confirm( {
				cssClass: ' fl-builder-reset-lightbox',
				message: FLBuilderStrings.resetSettingsMessage,
				ok: function() {
					FLBuilder.ajax( {
						action  : 'reset_global_styles',
					}, function() {
						// reset config
						FLBuilderConfig.styles = {
							colors: [ { label: '', color: '' } ]
						};

						setTimeout( function() {
							// reload preview
							FLBuilder._updateLayout();

							// reload panel
							FLBuilder.triggerHook('showGlobalStyles');
						}, 500 );
					} );
				},
				strings: {
					ok: FLBuilderStrings.reset,
					cancel: FLBuilderStrings.cancel
				}
			} );
		},

		/**
		 * Discard global styles when the cancel button is clicked.
		 *
		 * @since 2.8
		 * @access private
		 * @method _onClickCancel
		 */
		_onClickCancel: function()
		{
			FLBuilder.triggerHook( 'clearGlobalPreviewTimeout' );
			FLBuilderGlobalStylesPreview.renderCSS(null, true);
		},

		/**
		 * Convert label to key, similar to sanitize_key.
		 *
		 * @since 2.8
		 * @access private
		 * @method _labelToKey
		 */
		_labelToKey: function(label) {
			// Check if label is empty
			if (label.length === 0) {
				return '';
			}

			// Replace underscores and spaces with dashes, convert to lowercase, and remove leading/trailing spaces
			label = label.replace(/[_\s]/g, '-').toLowerCase().trim();

			// Remove any characters that are not alphanumeric or a dash
			label = label.replace(/[^A-Za-z0-9\-]/g, '');

			return label;
		},

		/**
		 * Copy color code.
		 *
		 * @since 2.8
		 * @access private
		 * @method _copyColorCode
		 */
		_copyColorCode: function( e ) {
			var form   = $(e.target).closest('.fl-builder-settings')
				prefix = form.find('#fl-field-prefix').find('[type=text]').val(),
				label  = $(e.target).parents('.fl-global-color-field').find('.fl-global-color-field-input').val(),
				string = '',
				offset = $(e.target).offset(),
				uid    = FLBuilderGlobalStyles._randStr();

			// If label is empty, return early
			if (label.length === 0) {
				return;
			}

			// Convert label to a valid CSS variable key
			label = FLBuilderGlobalStyles._labelToKey( label );

			// If prefix is empty, set it to a default value
			if (prefix.length === 0) {
				prefix = 'fl-global';
			} else {
				// Convert prefix to a valid CSS variable key
				prefix = FLBuilderGlobalStyles._labelToKey(prefix);
			}

			prefix = '--' + prefix;
			string = 'var(' + prefix + '-' + label + ')';

			// Copy the string to the clipboard
			FLBuilderSettingsCopyPaste._setClipboard(string, true);

			// Push notification UID to storage.
			window.flNotifications.push(uid);

			// Add confirmation message to body
			$('body', window.parent.document).append('<div class="fl-builder-confirmation-tooltip fl-builder-confirmation-tooltip-' + uid + '" >' + FLBuilderStrings.copiedToClipboard + '</div>');

			// Set the CSS properties for the confirmation message
			$('.fl-builder-confirmation-tooltip-' + uid).css(offset);

			// Fade in the confirmation message
			$('.fl-builder-confirmation-tooltip-' + uid).fadeIn();

			// Schedule the confirmation message to fade out after 3 seconds
			setTimeout(function() {
				var uid = window.flNotifications.shift();

				$('.fl-builder-confirmation-tooltip-' + uid).fadeOut(500, function() {
					$(this).remove();
				});
			}, 3000);
		}
	}

	/* Start the party!!! */
	$(function(){
		FLBuilderGlobalStyles._init();
	});

})(jQuery);
