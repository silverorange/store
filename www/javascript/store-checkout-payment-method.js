function StoreCheckoutPaymentMethodPage(id)
{
	this.id = id;
	this.container = document.getElementById('payment_method_container');
	this.list = document.getElementsByName('payment_method_list');
	this.list_new = document.getElementById('payment_method_list_new');

	// set up event handlers
	for (var i = 0; i < this.list.length; i++) {
		YAHOO.util.Event.addListener(this.list[i], 'click',
			SwatCheckoutPaymentMethodPage.handlePaymentMethodClick, this);
	}

	this.fields = [
		'payment_type',
		'credit_card_number',
		'card_verification_value',
		'card_issue_number',
		'credit_card_expiry_month',
		'credit_card_expiry_year',
		'card_inception_month',
		'card_inception_year',
		'credit_card_fullname'
	];

	var payment_type_options =
		document.getElementsByName('payment_type');

	for (var i = 0; i < payment_type_options.length; i++)
		this.fields.push(payment_type_options[i].id);

	this.updateFields();
}

StoreCheckoutPaymentMethodPage.handlePaymentMethodClick = function(event, page)
{
	page.updateFields();
}

StoreCheckoutPaymentMethodPage.prototype.updateFields = function()
{
	if (this.isSensitive()) 
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutPaymentMethodPage.prototype.isSensitive = function()
{
	return this.list_new.checked;
}

StoreCheckoutPaymentMethodPage.prototype.sensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.removeClass(this.container, 'swat-insensitive');

	StoreCheckoutPage_sensitizeFields(this.fields);
}

StoreCheckoutPaymentMethodPage.prototype.desensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.addClass(this.container, 'swat-insensitive');

	StoreCheckoutPage_desensitizeFields(this.fields);
}
