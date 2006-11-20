function StoreAccountAddress(id)
{
	this.provstate = document.getElementById('provstate');
	this.provstate_other =
		document.getElementById('provstate_other');

	this.id = id;
	this.sensitive = null;

	if (this.provstate && this.provstate_other) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			StoreAccountAddress.provstateChangeHandler, this);

		this.provstate_other_sensitive =
			(this.provstate.value === 's:5:"other";');

		if (!this.provstate_other_sensitive && this.provstate_other) {
			this.provstate_other.disabled = true;
			YAHOO.util.Dom.addClass(this.provstate_other,
				'swat-insensitive');
		}
	}
}

StoreAccountAddress.provstateChangeHandler = function(event, address)
{
	var provstate = YAHOO.util.Event.getTarget(event);
	address.provstate_other_sensitive = (provstate.value === 's:5:"other";');

	if (address.provstate_other_sensitive) {
		address.provstate_other.disabled = false;
		YAHOO.util.Dom.removeClass(address.provstate_other, 'swat-insensitive');
	} else {
		address.provstate_other.disabled = true;
		YAHOO.util.Dom.addClass(address.provstate_other, 'swat-insensitive');
	}
}
