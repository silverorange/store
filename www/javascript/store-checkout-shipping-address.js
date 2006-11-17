function StoreCheckoutShippingAddress(id)
{
	this.id = id;
	this.sensitive = null;
	this.container = document.getElementById('shipping_address_container');
	this.list = document.getElementsByName('shipping_address_list');
	this.list_new = document.getElementById('shipping_address_list_new');

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutShippingAddress.clickHandler, this);

	// initialize state
	if (this.list_new.checked)
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutShippingAddress.clickHandler = function(event, address)
{
	if (address.list_new.checked)
		address.sensitize();
	else if (address.sensitive || address.sensitive == null)
		address.desensitize();
}

StoreCheckoutShippingAddress.prototype.sensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.removeClass(this.container, 'swat-insensitive');
	
	StoreCheckoutPage_sensitizeFields([
		'shipping_address_fullname',
		'shipping_address_line1',
		'shipping_address_line2',
		'shipping_address_city',
		'shipping_address_provstate',
		'shipping_address_provstate_other',
		'shipping_address_postalcode',
		'shipping_address_country'
	]);

	this.sensitive = true;
}

StoreCheckoutShippingAddress.prototype.desensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.addClass(this.container, 'swat-insensitive');

	StoreCheckoutPage_desensitizeFields([
		'shipping_address_fullname',
		'shipping_address_line1',
		'shipping_address_line2',
		'shipping_address_city',
		'shipping_address_provstate',
		'shipping_address_provstate_other',
		'shipping_address_postalcode',
		'shipping_address_country'
	]);

	this.sensitive = false;
}
