function StoreCheckoutAddressPage(id, provstate_other_index)
{
	this.id = id;
	this.sensitive = null;
	this.status = 'none';
	this.fields = this.getFieldNames();
	this.provstate_other_index = provstate_other_index;
	this.semaphore = false;

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutAddressPage.clickHandler, this);

	if (this.provstate) {
		YAHOO.util.Event.addListener(this.provstate, 'change',
			this.provstateChangeHandler, this, true);
	}

	// initialize state
	YAHOO.util.Event.onDOMReady(function() {
		if (this.container) {
			this.initContainer();
		}

		if (!this.list_new || this.list_new.checked) {
			this.showAddressForm(false);
		} else {
			this.hideAddressForm(false);
		}

		this.provstateChangeHandler();
	}, this, true);
}

StoreCheckoutAddressPage.prototype.initContainer = function()
{
	var div = document.createElement('div');
	div.style.overflow = 'hidden';
	this.container.parentNode.replaceChild(div, this.container);
	div.appendChild(this.container);

	var duration = 0.4;

	var that = this;

	this.show_animation = new YAHOO.util.Anim(
		div, { height: { to: this.container.offsetHeight } }, duration,
		YAHOO.util.Easing.easeOut);

	this.show_animation.onComplete.subscribe(function() {
		div.style.overflow = 'visible';

		var fade_in = new YAHOO.util.Anim(
			that.container, { opacity: { to: 1 } }, duration,
			YAHOO.util.Easing.easeOut);

		fade_in.animate();
	});

	this.hide_animation = new YAHOO.util.Anim(
		this.container, { opacity: { to: 0 } }, duration,
		YAHOO.util.Easing.easeOut);

	this.hide_animation.onComplete.subscribe(function() {
		div.style.overflow = 'hidden';

		var collapse = new YAHOO.util.Anim(
			div, { height: { to: 0 } }, duration,
			YAHOO.util.Easing.easeOut);

		collapse.animate();
	});
}

StoreCheckoutAddressPage.prototype.showAddressForm = function(animate)
{
	if (this.container) {
		if (animate) {
			if (this.status == 'open') {
				return; // don't re-animate if already open
			} else if (this.semaphore) {
				this.hide_animation.stop();
			}

			var div = this.container.parentNode;
			div.style.height = '0';
			this.container.style.display = 'block';
			this.container.style.opacity = 0;

			this.semaphore = true;
			this.show_animation.animate();
		} else {
			this.container.style.display = 'block';
		}

		this.status = 'open';
	} else {
		var fields = [];
		for (var i = 0; i < this.fields.length; i++)
			fields.push(this.fields[i]);

		StoreCheckoutPage_sensitizeFields(fields);

		this.sensitive = true;
	}
}

StoreCheckoutAddressPage.prototype.hideAddressForm = function(animate)
{
	if (this.container) {
		if (animate) {
			if (this.status == 'closed') {
				return; // don't re-animate if already closed
			} else if (this.semaphore) {
				this.show_animation.stop();
			}

			this.hide_animation.animate();
		} else {
			this.container.style.display = 'none';
		}

		this.status = 'closed';
	} else {
		var fields = [];
		for (var i = 0; i < this.fields.length; i++) {
			fields.push(this.fields[i]);
		}

		if (this.provstate_other) {
			fields.push(this.provstate_other_id);
		}

		StoreCheckoutPage_desensitizeFields(fields);

		this.sensitive = false;
	}
}

StoreCheckoutAddressPage.clickHandler = function(e, address)
{
	if (address.list_new.checked) {
		address.showAddressForm(true);
	} else if (address.sensitive || address.sensitive == null) {
		address.hideAddressForm(true);
	}
}

StoreCheckoutAddressPage.prototype.provstateChangeHandler = function(
	e, address)
{
	if (this.provstate_other) {
		this.provstate_other.style.display =
			(this.provstate.selectedIndex == this.provstate_other_index) ?
			'block' : 'none';
	}
}

StoreCheckoutAddressPage.prototype.getFieldNames = function()
{
	return [];
}
