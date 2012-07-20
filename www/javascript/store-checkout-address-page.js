function StoreCheckoutAddressPage(id)
{
	this.id = id;
	this.sensitive = null;
	this.status = 'none';
	this.fields = this.getFieldNames();
	this.semaphore = false;

	// set up event handlers
	for (var i = 0; i < this.list.length; i++)
		YAHOO.util.Event.addListener(this.list[i], 'click',
			StoreCheckoutAddressPage.clickHandler, this);

	// initialize state
	YAHOO.util.Event.onDOMReady(function() {
		if (this.container) {
			this.initContainer();
		}

		// hide if the radio button state is set to hidden
		if (this.list_new && !this.list_new.checked) {
			this.hideAddressForm(false);
		}
	}, this, true);
}

StoreCheckoutAddressPage.prototype.initContainer = function()
{
	var div = document.createElement('div');
	this.container.parentNode.replaceChild(div, this.container);
	div.appendChild(this.container);

	var duration = 0.25;

	this.show_animation = new YAHOO.util.Anim(
		div, { height: { to: this.container.offsetHeight } }, duration,
		YAHOO.util.Easing.easeOut);

	this.show_animation.onComplete.subscribe(function() {
		this.container.parentNode.style.overflow = 'visible';
		this.container.parentNode.style.height = 'auto';
		YAHOO.util.Dom.addClass(div, 'store-checkout-address-open');

		var fade_in = new YAHOO.util.Anim(
			this.container, { opacity: { to: 1 } }, duration,
			YAHOO.util.Easing.easeOut);

		fade_in.animate();
	}, this, true);

	this.hide_animation = new YAHOO.util.Anim(
		this.container, { opacity: { to: 0 } }, duration,
		YAHOO.util.Easing.easeOut);

	this.hide_animation.onComplete.subscribe(function() {
		YAHOO.util.Dom.addClass(div, 'store-checkout-address-closed');

		var collapse = new YAHOO.util.Anim(
			div, { height: { to: 0 } }, duration,
			YAHOO.util.Easing.easeOut);

		collapse.onComplete.subscribe(function() {
			var display = YAHOO.util.Dom.getStyle(this.container, 'display');

			if (display != 'none') {
				this.container._old_display = display;
			}

			this.container.style.display = 'none';
		}, this, true);

		collapse.animate();

	}, this, true);
}

StoreCheckoutAddressPage.prototype.showAddressForm = function(animate)
{
	if (this.container) {
		var div = this.container.parentNode;

		if (animate) {
			if (this.status == 'open') {
				return; // don't re-animate if already open
			} else if (this.semaphore) {
				this.hide_animation.stop();
			}

			div.style.height = '0';
			if (this.container._old_display) {
				this.container.style.display = this.container._old_display;
			} else {
				this.container.style.display = 'block';
			}
			this.container.style.opacity = 0;

			this.semaphore = true;
			this.show_animation.animate();
		} else {
			if (this.container._old_display) {
				this.container.style.display = this.container._old_display;
			} else {
				this.container.style.display = 'block';
			}
			div.style.height = 'auto';
			div.style.width = '100%';
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
		this.container.parentNode.style.overflow = 'hidden';
		if (animate) {
			if (this.status == 'closed') {
				return; // don't re-animate if already closed
			} else if (this.semaphore) {
				this.show_animation.stop();
			}

			this.hide_animation.animate();
		} else {
			var display = YAHOO.util.Dom.getStyle(this.container, 'display');

			if (display != 'none') {
				this.container._old_display = display;
			}

			this.container.style.display = 'none';
		}

		this.status = 'closed';
	} else {
		var fields = [];
		for (var i = 0; i < this.fields.length; i++) {
			fields.push(this.fields[i]);
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

StoreCheckoutAddressPage.prototype.getFieldNames = function()
{
	return [];
}
