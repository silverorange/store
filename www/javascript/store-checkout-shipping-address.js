function StoreCheckoutShippingAddress(id)
{
	this.id = id;
	this.sensitive = null;
	this.container = document.getElementById('shipping_address_container');
	this.list = document.getElementsByName('shipping_address_list');
	this.list_new = document.getElementById('shipping_address_list_new');

	var is_ie = (document.addEventListener) ? false: true;
	var self = this;

	function clickHandler(event)
	{
		if (self.list_new.checked)
			self.sensitize();
		else if (self.sensitive || self.sensitive == null)
			self.desensitize();
	}

	// set up event handlers
	for (var i = 0; i < this.list.length; i++) {
		if (is_ie)
			this.list[i].attachEvent('onclick', clickHandler);
		else
			this.list[i].addEventListener('click', clickHandler, true);
	}

	// initialize state
	if (this.list_new.checked)
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutShippingAddress.prototype.sensitize = function()
{
	if (this.container)
		this.container.className = this.container.className.replace(
			/ *swat-insensitive/, '');
	
	StoreCheckoutPage_sensitizeFields([
		'shipping_address_fullname',
		'shipping_address_line1',
		'shipping_address_line2',
		'shipping_address_city',
		'shipping_address_provstate',
		'shipping_address_postalcode'
	]);

	this.sensitive = true;
}

StoreCheckoutShippingAddress.prototype.desensitize = function()
{
	if (this.container)
		this.container.className += ' swat-insensitive';

	StoreCheckoutPage_desensitizeFields([
		'shipping_address_fullname',
		'shipping_address_line1',
		'shipping_address_line2',
		'shipping_address_city',
		'shipping_address_provstate',
		'shipping_address_postalcode'
	]);

	this.sensitive = false;
}
