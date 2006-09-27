function StoreCheckoutPaymentMethod(id)
{
	this.id = id;
	this.sensitive = null;
	this.container = document.getElementById('payment_method_container');
	this.list = document.getElementsByName('payment_method_list');
	this.list_new = document.getElementById('payment_method_list_new');

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

	this.fields = [
		'payment_type',
		'credit_card_number',
		'credit_card_expiry_month',
		'credit_card_expiry_year',
		'credit_card_fullname'
	];

	var payment_type_options =
		document.getElementsByName('payment_type');

	for (var i = 0; i < payment_type_options.length; i++)
		this.fields.push(payment_type_options[i].id);

	// initialize state
	if (this.list_new.checked)
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutPaymentMethod.prototype.sensitize = function()
{
	if (this.container)
		this.container.className = this.container.className.replace(
			/ *swat-insensitive/, '');

	StoreCheckoutPage_sensitizeFields(this.fields);
	this.sensitive = true;
}

StoreCheckoutPaymentMethod.prototype.desensitize = function()
{
	if (this.container)
		this.container.className += ' swat-insensitive';

	StoreCheckoutPage_desensitizeFields(this.fields);
	this.sensitive = false;
}
