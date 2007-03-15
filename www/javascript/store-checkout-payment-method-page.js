function StoreCheckoutPaymentMethodPage(id, inception_date_ids,
	issue_number_ids)
{
	this.id = id;
	this.container = document.getElementById('payment_method_form');
	this.list_new = document.getElementById('payment_method_list_new');
	this.inception_date_types = [];
	this.issue_number_types = [];

	// set up event handlers for payment methods
	var payment_methods = document.getElementsByName('payment_method_list');
	for (var i = 0; i < payment_methods.length; i++) {
		YAHOO.util.Event.addListener(payment_methods[i], 'click',
			StoreCheckoutPaymentMethodPage.handlePaymentMethodClick, this);
	}

	// set up event handlers for payment types 
	var payment_types = document.getElementsByName('payment_type');
	for (var i = 0; i < payment_types.length; i++) {
		YAHOO.util.Event.addListener(payment_types[i], 'click',
			StoreCheckoutPaymentMethodPage.handlePaymentTypeClick, this);
	}

	for (var i = 0; i < inception_date_ids.length; i++) {
		var type = document.getElementById(
			'payment_type_' + inception_date_ids[i]);

		if (type)
			this.inception_date_types.push(type);
	}

	for (var i = 0; i < issue_number_ids.length; i++) {
		var type = document.getElementById(
			'payment_type_' + issue_number_ids[i]);

		if (type)
			this.issue_number_types.push(type);
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
		'credit_card_fullname',
		'save_account_payment_method'
	];

	this.inception_date_fields = [
		'card_inception_month',
		'card_inception_year'
	];

	this.issue_number_fields = [
		'card_issue_number'
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

StoreCheckoutPaymentMethodPage.handlePaymentTypeClick = function(event, page)
{
	page.updateFields();
}

StoreCheckoutPaymentMethodPage.prototype.updateFields = function()
{
	if (this.isSensitive()) 
		this.sensitize();
	else
		this.desensitize();

	if (this.isInceptionDateSensitive())
		this.sensitizeInceptionDate();
	else
		this.desensitizeInceptionDate();

	if (this.isIssueNumberSensitive())
		this.sensitizeIssueNumber();
	else
		this.desensitizeIssueNumber();
}

// payment method fields 

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

// inception date fields

StoreCheckoutPaymentMethodPage.prototype.isInceptionDateSensitive = function()
{
	var sensitive = false;

	for (var i = 0; i < this.inception_date_types.length; i++) {
		if (this.inception_date_types[i].checked) {
			sensitive = true;
			break;
		}
	}

	return sensitive;
}

StoreCheckoutPaymentMethodPage.prototype.sensitizeInceptionDate = function()
{
	YAHOO.util.Dom.addClass('card_inception_field', 'swat-insensitive');
	StoreCheckoutPage_sensitizeFields(this.inception_date_fields);
}

StoreCheckoutPaymentMethodPage.prototype.desensitizeInceptionDate = function()
{
	YAHOO.util.Dom.removeClass('card_inception_field', 'swat-insensitive');
	StoreCheckoutPage_desensitizeFields(this.inception_date_fields);
}

// issue number fields

StoreCheckoutPaymentMethodPage.prototype.isIssueNumberSensitive = function()
{
	var sensitive = false;

	for (var i = 0; i < this.issue_number_types.length; i++) {
		if (this.issue_number_types[i].checked) {
			sensitive = true;
			break;
		}
	}

	return sensitive;
}

StoreCheckoutPaymentMethodPage.prototype.sensitizeIssueNumber = function()
{
	YAHOO.util.Dom.addClass('card_issue_number_field', 'swat-insensitive');
	StoreCheckoutPage_sensitizeFields(this.issue_number_fields);
}

StoreCheckoutPaymentMethodPage.prototype.desensitizeIssueNumber = function()
{
	YAHOO.util.Dom.removeClass('card_issue_number_field', 'swat-insensitive');
	StoreCheckoutPage_desensitizeFields(this.issue_number_fields);
}
