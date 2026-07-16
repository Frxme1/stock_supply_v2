(function($){
	/**
	 * Helper class for dealing with live previews for global styles.
	 *
	 * @class FLBuilderGlobalStylesPreview
	 * @since 2.8
	 */
	FLBuilderGlobalStylesPreview = {
		/**
		 * A timeout object for delaying the current preview refresh.
		 *
		 * @property {Object} timeout
		 */
		timeout: null,

		/**
		 * Initialize preview.
		 *
		 * @method init
		 */
		init: function() {
			this.initDefaultFieldPreviews();

			FLBuilder.addHook('clearGlobalPreviewTimeout', $.proxy(this.clearTimeout, this));
			FLBuilder.addHook('fieldConnectionUpdated', $.proxy(this.renderPreview, this));
		},

		/**
		 * Destory preview.
		 *
		 * @method destroy
		 */
		destroy: function() {
			FLBuilder.removeHook('clearGlobalPreviewTimeout', $.proxy(this.clearTimeout, this));
		},

		/**
		 * Clear timeout object
		 *
		 * @method clearTimeout
		 */
		clearTimeout: function() {
			if (this.timeout !== null) {
				clearTimeout(this.timeout);
			}
		},

		/**
		 * Initializes the default preview logic for each
		 * field in a settings form.
		 *
		 * @method initDefaultFieldPreviews
		 */
		initDefaultFieldPreviews: function() {
			if ( FLBuilderConfig.safemode ) {
				return false;
			}

			var fields = $('form.fl-builder-global-styles').find('.fl-field'),
				field  = null,
				i      = 0;

			for ( ; i < fields.length; i++ ) {
				field = fields.eq(i);
				this.initFieldRefreshPreview( field );
			}
		},

		/**
		 * Initializes the refresh preview for a field.
		 *
		 * @since 1.3.3
		 * @method initFieldRefreshPreview
		 * @param {Object} field The field to preview.
		 */
		initFieldRefreshPreview: function(field)
		{
			var fieldType = field.data('type'),
				callback  = $.proxy(this.renderPreview, this);

			switch(fieldType) {
				case 'align':
					field.find('input').on('change', callback);
				break;

				case 'align':
					field.find( 'input' ).on( 'change', callback );
				break;

				case 'border':
					field.find( 'select' ).on( 'change', callback );
					field.find( 'input[type=number]' ).on( 'input', callback );
					field.find( 'input[type=hidden]' ).on( 'change', callback );
				break;

				case 'color':
					field.find( '.fl-color-picker-value' ).on( 'change', callback );
				break;

				case 'dimension':
					field.find( 'input[type=number]' ).on( 'input', callback );
				break;

				case 'gradient':
					field.find( 'select' ).on( 'change', callback );
					field.find( '.fl-gradient-picker-angle' ).on( 'input', callback );
					field.find( '.fl-color-picker-value' ).on( 'change', callback );
					field.find( '.fl-gradient-picker-stop' ).on( 'input', callback );
				break;

				case 'photo':
					field.find( 'select' ).on( 'change', callback );
				break;

				case 'select':
					field.find( 'select' ).on( 'change', callback );
				break;

				case 'shadow':
					field.find( 'input' ).on( 'input', callback );
					field.find( '.fl-color-picker-value' ).on( 'change', callback );
				break;

				case 'text':
					field.find( 'input[type=text]' ).on( 'keyup', callback );
				break;

				case 'typography':
					field.find( 'select' ).on( 'change', callback );
					field.find( 'input[type=number]' ).on( 'input', callback );
					field.find( 'input[type=hidden]' ).on( 'change', callback );
				break;

				case 'unit':
					field.find( 'input[type=number]' ).on( 'input', callback );
				break;

				default:
					field.on('change', callback);
			}
		},

		/**
		 * Runs a preview refresh with a delay.
		 *
		 * @method renderPreview
		 */
		renderPreview: function(e)
		{
			var field           = $(e.target).closest('tr'),
				heading         = field.find('th'),
				widgetHeading   = $('.fl-builder-widget-settings .fl-builder-settings-title', window.parent.document),
				lightboxHeading = $('.fl-builder-settings .fl-lightbox-header', window.parent.document),
				loaderSrc       = FLBuilderLayoutConfig.paths.pluginUrl + 'img/ajax-loader-small.svg',
				loader          = $('<img class="fl-builder-preview-loader" src="' + loaderSrc + '" />');

			// clear prev timeout
			this.clearTimeout();

			// set new timeout
			this.timeout = setTimeout(function() {
				var form     = $('.fl-builder-settings-lightbox .fl-builder-settings', window.parent.document),
					valid    = form.validate().form(),
					settings = FLBuilder._getSettings(form);

				if ( ! valid ) {
					return;
				}

				// rm prev loader
				$('.fl-builder-preview-loader', window.parent.document).remove();

				// add new loader
				if (heading.length > 0) {
					heading.append(loader);
				} else if(widgetHeading.length > 0) {
					widgetHeading.append(loader);
				} else if(lightboxHeading.length > 0) {
					lightboxHeading.append(loader);
				}

				FLBuilderGlobalStylesPreview.renderCSS(settings, false);
			}, 1000);

			// font
			if ($(e.target).hasClass('fl-font-field-font')) {
				var font      = field.find('.fl-font-field-font'),
					selected  = font.find(':selected'),
					fontGroup = selected.parent().attr('label'),
					weight    = field.find('.fl-font-field-weight'),
					uniqueID  = field.attr('id').replace('fl-field', 'fl-global-field');

				if (fontGroup == 'Google' || fontGroup == 'Recently Used') {
					FLBuilderGlobalStylesPreview.buildFontStylesheet(uniqueID, font.val(), weight.val());
				}
			}
		},

		/**
		 * Runs a preview refresh with a delay.
		 *
		 * @method renderCSS
		 */
		renderCSS: function(settings, closePanel) {
			FLBuilder.ajax( {
				action: 'generate_global_style_css',
				global_settings: settings,
			}, (response) => {
				var win = FLBuilder.UIIFrame.getIFrameWindow();

				$('.fl-builder-preview-loader', window.parent.document).remove();
				$('#fl-builder-global-styles', win.document).empty().append(JSON.parse(response));

				if (closePanel) {
					FLBuilder._lightbox.close();

					setTimeout(() => {
						FLBuilder._updateLayout();
					}, 10);
				}
			});
		},

		/**
		 * Gets all fonts store insite FLBuilderPreview._fontsList and renders the respective
		 * link tag with Google Fonts.
		 *
		 * @method buildFontStylesheet
		 * @param  {String} id     The field unique ID.
		 * @param  {String} font   The selected font.
		 * @param  {String} weight The selected weight.
		 */
		buildFontStylesheet: function( id, font, weight ) {
			var url       = FLBuilderConfig.googleFontsUrl,
				href      = '',
				fontObj   = {},
				fontArray = {};

			// build the font family / weight object
			fontObj[ font ] = [ weight ];

			// adds to the list of fonts for this font setting
		    FLBuilderPreview._fontsList[ id ] = fontObj;

			// iterate over the keys of the FLBuilderPreview._fontsList object
			Object.keys( FLBuilderPreview._fontsList ).forEach( function( fieldFont ) {
				var field = FLBuilderPreview._fontsList[ fieldFont ];

				// iterate over the font / weight object
				Object.keys( field ).forEach( function( key ) {
					// get the weights of this font
					var weights = field[ key ];
					fontArray[ key ] = fontArray[ key ] || [];

					// remove duplicates from the values array
					weights = weights.filter( function( weight ) {
				        return fontArray[ key ].indexOf( weight ) < 0;
				    });

					fontArray[ key ] = fontArray[ key ].concat( weights );
				});
			});

			$.each(fontArray, function( font, weight ) {
				if ( 'Molle' === font ) {
					href += font + ':i|';
				} else {
					href += font + ':' + weight.join() + '|';
				}
			});

			// remove last character and replace spaces with plus signs
			href = url + href.slice( 0, -1 ).replace( ' ', '+' );

			if ($( '#fl-builder-google-fonts-preview' ).length < 1) {
				$( '<link>' )
					.attr( 'id', 'fl-builder-google-fonts-preview' )
					.attr( 'type', 'text/css' )
					.attr( 'rel', 'stylesheet' )
					.attr( 'href', href )
					.appendTo('head');
			} else {
				$( '#fl-builder-google-fonts-preview' ).attr( 'href', href );
			}
		},
	};

	/* Start the party!!! */
	var interval = setInterval( function() {
		if (typeof FLBuilder !== 'undefined') {
			clearInterval( interval );

			FLBuilder.addHook( 'initCustomPreview', function( e, config ) {
				if ( 'general' == config.type && 'styles' == config.id ) {
					FLBuilderGlobalStylesPreview.init();
				}
			} );
		}
	}, 10 );
})(jQuery);
