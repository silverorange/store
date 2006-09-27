function StoreCheckoutBillingAddress(id)
{
	this.id = id;
	this.sensitive = null;
	this.container = document.getElementById('billing_address_container');
	this.list = document.getElementsByName('billing_address_list');
	this.list_new = document.getElementById('billing_address_list_new');

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

StoreCheckoutBillingAddress.prototype.sensitize = function()
{
	if (this.container)
		this.container.className = this.container.className.replace(
			/ *swat-insensitive/, '');
	
	StoreCheckoutPage_sensitizeFields([
		'billing_address_fullname',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate',
		'billing_address_postalcode'
	]);

	this.sensitive = true;
}

StoreCheckoutBillingAddress.prototype.desensitize = function()
{
	if (this.container)
		this.container.className += ' swat-insensitive';

	StoreCheckoutPage_desensitizeFields([
		'billing_address_fullname',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate',
		'billing_address_postalcode'
	]);

	this.sensitive = false;
}
