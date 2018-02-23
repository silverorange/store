YAHOO.util.Event.onDOMReady(function() {
	new StoreGoogleAddressAutoComplete(
		'line1',
		{
			line1: 'line1',
			line2: 'line2',
			city: 'city',
			postal_code: 'postal_code',
			provstate_entry: 'provstate_entry',
			country: 'country',
			provstate: 'provstate_flydown'
		}
	);
});
