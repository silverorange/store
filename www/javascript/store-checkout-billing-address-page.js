function StoreCheckoutBillingAddressPage(id)
{
	this.container = document.getElementById('billing_address_form');
	this.list = document.getElementsByName('billing_address_list');
	this.list_new = document.getElementById('billing_address_list_new');

	YAHOO.util.Event.onDOMReady(function() {
		new StoreGoogleAddressAutoComplete(
			'billing_address_line1',
			{
				line1: 'billing_address_line1',
				line2: 'billing_address_line2',
				city: 'billing_address_city',
				postal_code: 'billing_address_postalcode',
				provstate_entry:
					'billing_address_address_provstate_entry',
				country: 'billing_address_country',
				provstate: 'billing_address_provstate_flydown',
			}
		);
	});

	StoreCheckoutBillingAddressPage.superclass.constructor.call(this, id);
}

YAHOO.lang.extend(StoreCheckoutBillingAddressPage, StoreCheckoutAddressPage, {

getFieldNames: function()
{
	return [
		'billing_address_fullname',
		'billing_address_phone',
		'billing_address_company',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate_flydown',
		'billing_address_provstate_entry',
		'billing_address_postalcode',
		'billing_address_country'
	];
}

});
