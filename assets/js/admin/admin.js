/* global yith_pos, yith_pos_admin, ajaxurl */
( function ( $ ) {

	var disableOpenRegister = function () {
		var rows = $( document ).find( '.wp-list-table tr.type-yith-pos-register' );

		if ( rows.length > 0 ) {
			$.each( rows, function () {
				var t            = $( this ),
					enabled      = t.find( '.yith-pos-register-toggle-enabled input' ).val(),
					openRegister = t.find( '.yith-plugin-fw__action-button--open-register-action' );

				if ( 'yes' !== enabled ) {
					openRegister.hide();
				} else {
					openRegister.show();
				}
			} );
		}
	}

	disableOpenRegister();

	/**
	 * Compact List
	 */
	$( document ).on( 'click', '.yith-pos-compact-list__show-more, .yith-pos-compact-list__hide-more', function ( e ) {
		e.stopPropagation();
		var _list        = $( this ).closest( '.yith-pos-compact-list' ),
			_hiddenItems = _list.find( '.yith-pos-compact-list__hidden-items' );
		_list.toggleClass( 'yith-pos-compact-list--open' );
		if ( _hiddenItems.length ) {
			if ( _list.is( '.yith-pos-compact-list--open' ) ) {
				_hiddenItems.slideDown( 300 );
			} else {
				_hiddenItems.slideUp( 300 );
			}
		}
	} );

	/**
	 * Store toggle enabled
	 */
	$( document ).on( 'change', '.yith-pos-store-toggle-enabled input', function () {
		var enabled   = $( this ).val() === 'yes' ? 'yes' : 'no',
			container = $( this ).closest( '.yith-pos-store-toggle-enabled' ),
			storeID   = container.data( 'store-id' ),
			security  = container.data( 'security' );

		container.block( yith_pos.blockParams );

		$.ajax( {
					type    : 'POST',
					data    : {
						action  : 'yith_pos_store_toggle_enabled',
						id      : storeID,
						enabled : enabled,
						security: security
					},
					url     : ajaxurl,
					success : function ( response ) {
						if ( typeof response.error !== 'undefined' ) {
							alert( response.error );
						}
					},
					complete: function () {
						container.unblock();
					}
				} );
	} );

	/**
	 * Register toggle enabled
	 */
	$( document ).on( 'change', '.yith-pos-register-toggle-enabled input', function () {
		var enabled    = $( this ).val() === 'yes' ? 'yes' : 'no',
			container  = $( this ).closest( '.yith-pos-register-toggle-enabled' ),
			registerID = container.data( 'register-id' ),
			security   = container.data( 'security' );

		container.block( yith_pos.blockParams );

		$.ajax( {
					type    : 'POST',
					data    : {
						action  : 'yith_pos_register_toggle_enabled',
						id      : registerID,
						enabled : enabled,
						security: security
					},
					url     : ajaxurl,
					success : function ( response ) {
						if ( typeof response.error !== 'undefined' ) {
							alert( response.error );
						}
					},
					complete: function () {
						container.unblock();
						disableOpenRegister();
					}
				} );
	} );


	/**
	 * Create user form
	 */
	$( '.yith-pos-create-user-form__container' ).each( function () {
		var _container     = $( this ),
			_form          = _container.find( '.yith-pos-create-user-form' ),
			_button        = _container.find( '.yith-pos-create-user-form__add' ),
			_select2_id    = _container.data( 'select2-to-populate' ),
			_select2       = !!_select2_id ? $( _select2_id ) : {},
			_fields        = _form.find( '.yith-pos-create-user-form__field' ),
			_message       = _form.find( '.yith-pos-create-user-form__message' ),
			_open          = function () {
				_form.slideDown( 300 );
				_container.addClass( 'yith-pos-create-user-form--open' );
			},
			_close         = function () {
				_form.slideUp( 300 );
				_container.removeClass( 'yith-pos-create-user-form--open' );
			},
			_resetFields   = function () {
				_form.find( '.yith-pos-create-user-form__field--to-reset' ).val( '' );
			},
			_toggleHandler = function () {
				if ( _container.is( '.yith-pos-create-user-form--open' ) ) {
					_close();
					_button.html( _button.data( 'text' ) ).removeClass( 'closed' );
				} else {
					_open();
					yith_pos.scrollTo( _button );
					_button.html( _button.data( 'close-text' ) ).addClass( 'closed' );
				}
			},
			_saveHandler   = function () {
				var _data = {}, _name, _value;

				_message.html( '' );

				_fields.each( function () {
					$( this ).trigger( 'validate_field' );
					_name  = $( this ).data( 'name' );
					_value = $( this ).val();

					_data[ _name ] = _value;
				} );

				if ( _container.find( '.validate-error' ).length === 0 ) {
					_container.block( yith_pos.blockParams );

					$.ajax( {
								type    : 'POST',
								data    : _data,
								url     : ajaxurl,
								success : function ( response ) {
									if ( typeof response.success !== 'undefined' && response.success ) {
										if ( _select2 && _select2.length ) {
											var _option = new Option( response.user_name_html, response.user_id, true, true );
											_select2.append( _option ).trigger( 'change' );
											yith_pos.scrollTo( _select2 );
											_resetFields();
											_close();
										}
									} else {
										_message.html( '<p class="validate-error">' + response.error + '</p>' );
									}
								},
								complete: function () {
									_container.unblock();
								}
							} );
				}
			};

		_button.on( 'click', _toggleHandler );
		_container.on( 'click', '.yith-pos-create-user-form__save', _saveHandler );
	} );

	/**
	 * Settings Box - Toggle
	 */
	$( document ).on( 'click', '.yith-pos-settings-box__toggle', function ( event ) {
		var _toggle  = $( event.target ),
			_box     = _toggle.closest( '.yith-pos-settings-box' ),
			_content = _box.find( '.yith-pos-settings-box__content' );

		if ( _box.is( '.yith-pos-settings-box--closed' ) ) {
			_content.slideDown( 400 );
		} else {
			_content.slideUp( 400 );
		}

		_box.toggleClass( 'yith-pos-settings-box--closed' );
	} );


	/**
	 * Edit POS CPT
	 */
	var isPosPostType = function ( post_type ) {
		return ['yith-pos-store', 'yith-pos-register', 'yith-pos-receipt'].includes( post_type );
	};

} )( jQuery );