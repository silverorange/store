function StoreCheckoutPaymentMethodPage(id, inception_date_ids,
	issue_number_ids, card_ids)
{
	this.id = id;
	this.card_ids = card_ids;
	this.container = document.getElementById('payment_method_form');
	this.card_container = document.getElementById('card_container');
	this.account_fields_container =
		document.getElementById('account_fields_container');

	this.inception_date_types = [];
	this.issue_number_types = [];
	this.card_types = [];

	// set up event handlers for payment options
	var payment_options = document.getElementsByName('payment_option');
	for (var i = 0; i < payment_options.length; i++) {
		// only consider input elements (IE fix)
		if (payment_options[i].nodeName == 'INPUT') {
			YAHOO.util.Event.on(
				payment_options[i],
				'click',
				this.handlePaymentTypeClick,
				this,
				true
			);
		}
	}

	for (var i = 0; i < inception_date_ids.length; i++) {
		var type = document.getElementById(
			'payment_option_type_' + inception_date_ids[i]);

		if (type) {
			this.inception_date_types.push(type);
		}
	}

	for (var i = 0; i < issue_number_ids.length; i++) {
		var type = document.getElementById(
			'payment_option_type_' + issue_number_ids[i]);

		if (type) {
			this.issue_number_types.push(type);
		}
	}

	for (var i = 0; i < card_ids.length; i++) {
		var type = document.getElementById(
			'payment_option_type_' + card_ids[i]);

		if (type) {
			this.card_types.push(type);
		}
	}

	this.fields = [
		'payment_option',
		'card_type',
		'card_number',
		'card_verification_value',
		'card_issue_number',
		'card_expiry_month',
		'card_expiry_year',
		'card_inception_month',
		'card_inception_year',
		'card_fullname',
		'save_account_payment_method'
	];

	this.card_fields = [
		'card_type',
		'card_number',
		'card_verification_value',
		'card_issue_number',
		'card_expiry_month',
		'card_expiry_year',
		'card_inception_month',
		'card_inception_year',
		'card_fullname',
		'save_account_payment_method'
	];

	this.inception_date_fields = [
		'card_inception_month',
		'card_inception_year'
	];

	this.issue_number_fields = [
		'card_issue_number'
	];

	this.account_fields = [
		'account_card_verification_value'
	];

	for (var i = 0; i < payment_options.length; i++) {
		if (payment_options[i].nodeName == 'INPUT') {
			this.fields.push(payment_options[i].id);
		}
	}

	YAHOO.util.Event.onDOMReady(function() {
		this.updateFields();
	}, this, true);
}

StoreCheckoutPaymentMethodPage.prototype.handlePaymentTypeClick = function(e)
{
	this.updateFields();
}

StoreCheckoutPaymentMethodPage.prototype.updateFields = function()
{
	if (this.isInceptionDateSensitive()) {
		this.sensitizeInceptionDate();
	} else {
		this.desensitizeInceptionDate();
	}

	if (this.isIssueNumberSensitive()) {
		this.sensitizeIssueNumber();
	} else {
		this.desensitizeIssueNumber();
	}
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
	YAHOO.util.Dom.removeClass('card_inception_field', 'swat-insensitive');
	StoreCheckoutPage_sensitizeFields(this.inception_date_fields);
}

StoreCheckoutPaymentMethodPage.prototype.desensitizeInceptionDate = function()
{
	YAHOO.util.Dom.addClass('card_inception_field', 'swat-insensitive');
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
	YAHOO.util.Dom.removeClass('card_issue_number_field', 'swat-insensitive');
	StoreCheckoutPage_sensitizeFields(this.issue_number_fields);
}

StoreCheckoutPaymentMethodPage.prototype.desensitizeIssueNumber = function()
{
	YAHOO.util.Dom.addClass('card_issue_number_field', 'swat-insensitive');
	StoreCheckoutPage_desensitizeFields(this.issue_number_fields);
}
