function StoreCheckoutBillingAddress(id)
{
	this.id = id;
	this.sensitive = null;
	this.container = document.getElementById('billing_address_container');
	this.list = document.getElementsByName('billing_address_list');
	this.list_new = document.getElementById('billing_address_list_new');

	this.provstate = document.getElementById('billing_address_provstate');

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutBillingAddress.clickHandler, this);

	if (this.provstate) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			StoreCheckoutBillingAddress.provstateChangeHandler, this);

		this.provstate_other_sensitive =
			(this.provstate.value === 's:5:"other";');

		if (!this.provstate_other_sensitive)
			StoreCheckoutPage_desensitizeFields([
				'billing_address_provstate_other']);
	}

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

StoreCheckoutBillingAddress.provstateChangeHandler = function(event, address)
{
	var provstate = YAHOO.util.Event.getTarget(event);
	address.provstate_other_sensitive = (provstate.value === 's:5:"other";');

	if (address.provstate_other_sensitive)
		StoreCheckoutPage_sensitizeFields([
			'billing_address_provstate_other']);
	else
		StoreCheckoutPage_desensitizeFields([
			'billing_address_provstate_other']);
}

StoreCheckoutBillingAddress.prototype.sensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.removeClass(this.container, 'swat-insensitive');

	var fields = [
		'billing_address_fullname',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate',
		'billing_address_postalcode',
		'billing_address_country'];

	if (this.provstate_other_sensitive)
		fields.push('billing_address_provstate_other');

	StoreCheckoutPage_sensitizeFields(fields);

	this.sensitive = true;
}

StoreCheckoutBillingAddress.prototype.desensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.addClass(this.container, 'swat-insensitive');

	var fields = [
		'billing_address_fullname',
		'billing_address_line1',
		'billing_address_line2',
		'billing_address_city',
		'billing_address_provstate',
		'billing_address_provstate_other',
		'billing_address_postalcode',
		'billing_address_country'];

	StoreCheckoutPage_desensitizeFields(fields);

	this.sensitive = false;
}
