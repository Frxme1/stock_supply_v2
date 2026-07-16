( function( $ ) {
	$.validator.addMethod( "nonull", function( value, element ) {
		layout = $('#fl-field-layout select[name=layout]').val();
		if ( 'columns' !== layout ) {
			return true;
		}
		return parseInt( value ) >= 1;
	}, "Value must be one or greater" );

	FLBuilder.registerModuleHelper( 'post-grid', {

		rules: {
			post_columns: {
				nonull: true
			},
			post_columns_large: {
				nonull: true
			},
			post_columns_medium: {
				nonull: true
			},
			post_columns_responsive: {
				nonull: true
			},
		},

		resizeTimeout: null,

		init: function() {
			var form = $( '.fl-builder-settings' ),
				resizeFields = form.find( '#fl-field-border, #fl-field-title_typography, #fl-field-info_typography, #fl-field-content_typography' ),
				buttonBgColor = form.find( 'input[name=more_btn_bg_color]' ),
				icon = form.find('input[name=icon]'),
				layout = form.find( 'select[name=layout]' ),
				postType = form.find( '#fl-field-post_type' ).find('select'),
				showContent = form.find( 'select[name=show_content]' ),
				showImage = form.find( 'select[name=show_image]' ),
				dataSource = form.find( '#fl-field-data_source' ).find('select');
				userSource = form.find( '#fl-field-users' ).find('select'),
				equalHeightImage = form.find( 'select[name=equal_height_image]' ),
			layout.on( 'change', this._layoutChanged.bind( this ) );
			layout.on( 'change', this._equalHeightImageDependency.bind( this ) );
			postType.on( 'change', this._toggleEventsSection.bind( this ) );
			postType.on( 'change', this._toggleWooCommerceSection.bind( this ) );
			dataSource.on( 'change', this._toggleWooCommerceSection.bind( this ) );
			showContent.on( 'change', this._showContentChanged.bind(this) );
			showImage.on( 'change', this._featuredImageDependencyChanged.bind(this) );
			userSource.on( 'change', this._userSourceChanged.bind(this) );
			resizeFields.find( 'input' ).on( 'input', this._resizeLayout.bind( this ) );
			resizeFields.find( 'select' ).on( 'change', this._resizeLayout.bind( this ) );
			buttonBgColor.on( 'change', this._previewButtonBackground );
			icon.on( 'change', this._flipSettings );
			equalHeightImage.on( 'change', this._equalHeightImageDependency.bind( this ) );
			this._flipSettings();
			this._toggleEventsSection();
			this._toggleWooCommerceSection();
			this._featuredImageDependencyChanged();
			this._equalHeightImageDependency();
		},

		/**
		 * Layout Field Change event handler.
		 * @since 2.4.2
		 */
		_layoutChanged: function() {
			this._showContentChanged();
			this._featuredImageDependencyChanged();
		},

		_userSourceChanged: function() {
			current = $( '.fl-builder-settings' ).find( '#fl-field-users' ).find('select').val();
			if ( 'loggedin' === current || 'author' === current ) {
				$( '.fl-builder-settings' ).find( '#fl-field-users' ).find( '#as-selections-users' ).hide()
			} else {
				$( '.fl-builder-settings' ).find( '#fl-field-users' ).find( '#as-selections-users' ).show()
			}
		},

		/**
		 * Toggle 'The Calendar Events' section.
		 * @since TDB
		 */
		_toggleEventsSection: function () {
			var form = $( '.fl-builder-settings' ),
				tecEventsSection = form.find('#fl-builder-settings-section-events'),
				tecEventsButtonSection = form.find('#fl-builder-settings-section-events_button'),
				dataSource = form.find('#fl-field-data_source select').val(),
				selectedPostTypes = form.find( '#fl-field-post_type' ).find('select').val();

			if ( ( tecEventsSection.length <= 0 && tecEventsButtonSection.length <= 0 ) || 'custom_query' !== dataSource ) {
				return;
			}
			if ( $.inArray( 'tribe_events', selectedPostTypes ) > -1 ) {
				tecEventsSection.show();
				tecEventsButtonSection.show();
			} else {
				tecEventsSection.hide();
				tecEventsButtonSection.hide();
			}
		},

		/**
		 * Toggle WooCommerce section.
		 * @since 2.6
		 */
		_toggleWooCommerceSection: function() {
			var form = $('.fl-builder-settings'),
				dataSource = form.find('#fl-field-data_source select').val(),
				selectedPostTypes = form.find('#fl-field-post_type').find('select').val(),
				wooSection = form.find('#fl-builder-settings-section-woo'),
				wooStyleSection = form.find('#fl-builder-settings-section-woo_style'),
				wooButtonSection = form.find('#fl-builder-settings-section-woo_button'),
				isWooLayout = false;

			if ( wooSection.length <= 0 ) {
				return;
			}

			if ( 'main_query' === dataSource ) {
				isWooLayout = 'fl-theme-layout' === FLBuilderConfig.postType && $('body').hasClass('woocommerce');
			} else {
				isWooLayout = $.inArray('product', selectedPostTypes ) > -1;
			}

			wooStyleSection = wooStyleSection.length ? wooStyleSection : form.find('#fl-builder-settings-tab-style #fl-builder-settings-section-woo');
			if ( isWooLayout ) {
				wooSection.show();
				wooStyleSection.show();
				wooButtonSection.show();
			} else {
				wooSection.hide();
				wooStyleSection.hide();
				wooButtonSection.hide();
			}
		},

		/**
		 * Show Content Field Change event handler.
		 * @since 2.4.2
		 */
		_showContentChanged: function() {
			var form = $('.fl-builder-settings'),
				showContent = form.find('select[name=show_content]').val();

			this._switchContentFields( '0' === showContent );
		},

		/**
		 * Show Content Field Change event handler.
		 * @since 2.4.2
		 */
		_featuredImageDependencyChanged: function() {
			var form              = $('.fl-builder-settings'),
				layout            = form.find( 'select[name=layout]' ).val(),
				image             = form.find( 'select[name=show_image]' ).val(),
				showImage         = form.find( '#fl-field-show_image' ),
				imagePosition     = form.find( '#fl-field-image_position' ),
				gridImagePosition = form.find( '#fl-field-grid_image_position' ),
				imageSize         = form.find( '#fl-field-image_size' ),
				gridImageSpacing  = form.find( '#fl-field-grid_image_spacing' ),
				imageSpacing      = form.find( '#fl-field-image_spacing' ),
				imageWidth        = form.find( '#fl-field-image_width' ),
				imageFallback     = form.find( '#fl-field-image_fallback' );

			showImage.hide();
			imagePosition.hide();
			gridImagePosition.hide();
			imageSpacing.hide();
			gridImageSpacing.hide();
			imageSize.hide();
			imageWidth.hide();
			imageFallback.hide();

			if ( 'columns' === layout || 'grid' === layout ) {
				showImage.show();

				if ( '1' === image ) {
					gridImagePosition.show();
					gridImageSpacing.show();
					imageSize.show();
					imageFallback.show();
				}
			} else if ( 'gallery' ===  layout ) {
				imageFallback.show();
			} else if ( 'feed' ===  layout ) {
				showImage.show();

				if ( '1' === image ) {
					imagePosition.show();
					imageSpacing.show();
					imageWidth.show();
					imageSize.show();
					imageFallback.show();
				}
			}
		},

		_equalHeightImageDependency: function() {
			var $form              = $('.fl-builder-settings'),
				layout             = $form.find( 'select[name=layout]' ).val(),
				equalHeightImage   = $form.find( 'select[name=equal_height_image]' ).val(),
				$equalHeightImage  = $form.find( '#fl-field-equal_height_image' ),
				$fixedHeightImage  = $form.find( '#fl-field-image_height' ),
				$imageRatio        = $form.find( '#fl-field-image_aspect_ratio' );

			$equalHeightImage.hide();
			$fixedHeightImage.hide();
			$imageRatio.hide();

			if ( 'columns' === layout || 'grid' === layout ) {
				$equalHeightImage.show();

				if ( 'fixed' === equalHeightImage ) {
					$fixedHeightImage.show();
				} else if ( 'ratio' === equalHeightImage ) {
					$imageRatio.show();
				}
			}
		},

		/**
		 * Decide what to do with the Content Type and the Content Length fields
		 * depending on the layout.
		 * @since 2.4.2
		 */
		_switchContentFields: function( hide ) {
			var form = $('.fl-builder-settings'),
				layout = form.find('select[name=layout]').val(),
				contentType = form.find('select[name=content_type]').val(),
			    contentTypeField = form.find('#fl-field-content_type'),
				contentLengthField = form.find('#fl-field-content_length');

			// Hide both fields when Content == '0' (Hide).
			if ( hide ) {
				contentTypeField.hide();
				contentLengthField.hide();
				return;
			}

			if( 'columns' === layout || 'grid' === layout )  {
				contentTypeField.hide();
				contentLengthField.show();
				return;
			}

			if ( 'feed' === layout ) {
				contentTypeField.show();
				if ( 'full' === contentType ) {
					contentLengthField.hide();
				} else {
					contentLengthField.show();
				}
			}
		},

		_flipSettings: function() {
			var form  = $( '.fl-builder-settings' ),
					icon = form.find( 'input[name=icon]' );
			if ( -1 !== icon.val().indexOf( 'fad fa') ) {
				$('#fl-field-duo_color1').show();
				$('#fl-field-duo_color2').show();
				$('#fl-field-icon_color').hide();
				$('#fl-field-hover_color').hide()
			} else {
				$('#fl-field-duo_color1').hide();
				$('#fl-field-duo_color2').hide();
				$('#fl-field-icon_color').show();
				$('#fl-field-hover_color').show()
			}
		},

		_resizeLayout: function( e ) {
			clearTimeout( this.resizeTimeout );
			this.resizeTimeout = setTimeout( this._doResizeLayout.bind( this ), 250 );
		},

		_doResizeLayout: function( e ) {
			var form = $( '.fl-builder-settings' ),
				layout = form.find( 'select[name=layout]' ).val(),
				preview = FLBuilder.preview;

			if ( 'grid' !== layout || ! preview ) {
				return;
			}

			var masonry = preview.elements.node.find( '.fl-post-grid.masonry' ).data( 'masonry' );

			if ( masonry && masonry.layout ) {
				masonry.layout();
			}
		},

		_previewButtonBackground: function( e ) {
			var preview	= FLBuilder.preview,
				selector = preview.classes.node + ' a.fl-button, ' + preview.classes.node + ' a.fl-button:visited',
				form = $( '.fl-builder-settings:visible' ),
				style = form.find( 'select[name=more_btn_style]' ).val(),
				bgColor = form.find( 'input[name=more_btn_bg_color]' ).val();

			if ( 'flat' === style ) {
				if ( '' !== bgColor && bgColor.indexOf( 'rgb' ) < 0 ) {
					bgColor = '#' + bgColor;
				}
				preview.updateCSSRule( selector, 'background-color', bgColor );
				preview.updateCSSRule( selector, 'border-color', bgColor );
			} else {
				preview.delayPreview( e );
			}
		},
	} );

} )( jQuery );
