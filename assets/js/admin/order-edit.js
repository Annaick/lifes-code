/* global yithPosDiscountCouponReasons */
( function ( $ ) {
	if ( yithPosDiscountCouponReasons ) {
		var renamePosDiscountCoupons = function () {
			var coupons = $( '.wc_coupon_list > li.code > span > span' );

			coupons.each( function () {
				var code = $( this ).html();
				if ( code in yithPosDiscountCouponReasons ) {
					$( this ).html( yithPosDiscountCouponReasons[ code ] );
				}
			} );
		};

		if ( typeof MutationObserver !== 'undefined' ) {
			var observer_config = { childList: true, subtree: true },
				observer        = new MutationObserver( renamePosDiscountCoupons );

			observer.observe( $( '#woocommerce-order-items' ).get( 0 ), observer_config );
		}

		renamePosDiscountCoupons();

	}
} )( jQuery );