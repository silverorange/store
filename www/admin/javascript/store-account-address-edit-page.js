function StoreAccountAddressEditPage(id, provstate_other_index)
{
	this.id = id;

	this.provstate = document.getElementById('provstate');
	this.provstate_other = document.getElementById('provstate_other');
	this.provstate_other_index = provstate_other_index;

	if (this.provstate && this.provstate_other) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			this.provstateChangeHandler, this, true);

		this.updateFields();
	}
}

StoreAccountAddressEditPage.prototype.updateFields = function()
{
	this.provstate_other_sensitive =
		(this.provstate.selectedIndex == this.provstate_other_index);

	if (this.provstate_other_sensitive) {
		this.provstate_other.disabled = false;
		YAHOO.util.Dom.removeClass(this.provstate_other, 'swat-insensitive');
	} else {
		this.provstate_other.disabled = true;
		YAHOO.util.Dom.addClass(this.provstate_other, 'swat-insensitive');
	}
}

StoreAccountAddressEditPage.prototype.provstateChangeHandler = function(event,
	address)
{
	this.updateFields();
}
