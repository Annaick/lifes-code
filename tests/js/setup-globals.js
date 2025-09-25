/** @format */

global.yithPosSettings = {
    wc      : {
        adminUrl                 : 'https://vagrant.local/wp/wp-admin/',
        locale                   : 'en-US',
        currency                 : { code: 'USD', precision: 2, symbol: '$' },
        date                     : {
            dow: 0
        },
        orderStatuses            : {
            pending   : 'Pending payment',
            processing: 'Processing',
            'on-hold' : 'On hold',
            completed : 'Completed',
            cancelled : 'Cancelled',
            refunded  : 'Refunded',
            failed    : 'Failed'
        },
        l10n                     : {
            userLocale   : 'en_US',
            weekdaysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        },
        wcAdminSettings          : {
            woocommerce_actionable_order_statuses     : [],
            woocommerce_excluded_report_order_statuses: []
        },
        calcDiscountsSequentially: true
    },
    tax     : {
        enabled              : true,
        priceIncludesTax     : true,
        showPriceIncludingTax: false,
        classesAndRates      : {
            ''            : [{ rate: 20, label: 'VAT', shipping: 'yes', compound: 'no' }],
            'reduced-rate': [{ rate: 10, label: 'VAT', shipping: 'yes', compound: 'no' }],
            'zero-rate'   : []
        },
        classes              : ['reduced-rate', 'zero-rate', ''],
        classesLabels        : ['Reduced Rate', 'Zero Rate', '']
    },
    register: {
        id: 1
    },
    user: {
        id: 1
    }
};