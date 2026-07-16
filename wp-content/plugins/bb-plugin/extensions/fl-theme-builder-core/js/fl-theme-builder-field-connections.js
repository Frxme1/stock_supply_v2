( function( $ ) {

	/**
	 * Handles logic for field connections.
	 *
	 * @class FLThemeBuilderFieldConnections
	 * @since 1.0
	 */
	FLThemeBuilderFieldConnections = {

		/**
		 * Cached data for field connection menus.
		 *
		 * @since 1.0
		 * @access private
		 * @property {Object} _menus
		 */
		_menus : {},

		/**
		 * Initializes field connections.
		 *
		 * @since 1.0
		 * @access private
		 * @method _init
		 */
		_init: function()
		{
			this._bind();
		},

		/**
		 * Binds field connection events.
		 *
		 * @since 1.0
		 * @access private
		 * @method _bind
		 */
		_bind: function()
		{
			FLBuilder.addHook( 'settings-form-init', this._initSettingsForms );

			$( 'body', window.parent.document ).on( 'click', this._closeMenus );
			$( 'body', window.parent.document ).delegate( '.fl-field-connections-toggle .fas', 'click', this._menuToggleClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connections-property', 'click', this._menuItemClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connections-property-token', 'click', this._menuItemTokenClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connections-search', 'keyup', this._menuSearchKeyup );
			$( 'body', window.parent.document ).delegate( '.fl-field-connection-remove', 'click', this._removeConnectionClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connection-edit', 'click', this._editConnectionClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connection-settings .fl-builder-settings-save', 'click', this._saveSettingsFormClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connection-settings .fl-builder-settings-cancel', 'click', this._cancelSettingsFormClicked );
			$( 'body', window.parent.document ).delegate( '.fl-field-connection', 'click', this._fieldConnectionClicked );
		},

		/**
		 * Callback for initializing settings forms.
		 *
		 * @since 1.0
		 * @access private
		 * @method _initSettingsForms
		 */
		_initSettingsForms: function()
		{
			$( '.fl-field-connection.fl-field-connection-visible' ).each( function() {
				var connection = $( this );
				var field = connection.closest( '.fl-field' );

				if ( connection.find( '.fl-field-connection-value' ).length ) {
					// Ignore validation for flat field connections.
					field.find( 'input, textarea, select' ).addClass( 'fl-ignore-validation' );
				} else {
					// Ignore validation for compound field connections.
					connection.parent().find( 'input, textarea, select' ).addClass( 'fl-ignore-validation' );
				}
			} );
		},

		/**
		 * Callback for when the body is clicked to close
		 * any open connections menus.
		 *
		 * @since 1.0
		 * @access private
		 * @method _closeMenus
		 * @param {Object} e The event object.
		 */
		_closeMenus: function( e )
		{
			var target;

			if ( 'undefined' != typeof e ) {

				target = $( e.target );

				if ( target.closest( '.fl-field-connections-toggle' ).length ) {
					return;
				}
				if ( target.closest( '.fl-field-connections-menu' ).length ) {
					return;
				}
			}

			$( '.fl-field-connections-menu' ).remove();
			$( '.fl-field-connections-toggle-open' ).removeClass( 'fl-field-connections-toggle-open' );
		},

		/**
		 * Callback for when the menu toggle is clicked to
		 * show or hide a connections menu.
		 *
		 * @since 1.0
		 * @access private
		 * @method _menuToggleClicked
		 * @param {Object} e The event object.
		 */
		_menuToggleClicked: function( e )
		{
			var target     = $( this ),
				field      = target.parents( '.fl-field' ),
				fieldId    = field.attr( 'id' ),
				control    = target.parents( '.fl-field-control' ),
				wrapper    = target.parents( '.fl-field-control-wrapper' ),
				toggle     = target.closest( '.fl-field-connections-toggle' ),
				isOpen     = toggle.hasClass( 'fl-field-connections-toggle-open' ),
				connection = control.find( '.fl-field-connection' ),
				menu       = $( '.fl-field-connections-menu[data-field="' + fieldId + '"]' ),
				template   = wp.template( 'fl-field-connections-menu' ),
				menuData   = FLThemeBuilderFieldConnections._menus[ fieldId.replace( 'fl-field-', '' ) ],
				groups     = menu.find( '.fl-field-connections-groups' ),
				search     = menu.find( '.fl-field-connections-search' );

			FLThemeBuilderFieldConnections._closeMenus();

			if ( ! isOpen ) {

				toggle.addClass( 'fl-field-connections-toggle-open' );

				if ( ! menu.length ) {

					$( 'body', window.parent.document ).append( template( {
						fieldId   : fieldId,
						fieldType : field.attr( 'data-type' ),
						menuData  : menuData
					} ) );

					menu   = $( '.fl-field-connections-menu[data-field="' + fieldId + '"]' );
					groups = menu.find( '.fl-field-connections-groups' );
					search = menu.find( '.fl-field-connections-search' );
				}

				new Tether( {
					element          : menu[0],
					target           : wrapper[0],
					attachment       : 'top left',
					targetAttachment : 'top left',
					constraints: [ {
						to  : wrapper[0],
						attachment: 'together',
						pin: ['top']
				    } ]
				} );

				menu.css( 'width', wrapper.width() );
				menu.fadeIn( 200 );

				if ( connection.is( ':visible' ) ) {
					menu.removeClass( 'fl-field-connection-tokens-visible' );
				} else {
					menu.addClass( 'fl-field-connection-tokens-visible' );
				}

				if ( groups.height() > menu.height() ) {
					search.show();
				}
			}
		},

		/**
		 * Callback for when a connection menu item is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _menuItemClicked
		 * @param {Object} e The event object.
		 */
		_menuItemClicked: function( e )
		{
			var item       = $( e.target ).closest( '.fl-field-connections-property' ),
				menu       = item.closest( '.fl-field-connections-menu' ),
				fieldId    = menu.attr( 'data-field' ),
				field      = $( '#' + fieldId ),
				label      = item.find( '.fl-field-connections-property-label' ).html(),
				formId     = item.attr( 'data-form' ),
				config     = {
					object   : item.attr( 'data-object' ),
					property : item.attr( 'data-property' ),
					field    : field.attr( 'data-type' ),
					settings : null
				};

			FLThemeBuilderFieldConnections._connectField( field, label, config, formId );
		},

		/**
		 * Connect a field.
		 *
		 * @since 2.8
		 */
		_connectField: function( field, label, config, formId )
		{
			var connection = field.closest( '.fl-field' ).find( '.fl-field-connection' ),
				global     = field.closest( '.fl-builder-settings' ).hasClass( 'fl-builder-global-styles' );

			if ( field.hasClass( 'fl-field' ) ) {
				field.find( 'input, textarea, select' ).addClass( 'fl-ignore-validation' );
				field.find( '.fl-field-connection-value' ).val( JSON.stringify( config ) ).trigger( 'change' );
				connection.find( '.fl-field-connection-label' ).html( label );
				connection.fadeIn( 200 ).addClass( 'fl-field-connection-visible' );
			} else {
				FLThemeBuilderCompoundConnections._connectField( field, label, config );
			}

			FLThemeBuilderFieldConnections._closeMenus();

			if ( global ) {
				FLBuilder.triggerHook( 'fieldConnectionUpdated', field );
			}

			if ( 'undefined' == typeof formId ) {
				connection.removeAttr( 'data-form' );
				FLThemeBuilderFieldConnections._triggerPreview( { target : field } );
			} else {
				connection.attr( 'data-form', formId );
				connection.addClass( 'fl-field-connection-clear-on-cancel' );
				FLThemeBuilderFieldConnections._showSettingsForm( field, formId, config );

				if ( FLBuilder.isBlockEditor() ) {
					FLThemeBuilderFieldConnections._triggerPreview( { target : field } );
				}
			}
		},

		/**
		 * Callback for when a connection menu item token is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _menuItemTokenClicked
		 * @param {Object} e The event object.
		 */
		_menuItemTokenClicked: function( e )
		{
			var target     = $( e.target ),
				menu       = target.closest( '.fl-field-connections-menu' ),
				fieldId    = menu.attr( 'data-field' ),
				field      = $( '#' + fieldId ),
				connection = field.find( '.fl-field-connection' ),
				item       = target.closest( '.fl-field-connections-property' ),
				formId     = item.attr( 'data-form' ),
				token      = target.closest( '.fl-field-connections-property-token' ).attr( 'data-token' );

			if ( 'undefined' == typeof formId ) {
				token = '[wpbb ' + token + ']';
				FLThemeBuilderFieldConnections._insertMenuItemToken( field, token );
			} else {

				connection.attr( 'data-token', token );

				FLThemeBuilderFieldConnections._showSettingsForm( field, formId, {
					object   : token.split( ':' )[0],
					property : token.split( ':' )[1],
					field    : field.attr( 'data-type' ),
					settings : null
				} );
			}

			FLThemeBuilderFieldConnections._closeMenus();
			e.stopPropagation();
		},

		/**
		 * Inserts a menu item token for a field.
		 *
		 * @since 1.0
		 * @access private
		 * @method _insertMenuItemToken
		 * @param {Object} field
		 * @param {String} token
		 */
		_insertMenuItemToken: function( field, token )
		{
			var type    = field.attr( 'data-type' ),
				input   = null,
				value   = null;

			if ( 'text' == type || 'textarea' == type || ( 'editor' == type && field.find( '.html-active' ).length > 0 ) ) {

				if ( 'text' == type ) {
					input = field.find( 'input[type=text]' );
				} else if ( 'textarea' == type || 'editor' == type ) {
					input = field.find( 'textarea' );
				}

				value = input.val();

				if ( input[0].selectionStart || 0 === input[0].selectionStart ) {
					input.val( value.substring( 0, input[0].selectionStart ) + token + value.substring( input[0].selectionStart ) );
				} else {
					input.val( value + ' ' + token );
				}

				input.trigger( 'input' );
			} else if ( 'editor' == type ) {
				window.parent.send_to_editor( token );
			} else if ( 'code' == type ) {
				field.data( 'editor' ).insert( token );
			}
		},

		/**
		 * Callback for when a key is pressed for the
		 * connections menu search.
		 *
		 * @since 1.0
		 * @access private
		 * @method _menuSearchKeyup
		 * @param {Object} e The event object.
		 */
		_menuSearchKeyup: function( e )
		{
			var input = $( e.target ),
				value = input.val().toLowerCase(),
				menu  = input.closest( '.fl-field-connections-menu' );

			menu.find( '.fl-field-connections-group' ).each( function() {

				var group = $( this ),
					label = group.find( '.fl-field-connections-group-label' ),
					props = group.find( '.fl-field-connections-property' );

				if ( label.text().toLowerCase().indexOf( value ) > -1 ) {
					props.attr( 'data-hidden', 0 );
					props.show();
					group.show();
				} else {
					props.each( function() {

						var prop  = $( this ),
							label = prop.find( '.fl-field-connections-property-label' );

						if ( label.text().toLowerCase().indexOf( value ) > -1 ) {
							prop.attr( 'data-hidden', 0 );
							prop.show();
						} else {
							prop.attr( 'data-hidden', 1 );
							prop.hide();
						}
					} );
				}

				if ( group.find( '.fl-field-connections-property[data-hidden=0]' ).length ) {
					group.show();
				} else {
					group.hide();
				}
			} );

			e.preventDefault();
			e.stopPropagation();
		},

		/**
		 * Shows a settings form for a field connection.
		 *
		 * @since 1.0
		 * @access private
		 * @method _showSettingsForm
		 * @param {Object} field The field for this connection.
		 * @param {String} formId The form config.
		 * @param {Object} config The form config.
		 */
		_showSettingsForm: function( field, formId, config )
		{
			var value = field.find( '.fl-field-connection-value' ).val();
			var connectionType = value ? 'object' : 'token'

			formId = undefined === formId ? field.find( '.fl-field-connection' ).attr( 'data-form' ) : formId;
			config = undefined === config ? JSON.parse( field.find( '.fl-field-connection-value' ).val() ) : config;

			if ( FLBuilder.isBlockEditor() ) {
				FLBuilder.triggerHook( 'renderFieldConnectionSettings', [
					formId,
					field,
					connectionType,
					config.settings
				] );
				return;
			}

			var lightbox = FLBuilder._openNestedSettings( {
				className : 'fl-builder-lightbox fl-field-connection-settings'
			} );

			field.addClass( 'fl-field-connection-editing' );

			FLBuilder.ajax( {
				action   : 'render_connection_settings',
				object   : config.object,
				property : config.property,
				type     : formId,
				settings : config.settings
				}, function( response ) {

					var data = JSON.parse( response );

					lightbox._node.find( '.fl-lightbox-content' ).html( data.html );

					FLBuilder._initSettingsForms();
					$( document ).trigger( '_initSettingsFormsComplete' );
				} );
		},

		/**
		 * Saves a connection settings form when the save
		 * button is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _saveSettingsFormClicked
		 * @param {Object} e
		 */
		_saveSettingsFormClicked: function( e )
		{
			var form = $( '.fl-field-connection-settings form' );
			var field = $( '.fl-field-connection-editing' );
			var connection = field.find( '.fl-field-connection' );

			FLThemeBuilderFieldConnections._saveSettings( form, field );
			FLBuilder._closeNestedSettings();

			field.removeClass( 'fl-field-connection-editing' );
			connection.removeClass( 'fl-field-connection-clear-on-cancel' );
		},

		/**
		 * Saves connection settings.
		 *
		 * @since 2.9
		 * @param {Object} form
		 * @param {Object} field
		 */
		_saveSettings: function( form, field )
		{
			var settings   = FLBuilder._getSettings( form ),
				connection = field.find( '.fl-field-connection' ),
				value      = field.find( '.fl-field-connection-value' ),
				val        = value.val(),
				parsed     = null,
				shortcode  = '',
				token      = connection.attr( 'data-token' ),
				prop       = null;

			if ( '' != val ) {
				parsed          = JSON.parse( val );
				parsed.settings = FLThemeBuilderFieldConnections._fixDependency( settings );
				value.val( JSON.stringify( parsed ) ).trigger( 'change' );
				FLThemeBuilderFieldConnections._triggerPreview( { target : field } );
			} else {
				shortcode = '[wpbb ' + token;

				for ( prop in settings ) {
					if ( ! form.find( '[name=' + prop + ']:visible' ).length || 'name_custom' === prop ) {
						continue;
					}
					shortcode += ' ' + prop.replace( /([a-z]+)_display/, 'display' ) + "='" + settings[ prop ] + "'";
				}

				shortcode += ']';

				FLThemeBuilderFieldConnections._insertMenuItemToken( field, shortcode );
			}
		},

		/**
		 * Fix dependency settings.
		 *
		 * @since TBD
		 * @access private
		 * @method _fixDependency
		 * @param {Object} settings
		 */
		_fixDependency: function( settings ) {
			if ( 'file' == settings.type ) {
				settings.display = settings.file_display;
			}

			return settings;
		},

		/**
		 * Called when the cancel button of a settings
		 * form is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _cancelSettingsFormClicked
		 * @param {Object} e
		 */
		_cancelSettingsFormClicked: function( e )
		{
			var field 	   = $( '.fl-field-connection-editing' ),
				connection = field.find( '.fl-field-connection' ),
				val   	   = field.find( '.fl-field-connection-value' ).val();

			field.removeClass( 'fl-field-connection-editing' );

			if ( connection.hasClass( 'fl-field-connection-clear-on-cancel' ) ) {
				field.find( '.fl-field-connection-remove' ).trigger( 'click' );
			}
			else if ( '' != val ) {
				FLThemeBuilderFieldConnections._triggerPreview( { target: field } );
			}
		},

		/**
		 * Callback for when the remove button for a
		 * connection is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _removeConnectionClicked
		 * @param {Object} e The event object.
		 */
		_removeConnectionClicked: function( e )
		{
			var target     = $( e.target ),
				field      = target.closest( '.fl-field' ),
				connection = target.closest( '.fl-field-connection' ),
				value      = connection.siblings( '.fl-field-connection-value' ),
				global     = field.closest( '.fl-builder-settings' ).hasClass( 'fl-builder-global-styles' );

			if ( field.find( '.fl-field-connection-compound' ).length ) {
				FLThemeBuilderCompoundConnections._removeConnection( connection );
			} else {
				field.find( '.fl-ignore-validation' ).removeClass( 'fl-ignore-validation' );
				connection.removeAttr( 'data-form' );
				connection.fadeOut( 200 ).removeClass( 'fl-field-connection-visible' );
				value.val( '' ).trigger( 'change' );

				if ( global ) {
					FLBuilder.triggerHook( 'fieldConnectionUpdated', field );
				}
			}

			e.stopPropagation();

			FLThemeBuilderFieldConnections._triggerPreview( e );
		},

		/**
		 * Saves a connection settings form when the
		 * edit icon is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _editConnectionClicked
		 * @param {Object} e
		 */
		_editConnectionClicked: function( e )
		{
			var field = $( this ).closest( '.fl-field' );

			FLThemeBuilderFieldConnections._showSettingsForm( field );
		},

		/**
		 * Callback for when field connection overlays are clicked.
		 *
		 * @since 2.8
		 */
		_fieldConnectionClicked: function()
		{
			var connection = $( this );
			var field = $( this ).closest( '.fl-field' );
			var isColor = 'color' === field.data( 'type' );
			var isCompoundColor = connection.closest( '.fl-color-picker' ).length;

			// Show the color picker for color connections.
			if ( isColor || isCompoundColor ) {
				var picker = isColor ? field.find( '.fl-color-picker' ) : connection.closest( '.fl-color-picker' );
				var button = picker.find( '.fl-color-picker-color' );
				var input = picker.find( '.fl-color-picker-value' );
				var value = input.val();
				var remove = connection.find( '.fl-field-connection-remove' );
				var swatch = connection.find( '.swatch' );
				var pickerInput = $( '.fl-color-picker-input' );

				input.off( 'change.fl-field-connection' );
				pickerInput.val( swatch.css( 'background-color' ) ).trigger( 'keyup' );

				setTimeout( function() {
					button.trigger( 'click' );
					input.on( 'change.fl-field-connection', function() {
						if ( value !== $( this ).val() ) {
							input.off( 'change.fl-field-connection' );
							remove.trigger( 'click' );
						}
					} );
				}, 100 )
			}
		},

		/**
		 * Triggers the preview for a field when a field is
		 * connected or disconnected.
		 *
		 * TODO: Add live preview instead of refresh.
		 *
		 * @since 1.0
		 * @access private
		 * @method _triggerPreview
		 * @param {Object} e The event object.
		 */
		_triggerPreview: function( e )
		{
			if ( $( '.fl-form-field-settings:visible' ).length ) {
				return;
			}
			if( FLBuilder.preview ) {
				FLBuilder.preview.delayPreview( e );
			}
		}
	};

	/**
	 * Handles logic for compound field connections.
	 *
	 * @class FLThemeBuilderCompoundConnections
	 * @since 2.8
	 */
	FLThemeBuilderCompoundConnections = {

		/**
		 * Initializes compound field connections.
		 *
		 * @since 2.8
		 */
		_init: function()
		{
			this._bind();
		},

		/**
		 * Binds compound field connection events.
		 *
		 * @since 2.8
		 */
		_bind: function()
		{
			FLBuilder.addHook( 'settings-form-init', this._initSettingsForms );
		},

		/**
		 * Renders the connection overlay for inputs within compound
		 * fields when settings forms are initialized.
		 *
		 * @since 2.8
		 */
		_initSettingsForms: function()
		{
			var connections = $( '.fl-field-connection-compound', window.parent.document );

			connections.each( function() {
				var connection = $( this );
				var field = connection.closest( '.fl-field' );
				var values = connection.find( '.fl-field-connection-value' );

				field.addClass( 'fl-field-connection-visible' );

				values.each( function() {
					var value = $( this ).val();
					var basename = $( this ).attr( 'name' ).replace( /connections|\[|\]/g, '' );

					if ( value ) {
						value = JSON.parse( value );

						var data = FLThemeBuilderCompoundConnections._getInputData( basename, value );

						for ( var name in data ) {
							var input = $( '[name="' + name + '"]' );
							var label = FLBuilderConfig.globalColorLabels[ data[ name ].property ];

							FLThemeBuilderCompoundConnections._renderConnectionOverlay( input, label );
						}
					}
				} );
			} );
		},

		/**
		 * Returns the input names and associated data for each setting in
		 * a compound field connection.
		 *
		 * @since 2.8
		 */
		_getInputData: function( basename, value )
		{
			var result = {};

			for ( var key in value ) {
				var name = isNaN( +key ) ? basename + '[][' + key + ']' : basename + '[' + key + ']';

				if ( value[ key ].property ) {
					result[ name ] = value[ key ];
				} else {
					$.extend( result, this._getInputData( name, value[ key ] ) );
				}
			}

			return result;
		},

		/**
		 * Sets the value for a single input in a compound connection.
		 *
		 * @since 2.8
		 */
		_setConnectionValue: function( input, newValue )
		{
			var field = input.closest( '.fl-field' );
			var name = input.attr( 'name' );
			var basename = name.replace( /\[(.*)\]/, '' );
			var valueInput = field.find( '.fl-field-connection-value[name="connections[][' + basename + ']"]' );
			var value = valueInput.val() ? JSON.parse( valueInput.val() ) : {};

			// Build the value object for this connection.
			var matches = [];
			var match = null;
			var regex = /\[\]|\[([^\]]+)\]/g;
			var setting = value;

			while ( ( match = regex.exec( name ) ) !== null ) {
				if ( match[1] ) {
					matches.push( match[1] );
				}
			}

			for ( var i = 0; i < matches.length; i++ ) {
				if ( matches.length - 1 === i ) {
					if ( ! newValue ) {
						delete setting[ matches[ i ] ];
					} else {
						setting[ matches[ i ] ] = newValue;
					}
				} else if ( 'undefined' === typeof setting[ matches[ i ] ] ) {
					setting[ matches[ i ] ] = {};
				}

				setting = setting[ matches[ i ] ];
			}

			valueInput.val( JSON.stringify( value ) ).trigger( 'change' );
		},

		/**
		 * Connects a single input in a compound field.
		 *
		 * @since 2.8
		 */
		_connectField: function( input, label, config )
		{
			FLThemeBuilderCompoundConnections._setConnectionValue( input, config );
			FLThemeBuilderCompoundConnections._renderConnectionOverlay( input, label );

			input.addClass( 'fl-ignore-validation' );
		},

		/**
		 * Renders the connection overlay for a single input in a compound field.
		 *
		 * @since 2.8
		 */
		_renderConnectionOverlay: function( input, label )
		{
			var template = wp.template( 'fl-field-connection' );
			var rendered = template( { label: label } );

			if ( input.closest( '.fl-color-picker' ).length ) {
				input.closest( '.fl-color-picker' ).append( rendered );
			} else {
				// TODO: Support other input types in compound fields.
			}
		},

		/**
		 * Callback for when the remove button for a
		 * connection is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _removeConnectionClicked
		 * @param {Object} e The event object.
		 */
		_removeConnection: function( connection )
		{
			var input = connection.parent().find( 'input' );

			input.removeClass( 'fl-ignore-validation' );
			connection.remove();

			FLThemeBuilderCompoundConnections._setConnectionValue( input, null );
		},
	};

	// Initialize
	$( function() {
		FLThemeBuilderCompoundConnections._init(); // Must come first.
		FLThemeBuilderFieldConnections._init();
	} );

} )( jQuery );
