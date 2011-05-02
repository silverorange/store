function StoreAccountAddressPage(id, provstate_other_index)
{
	this.id = id;

	this.provstate = document.getElementById('provstate');
	this.provstate_other = document.getElementById('provstate_other');
	this.provstate_other_index = provstate_other_index;

	if (this.provstate && this.provstate_other) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			this.provstateChangeHandler, this, true);

		this.provstateChangeHandler();
	}
}

StoreAccountAddressPage.prototype.provstateChangeHandler = function(e)
{
	this.provstate_other_enabled =
		(this.provstate.selectedIndex == this.provstate_other_index);

	if (this.provstate_other_enabled) {
		this.provstate_other.style.display = 'block';
	} else {
		this.provstate_other.style.display = 'none';
	}
}
