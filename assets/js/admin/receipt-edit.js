( function ( $ ) {

	var form             = $( '#post' ),
		logo             = $( '#_logo' ),
		container        = $( '.receipt-container' ),
		previewLogoImage = container.find( '#logo img' );

	form.prepend( $( '<input name="post_status" value="publish" type="hidden" />' ) );

	$( document ).on( 'click', '.yith-open-toggle', function () {
		var $this    = $( this ),
			_section = $this.data( 'target' );

		if ( $this.hasClass( 'yith-open-toggle-opened' ) ) {
			$this.removeClass( 'yith-open-toggle-opened' );
			$( '.' + _section ).removeClass( 'yith-pos-hidden' );
		} else {
			$this.addClass( 'yith-open-toggle-opened' );
			$( '.' + _section ).addClass( 'yith-pos-hidden' );
		}
	} );

	$( document ).on( 'change', '.on_off, [type=checkbox]', function () {
		var $t     = $( this ),
			id     = $t.attr( 'id' ),
			target = $( document ).find( '[data-dep=' + id + ']' );

		if ( typeof target !== 'undefined' && $t.is( ':checked' ) ) {
			$( target ).show();
		} else {
			$( target ).hide();
		}
	} );

	$( document ).on( 'input', '[type=text], textarea', function () {
		var $t     = $( this ),
			id     = $( this ).attr( 'id' ),
			target = $( document ).find( '[data-dep_label=' + id + ']' );

		target.text( $t.val() );
	} );

	logo.on( 'change', function () {
		var imageSrc = $( this ).val();
		if ( imageSrc ) {
			previewLogoImage.attr( 'src', imageSrc );
			previewLogoImage.show();
		} else {
			previewLogoImage.hide();
		}
	} );

	$( document ).find( '.on_off, [type=checkbox], [id="_logo"]' ).change();
	$( document ).find( '[type=text], textarea' ).trigger( 'input' );

	$( document ).on( 'click', '#print_receipt', function () {
		window.print();
	} );

	// Handle multiple deps.
	var multipleDepsFields = $( '[data-yith-pos-multiple-deps]' );
	multipleDepsFields.each( function () {
		var currentField = $( this ),
			currentRow   = currentField.closest( '.the-metabox' ),
			deps         = currentField.data( 'yith-pos-multiple-deps' ),
			depsFieldIds = Object.keys( deps.reduce( function ( acc, item ) {
				return Object.assign( acc, item );
			}, {} ) ),
			depsFields   = $( '#' + depsFieldIds.join( ', #' ) );

		deps = deps.map( function ( conditions ) {
			return Object.keys( conditions ).map( function ( fieldId ) {
				return {
					id   : fieldId,
					value: conditions[ fieldId ]
				}
			} )
		} );

		var checkConditions = function () {
			for ( var i in deps ) {
				var conditions = deps[ i ],
					check      = true;
				for ( var j in conditions ) {
					var condition = conditions[ j ],
						field     = $( '#' + condition.id ),
						value     = condition.value;

					check = field.val() === value;
					if ( field.is( '[type=checkbox]' ) ) {
						check = ( field.is( ':checked' ) ? 'yes' : 'no' ) === value;
					}

					if ( !check ) {
						break;
					}
				}

				if ( check ) {
					return true;
				}
			}

			return false;
		}

		var checkVisibility = function () {
			if ( checkConditions() ) {
				currentRow.show();
			} else {
				currentRow.hide();
			}
		}

		depsFields.on( 'change', checkVisibility );
		checkVisibility();
	} );

	// Update container data.
	$( '[data-update-container-data]' ).each( function () {
		var fieldContainer = $( this ),
			data           = fieldContainer.data( 'update-container-data' ),
			field          = fieldContainer.is( '.yith-plugin-fw-onoff-container' ) ? fieldContainer.find( 'input' ).first() : fieldContainer,
			update         = function () {
				var value = field.val();

				if ( field.is( '[type=checkbox]' ) ) {
					value = field.is( ':checked' ) ? 'yes' : 'no';
				}

				container.attr( 'data-' + data, value );
			}

		field.on( 'change', update );
		update();
	} );

	/**
	 * Open the correct tab if a required field is in an hidden tab panel.
	 */
	$( document ).on( 'click', '.yith-pos-save-receipt', function ( e ) {
		var requiredEmptyFields     = $( '.yith-required-field:empty' ),
			firstRequiredEmptyField = requiredEmptyFields.first();

		if ( firstRequiredEmptyField ) {
			var panelId    = firstRequiredEmptyField.closest( '.yith-plugin-fw__tab-panel' ).attr( 'id' ),
				tabHandler = $( '.yith-plugin-fw__tab__handler[href="#' + panelId + '"]' );

			tabHandler.trigger( 'click' );
		}
	} );


} )( jQuery );