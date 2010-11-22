/**
 * Store Product Page 
 *
 * @param integer the product id.
 * @param Array item_ids the array of item ids displayed on this product page.
 * @param integer the StoreCartEntry::SOURCE_* source.
 * @param integer the category the product belongs to.
 */
function StoreProductPage(product_id, item_ids, source, source_category)
{
	this.cart = null;

	this.item_ids = item_ids;
	this.product_id = product_id;
	this.source = source;
	this.source_category = source_category;
	this.button_values = [];
	this.current_request = 0;

	this.cart_message_id = 'product_page_cart';
	this.add_button_id = 'add_button';
	this.form = document.getElementById('form');

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

StoreProductPage.enter_quantity_message = 'Please enter a quantity.';

// base methods
// {{{ StoreProductPage.prototype.setCart

StoreProductPage.prototype.setCart = function(cart)
{
	this.cart = cart;
	this.cart.product_id = this.product_id;
}

// }}}
// {{{ StoreProductPage.prototype.init

StoreProductPage.prototype.init = function()
{
	this.quantity_boxes = YAHOO.util.Dom.getElementsByClassName(
		'store-quantity-entry', 'input', this.form);

	YAHOO.util.Event.on(this.form, 'submit',
		this.handleFormSubmit, this, true);

	// add listeners for cart methods
	if (this.cart !== null) {
		var that = this;

		function entriesAddedCallback(type, args, that) {
			var response = args[0];
			that.updateCartMessage(response);
			that.resetForm();
		}

		this.cart.entries_added_event.subscribe(
			entriesAddedCallback, this);

		function entryRemovedCallback(type, args, that) {
			var response = args[0];
			that.updateCartMessage(response);
		}

		this.cart.entry_removed_event.subscribe(
			entryRemovedCallback, this);

		function cartEmptyCallback(type, args, that) {
			that.updateCartMessage('');
		}

		this.cart.cart_empty_event.subscribe(
			cartEmptyCallback, this);
	}
}

// }}}
// {{{ StoreProductPage.prototype.handleFormSubmit

StoreProductPage.prototype.handleFormSubmit = function(e)
{
	if (this.cart === null) {
		if (!this.hasQuantity()) {
			YAHOO.util.Event.preventDefault(e);
			alert(StoreProductPage.enter_quantity_message);
		}
	} else {
		YAHOO.util.Event.preventDefault(e);

		if (!this.hasQuantity()) {
			this.openQuantityMessage();
		} else {
			this.addEntriesToCart();
		}
	}
}

// }}}
// {{{ StoreProductPage.prototype.hasQuantity

StoreProductPage.prototype.hasQuantity = function()
{
	var has_quantity = false;

	// check if any quantity box has a value
	for (var i = 0; i < this.quantity_boxes.length; i++) {
		if (this.quantity_boxes[i].value != 0) {
			has_quantity = true;
			break;
		}
	}

	return (this.quantity_boxes.length == 0 || has_quantity);
}

// }}}

// cart handling methods
// {{{ StoreProductPage.prototype.openQuantityMessage

StoreProductPage.prototype.openQuantityMessage = function()
{
	this.cart.setContent('<h3>' + StoreProductPage.enter_quantity_message +
		'</h3>');

	this.cart.open();
}

// }}}
// {{{ StoreProductPage.prototype.addEntriesToCart

StoreProductPage.prototype.addEntriesToCart = function()
{
	this.changeButtonText();
	var entries = [];

	for (var i = 0; i < this.item_ids.length; i++) {
		var entry = this.getEntry(this.item_ids[i]);

		if (entry !== null) {
			entries.push(entry);
		}
	}

	this.cart.addEntries(entries, this.source, this.source_category);
}

// }}}
// {{{ StoreProductPage.prototype.changeButtonText

StoreProductPage.prototype.changeButtonText = function(e)
{
	var button = document.getElementById(this.add_button_id);
	button.disabled = true;
	this.saveButtonValue(button);
	button.value = StoreCartLightBox.submit_message;
}

// }}}
// {{{ StoreProductPage.prototype.getEntry

StoreProductPage.prototype.getEntry = function(item_id)
{
	var quantity = document.getElementById(
		'quantity_quantity_renderer_' + item_id).value;

	if (parseInt(quantity) > 0) {
		var entry = {};
		entry.item_id = item_id;
		entry.quantity = parseFloat(quantity);
	} else {
		var entry = null;
	}

	return entry;
}

// }}}
// {{{ StoreProductPage.prototype.updateCartMessage

StoreProductPage.prototype.updateCartMessage = function(response)
{
	var div = document.getElementById(this.cart_message_id);

	if (response.cart_message) {
		if (div.innerHTML == '') {
			this.openCartMessage(response.cart_message);
		} else {
			div.innerHTML = response.cart_message;
			this.addCartMessageEvents(div);
		}
	} else if (div.innerHTML != '') {
		this.closeCartMessage();
	}
}

// }}}
// {{{ StoreProductPage.prototype.openCartMessage

StoreProductPage.prototype.openCartMessage = function(cart_message)
{
	var div = document.getElementById(this.cart_message_id);

	YAHOO.util.Dom.setStyle(div, 'opacity', 0);
	YAHOO.util.Dom.setStyle(div, 'height', 0);

	var hidden_div = document.createElement('div');
	hidden_div.innerHTML = cart_message;
	div.appendChild(hidden_div);
	var new_height = hidden_div.offsetHeight;
	div.removeChild(hidden_div);

	var height_animation = new YAHOO.util.Anim(
		div, { height: { to: new_height }}, 0.3);

	var opacity_animation = new YAHOO.util.Anim(
		div, { opacity: { to: 1 }}, 0.3);

	var that = this;

	height_animation.onComplete.subscribe(function() {
		div.innerHTML = cart_message;
		opacity_animation.animate();
		that.addCartMessageEvents(div);
	});

	height_animation.animate();
}

// }}}
// {{{ StoreProductPage.prototype.closeCartMessage

StoreProductPage.prototype.closeCartMessage = function()
{
	var div = document.getElementById(this.cart_message_id);

	var fade_animation = new YAHOO.util.Anim(
		div, { opacity: { to: 0 }}, 0.3);

	var height_animation = new YAHOO.util.Anim(
		div, { height: { to: 0 }}, 0.3);

	fade_animation.onComplete.subscribe(function() {
		height_animation.animate();
	});

	height_animation.onComplete.subscribe(function() {
		div.innerHTML = '';
	});

	fade_animation.animate();
}

// }}}
// {{{ StoreProductPage.prototype.addCartMessageEvents

StoreProductPage.prototype.addCartMessageEvents = function(div)
{
	var cart_links = YAHOO.util.Dom.getElementsByClassName(
		'store-open-cart-link', 'a', div);

	if (cart_links.length > 0) {
		YAHOO.util.Event.on(cart_links, 'click',
			this.cart.load, this.cart, true);
	}
}

// }}}
// {{{ StoreProductPage.prototype.resetForm

StoreProductPage.prototype.resetForm = function()
{
	this.restoreButtonValue(
		document.getElementById(this.add_button_id));

	// reset quantites
	for (var i = 0; i < this.quantity_boxes.length; i++) {
		this.quantity_boxes[i].value = 0;
	}
}

// }}}
// {{{ StoreProductPage.prototype.saveButtonValue

StoreProductPage.prototype.saveButtonValue = function(button)
{
	var value = {
		id: button.id,
		value: button.value
	}

	this.button_values.push(value);
}

// }}}
// {{{ StoreProductPage.prototype.restoreButtonValue

StoreProductPage.prototype.restoreButtonValue = function(button)
{
	for (var i = 0; i < this.button_values.length; i++) {
		if (this.button_values[i].id == button.id) {
			button.value = this.button_values[i].value;
			button.disabled = false;
			break;
		}
	}
}

// }}}
