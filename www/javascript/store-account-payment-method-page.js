function StoreAccountPaymentMethodPage(id, inception_date_ids,
	issue_number_ids)
{
	this.id = id;
	this.inception_date_types = [];
	this.issue_number_types = [];

	// set up event handlers for payment types 
	var payment_types = document.getElementsByName('payment_type');
	for (var i = 0; i < payment_types.length; i++) {
		YAHOO.util.Event.addListener(payment_types[i], 'click',
			this.handlePaymentTypeClick, this, true);
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

	this.inception_date_fields = [
		'card_inception_month',
		'card_inception_year'
	];

	this.issue_number_fields = [
		'card_issue_number'
	];

	var payment_type_options =
		document.getElementsByName('payment_type');

	this.updateFields();
}

StoreAccountPaymentMethodPage.prototype.handlePaymentTypeClick = function(
	event, page)
{
	this.updateFields();
}

StoreAccountPaymentMethodPage.prototype.updateFields = function()
{
	if (this.isInceptionDateSensitive())
		this.sensitizeInceptionDate();
	else
		this.desensitizeInceptionDate();

	if (this.isIssueNumberSensitive())
		this.sensitizeIssueNumber();
	else
		this.desensitizeIssueNumber();
}

// inception date fields

StoreAccountPaymentMethodPage.prototype.isInceptionDateSensitive = function()
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

StoreAccountPaymentMethodPage.prototype.sensitizeInceptionDate = function()
{
	YAHOO.util.Dom.removeClass('card_inception_field', 'swat-insensitive');

	var element;
	for (var i = 0; i < this.inception_date_fields.length; i++) {
		element = document.getElementById(this.inception_date_fields[i]);
		element.disabled = false;
		YAHOO.util.Dom.removeClass(element, 'swat-insensitive');
	}
}

StoreAccountPaymentMethodPage.prototype.desensitizeInceptionDate = function()
{
	YAHOO.util.Dom.addClass('card_inception_field', 'swat-insensitive');

	var element;
	for (var i = 0; i < this.inception_date_fields.length; i++) {
		element = document.getElementById(this.inception_date_fields[i]);
		element.disabled = true;
		YAHOO.util.Dom.addClass(element, 'swat-insensitive');
	}
}

// issue number fields

StoreAccountPaymentMethodPage.prototype.isIssueNumberSensitive = function()
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

StoreAccountPaymentMethodPage.prototype.sensitizeIssueNumber = function()
{
	YAHOO.util.Dom.removeClass('card_issue_number_field', 'swat-insensitive');

	var element;
	for (var i = 0; i < this.issue_number_fields.length; i++) {
		element = document.getElementById(this.issue_number_fields[i]);
		element.disabled = false;
		YAHOO.util.Dom.removeClass(element, 'swat-insensitive');
	}
}

StoreAccountPaymentMethodPage.prototype.desensitizeIssueNumber = function()
{
	YAHOO.util.Dom.addClass('card_issue_number_field', 'swat-insensitive');

	var element;
	for (var i = 0; i < this.issue_number_fields.length; i++) {
		element = document.getElementById(this.issue_number_fields[i]);
		element.disabled = true;
		YAHOO.util.Dom.addClass(element, 'swat-insensitive');
	}
}
