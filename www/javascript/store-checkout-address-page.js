function StoreCheckoutAddressPage(id, provstate_other_index)
{
	this.id = id;
	this.sensitive = null;
	this.fields = this.getFieldNames();
	this.provstate_other_index = provstate_other_index;

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutAddressPage.clickHandler, this);

	if (this.provstate) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			this.provstateChangeHandler, this, true);

		this.provstate_other_sensitive =
			(this.provstate.selectedIndex == this.provstate_other_index);

		if (!this.provstate_other_sensitive)
			StoreCheckoutPage_desensitizeFields([this.provstate_other_id]);
	} else {
		this.provstate_other_sensitive = true;
	}

	// initialize state
	if (!this.list_new || this.list_new.checked)
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutAddressPage.prototype.sensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.removeClass(this.container, 'swat-insensitive');

	var fields = [];
	for (var i = 0; i < this.fields.length; i++)
		fields.push(this.fields[i]);

	if (this.provstate_other_sensitive)
		fields.push(this.provstate_other_id);

	StoreCheckoutPage_sensitizeFields(fields);

	this.sensitive = true;
}

StoreCheckoutAddressPage.prototype.desensitize = function()
{
	if (this.container)
		YAHOO.util.Dom.addClass(this.container, 'swat-insensitive');

	var fields = [];
	for (var i = 0; i < this.fields.length; i++)
		fields.push(this.fields[i]);
	
	fields.push(this.provstate_other_id);

	StoreCheckoutPage_desensitizeFields(fields);

	this.sensitive = false;
}

StoreCheckoutAddressPage.clickHandler = function(event, address)
{
	if (address.list_new.checked)
		address.sensitize();
	else if (address.sensitive || address.sensitive == null)
		address.desensitize();
}

StoreCheckoutAddressPage.prototype.provstateChangeHandler = function(event,
	address)
{
	this.provstate_other_sensitive =
		(this.provstate.selectedIndex == this.provstate_other_index);

	if (this.provstate_other_sensitive)
		StoreCheckoutPage_sensitizeFields([this.provstate_other_id]);
	else
		StoreCheckoutPage_desensitizeFields([this.provstate_other_id]);
}

StoreCheckoutAddressPage.prototype.getFieldNames = function()
{
	return [];
}
