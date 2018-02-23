YAHOO.util.Event.onDOMReady(function() {
	new StoreGoogleAddressAutoComplete(
		'address_line1',
		{
			line1: 'address_line1',
			line2: 'address_line2',
			city: 'city',
			postal_code: 'postal_code',
			provstate_entry: 'provstate_entry',
			country: 'country',
			provstate: 'provstate_flydown'
		}
	);
});
