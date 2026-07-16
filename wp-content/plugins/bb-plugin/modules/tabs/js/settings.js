(function($){

	FLBuilder.registerModuleHelper('tabs', {

		init: function() {
			var form        = $( '.fl-builder-settings' ),
				layout 		= form.find( 'select[name=layout]' ),
				source       = form.find('select[name=source]'),
				contentType  = form.find('select[name=content_type]'),
				borderWidth = form.find( 'input[name=border_width]' );

			this._setMoreLink();
			source.on( 'change', this._setMoreLink );
			contentType.on( 'change', this._setMoreLink );
			layout.on( 'change', this._layoutChange );
			borderWidth.on( 'input', this._borderWidthChange );
		},

		_layoutChange: function() {
			var wrap	= FLBuilder.preview.elements.node.find( '.fl-tabs' ),
				form    = $( '.fl-builder-settings' ),
				layout 	= form.find( 'select[name=layout]' ).val();

			if ( 'horizontal' === layout ) {
				wrap.addClass( 'fl-tabs-horizontal' );
				wrap.removeClass( 'fl-tabs-vertical' );
			} else {
				wrap.addClass( 'fl-tabs-vertical' );
				wrap.removeClass( 'fl-tabs-horizontal' );
			}
		},

		_setMoreLink: function(e) {
			var form  = $( '.fl-builder-settings' ),
				contentSource = form.find( 'select[name=source]' ).val(),
				contentType = form.find( 'select[name=content_type]' ).val(),
				excerptLengthField = form.find('#fl-field-excerpt_length'),
				excerptMoreTextField = form.find('#fl-field-excerpt_more_text'),
				moreLinkField = form.find('#fl-field-more_link'),
				moreLinkTextField = form.find('#fl-field-more_link_text'),
				showExcerpt = ( 'post' === contentSource && 'post_excerpt' === contentType );
			
			if ( showExcerpt ) {
				excerptLengthField.show();
				excerptMoreTextField.show();
				moreLinkField.show();
				moreLinkTextField.show();
			} else {
				excerptLengthField.hide();
				excerptMoreTextField.hide();
				moreLinkField.hide();
				moreLinkTextField.hide();
			}
		},

		_borderWidthChange: function() {
			var preview		= FLBuilder.preview,
				form        = $( '.fl-builder-settings' ),
				layout 		= form.find( 'select[name=layout]' ).val(),
				borderWidth = form.find( 'input[name=border_width]' ).val(),
				selector	= preview.classes.node + ' .fl-tabs-labels .fl-tabs-label.fl-tab-active::after';

			if ( 'horizontal' === layout ) {
				preview.updateCSSRule( selector, {
					bottom: -borderWidth + 'px',
					height: borderWidth + 'px',
				} );
			} else {
				preview.updateCSSRule( selector, {
					right: -borderWidth + 'px',
					width: borderWidth + 'px',
				} );
			}
		}
	});

})(jQuery);
