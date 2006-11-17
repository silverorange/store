function StoreCheckoutBillingAddress(id)
{
	this.id = id;
	this.sensitive = null;
	this.container = document.getElementById('billing_address_container');
	this.list = document.getElementsByName('billing_address_list');
	this.list_new = document.getElementById('billing_address_list_new');

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutBillingAddress.clickHandler, this);

	// initialize state
	if (this.list_new.checked)
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutBillingAddress.clickHandler = function(event, address)
{
	if (address.list_new.checked)
		address.sensitize();
	else if (address.sensitive || address.sensitive == null)
		address.desensitize();
}

StoreCheckoutBillingAddress.prototype.sensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.removeClass(this.container, 'swat-insensitive');
	
	StoreCheckoutPage_sensitizeFields([
		'billing_address_fullname',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate',
		'billing_address_provstate_other',
		'billing_address_postalcode',
		'billing_address_country'
	]);

	this.sensitive = true;
}

StoreCheckoutBillingAddress.prototype.desensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.addClass(this.container, 'swat-insensitive');

	StoreCheckoutPage_desensitizeFields([
		'billing_address_fullname',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate',
		'billing_address_provstate_other',
		'billing_address_postalcode',
		'billing_address_country'
	]);

	this.sensitive = false;
}
