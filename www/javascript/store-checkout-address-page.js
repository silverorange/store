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

		this.initAutoComplete('billing_');
		this.initAutoComplete('shipping_');

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
};

StoreCheckoutAddressPage.prototype.initAutoComplete = function(prefix)
{
	// Auto-complete only works with input tags, not textarea
	var input = document.getElementById(prefix + 'address_line1');
	if (input && input.tagName !== 'INPUT') {
		return;
	}

	var autocomplete = new google.maps.places.Autocomplete(input);

	// prevent "enter" from submitting form
	google.maps.event.addDomListener(input, 'keydown', function(event) {
		if (event.keyCode === 13) {
			event.preventDefault();
		}
	});

	function setValue(name, value) {
		document.getElementById(prefix + name).value = value;
	}

	function fillInAddress() {
		var place = autocomplete.getPlace();
		var components = place.address_components;
		var parts = {};
		for (var i = 0; i < place.address_components.length; i++) {
			var addressType = place.address_components[i].types[0];
			parts[addressType] = place.address_components[i].short_name;i
		}

		console.log(place);
		console.log(parts);

		if (parts.street_number && parts.route) {
			setValue('address_line1', parts.street_number + ' ' + parts.route);
		} else if (parts.route) {
			setValue('address_line1', parts.route);
		}

		if (parts.locality) {
			setValue('address_city', parts.locality);
		}

		if (parts.postal_code) {
			setValue('address_postalcode', parts.postal_code);
		}

		if (parts.country) {
			var select = document.getElementById(prefix + 'address_country');
			for (var i = 0; i < select.options.length; i++) {
				if (select[i].value == parts.country) {
					select.selectedIndex = i;
					select.dispatchEvent(new Event('change'));
					break;
				}
			}
		}

		var code = parts.administrative_area_level_1;
		if (parts.country && code) {
			var id = false;
			var ids = StoreCheckoutAddressPage.prov_state_ids;
			for (var i = 0; i < ids.length; i++) {
				if (ids[i].country == parts.country && ids[i].code == code) {
					id = ids[i].id;
				}
			}

			var select = document.getElementById(
				prefix + 'address_provstate_flydown'
			);

			for (var i = 0; i < select.options.length; i++) {
				if (select[i].value == id) {
					select.selectedIndex = i;
					break;
				}
			}
		}
	}

	autocomplete.addListener('place_changed', fillInAddress);
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
};

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
};

StoreCheckoutAddressPage.clickHandler = function(e, address)
{
	if (address.list_new.checked) {
		address.showAddressForm(true);
	} else if (address.sensitive || address.sensitive === null) {
		address.hideAddressForm(true);
	}
};

StoreCheckoutAddressPage.prototype.getFieldNames = function()
{
	return [];
};
