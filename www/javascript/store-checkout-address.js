function StoreCheckoutAddress(id)
{
	this.id = id;
	this.sensitive = null;

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutAddress.clickHandler, this);

	if (this.provstate) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			StoreCheckoutAddress.provstateChangeHandler, this);

		this.provstate_other_sensitive =
			(this.provstate.value === 's:5:"other";');

		if (!this.provstate_other_sensitive)
			StoreCheckoutPage_desensitizeFields([this.provstate_other_id]);
	} else {
		this.provstate_other_sensitive = true;
	}

	// initialize state
	if (this.list_new.checked)
		this.sensitize();
	else
		this.desensitize();
}

StoreCheckoutAddress.prototype.sensitize = function()
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

StoreCheckoutAddress.prototype.desensitize = function()
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

StoreCheckoutAddress.clickHandler = function(event, address)
{
	if (address.list_new.checked)
		address.sensitize();
	else if (address.sensitive || address.sensitive == null)
		address.desensitize();
}

StoreCheckoutAddress.provstateChangeHandler = function(event, address)
{
	var provstate = YAHOO.util.Event.getTarget(event);
	address.provstate_other_sensitive = (provstate.value === 's:5:"other";');

	if (address.provstate_other_sensitive)
		StoreCheckoutPage_sensitizeFields([address.provstate_other_id]);
	else
		StoreCheckoutPage_desensitizeFields([address.provstate_other_id]);
}
