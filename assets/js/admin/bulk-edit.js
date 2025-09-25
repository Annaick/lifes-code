/* global yith_pos_admin */
( function ( $ ) {
	var visibility = $( '#woocommerce-fields-bulk select.visibility' );

	if ( !visibility.find( 'option[value=yith_pos]' ).length ) {
		visibility.append(
			$(
				'<option value="yith_pos">' +
				yith_pos_admin.i18n.pos_results_only +
				'</option>'
			)
		);
	}
} )( jQuery );
