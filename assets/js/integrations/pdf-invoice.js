/* global wp, yith_pos_pdf_invoice_integration_options */
( function () {
	const { addFilter }              = wp.hooks;
	const {
			  electronicInvoiceEnabled,
			  i18n,
			  receiverTypeOptions,
			  receiverTypeDefault
		  }                          = yith_pos_pdf_invoice_integration_options;
	const isElectronicInvoiceEnabled = 'yes' === electronicInvoiceEnabled;
	const getCustomerData            = ( customer, key, defaultValue = '' ) => {
		if ( 'meta_data' in customer ) {
			const item = customer.meta_data.find( _ => _.key === key );
			return item?.value ?? defaultValue;
		}

		return defaultValue;
	}

	addFilter( 'yith_pos_customer_billing_fields', 'yith-pos/pdf-invoice-integration', ( fields, { values } ) => {
		const theFields = {
			vat         : { key: 'pdf_invoice_vat_number', type: 'text', label: i18n.vat },
			ssn         : { key: 'pdf_invoice_vat_ssn', type: 'text', label: i18n.ssn },
			receiverType: { key: 'pdf_invoice_receiver_type', type: 'select', label: i18n.receiverType, options: receiverTypeOptions },
			receiverId  : { key: 'pdf_invoice_receiver_id', type: 'text', label: i18n.receiverId, isRequired: true },
			receiverPec : { key: 'pdf_invoice_receiver_pec', type: 'text', label: i18n.receiverPec, isRequired: true }
		};

		const upperFields = [];
		const downFields  = [];

		const vatIndex     = fields.findIndex( _ => _.key === 'pos_billing_vat' );
		const companyIndex = fields.findIndex( _ => _.key === 'billing_company' );

		if ( isElectronicInvoiceEnabled ) {
			upperFields.push( theFields.receiverType );

			if ( values.pdf_invoice_receiver_type === 'company' ) {
				if ( companyIndex > -1 ) {
					fields[ companyIndex ].isRequired = true;
				}
			}

			if ( 'IT' === values.billing_country ) {
				if ( 'private' === values.pdf_invoice_receiver_type ) {
					upperFields.push( { ...theFields.ssn, isRequired: true } );
				} else {
					downFields.push( { ...theFields.vat, isRequired: true } );
					downFields.push( theFields.receiverId );
					downFields.push( theFields.receiverPec );
				}
			} else {
				downFields.push( theFields.vat );
				downFields.push( theFields.ssn );
			}
		} else {
			downFields.push( theFields.vat );
			downFields.push( theFields.ssn );
		}

		if ( vatIndex > -1 ) {
			fields.splice( vatIndex, 1 );
		}

		if ( companyIndex > -1 ) {
			fields = fields.slice( 0, companyIndex ).concat( upperFields ).concat( fields.slice( companyIndex ) ).concat( downFields );
		} else {
			fields = fields.concat( upperFields ).concat( downFields );
		}

		const newCompanyIndex = fields.findIndex( _ => _.key === 'billing_company' );

		if ( newCompanyIndex > -1 && values.pdf_invoice_receiver_type === 'private' ) {
			fields.splice( newCompanyIndex, 1 );
		}

		return fields;
	} );

	addFilter( 'yith_pos_customer_initial_values', 'yith-pos/pdf-invoice-integration', ( values, { customer } ) => {
		values.pdf_invoice_vat_number = getCustomerData( customer, 'billing_vat_number' );
		values.pdf_invoice_vat_ssn    = getCustomerData( customer, 'billing_vat_ssn' );

		if ( isElectronicInvoiceEnabled ) {
			values.pdf_invoice_receiver_type = getCustomerData( customer, 'billing_receiver_type', receiverTypeDefault );
			values.pdf_invoice_receiver_id   = getCustomerData( customer, 'billing_receiver_id' );
			values.pdf_invoice_receiver_pec  = getCustomerData( customer, 'billing_receiver_pec' );
		}

		return values;
	} );

	addFilter( 'yith_pos_customer_data_from_values', 'yith-pos/pdf-invoice-integration', ( data, { values } ) => {
		data.meta_data = data?.meta_data ?? [];
		data.meta_data.push(
			{ key: 'billing_vat_number', value: values.pdf_invoice_vat_number },
			{ key: 'billing_vat_ssn', value: values.pdf_invoice_vat_ssn }
		);

		if ( isElectronicInvoiceEnabled ) {
			data.meta_data.push(
				{ key: 'billing_receiver_type', value: values?.pdf_invoice_receiver_type ?? '' },
				{ key: 'billing_receiver_id', value: values?.pdf_invoice_receiver_id ?? '' },
				{ key: 'billing_receiver_pec', value: values?.pdf_invoice_receiver_pec ?? '' }
			);
		}

		return data;
	} );

	addFilter( 'yith_pos_cart_generated_order', 'yith-pos/pdf-invoice-integration', ( order, params, customer ) => {
		const posVatIndex    = order.meta_data.findIndex( _ => _.key === '_billing_vat' );
		const additionalMeta = [
			{ key: '_billing_vat_number', value: getCustomerData( customer, 'billing_vat_number' ) },
			{ key: '_billing_vat_ssn', value: getCustomerData( customer, 'billing_vat_ssn' ) },
			{ key: '_billing_receiver_type', value: getCustomerData( customer, 'billing_receiver_type' ) },
			{ key: '_billing_receiver_id', value: getCustomerData( customer, 'billing_receiver_id' ) },
			{ key: '_billing_receiver_pec', value: getCustomerData( customer, 'billing_receiver_pec' ) }
		];

		if ( posVatIndex > -1 ) {
			order.meta_data.splice( posVatIndex, 1 );
		}

		order.meta_data = order.meta_data.concat( additionalMeta.filter( _ => !!_.value ) );

		return order;
	} );

} )();