/**
 * Extends the default product page to also do an xml-rpc driven mini cart
 *
 * @param integer the product id.
 * @param Array item_ids the array of item ids displayed on this product page.
 * @param integer the category the product belongs to.
 */
function StoreProductPageLightBox(product_id, item_ids, source_category)
{
	this.item_ids = item_ids;
	this.product_id = product_id;
	this.source_category = source_category;
	this.button_values = [];
	this.status = 'closed';
	this.current_request = 0;

	StoreProductPageLightBox.superclass.constructor.call(this);
}

StoreProductPageLightBox.submit_message = 'Updating Cart…';
StoreProductPageLightBox.loading_message = '<h2>Loading…</h2>';
StoreProductPageLightBox.empty_message = '<h2>All Items Removed</h2>' +
	'You no longer have any items from this page in your cart.';

YAHOO.lang.extend(StoreProductPageLightBox, StoreProductPage, {
// {{{ init: function()

init: function()
{
	this.configure();

	StoreProductPageLightBox.superclass.init.call(this);

	this.mini_cart_entry_count = 0;
	this.mini_cart = document.getElementById('store_product_cart');
	this.mini_cart_content = document.getElementById(
		'store_product_cart_content');

	// make the cart positioned visible, but off the page to preload images
	this.mini_cart.style.right = '-1000px';
	this.mini_cart.style.display = 'block';

	var cart_links = YAHOO.util.Dom.getElementsByClassName(
		'product-page-cart-link');

	if (cart_links.length > 0) {
		YAHOO.util.Event.on(cart_links, 'click', this.loadMiniCart, this, true);
	}

	YAHOO.util.Event.on(window, 'scroll', this.handleWindowChange, this, true);

	// make any click on the body close the mini-cart, except for the
	// mini-cart itself
	YAHOO.util.Event.on(this.mini_cart, 'click',
		function(e) {
			YAHOO.util.Event.stopPropagation(e);
	});

	YAHOO.util.Event.on(document.body, 'click', this.bodyCloseMiniCart, this, true);
	YAHOO.util.Event.on(window, 'resize', this.handleWindowChange, this, true);
},

// }}}
// {{{ handleFormSubmit: function()

handleFormSubmit: function(e)
{
	YAHOO.util.Event.preventDefault(e);

	if (!this.hasQuantity()) {
		this.openMiniCart('<h3>' +
			StoreProductPage.enter_quantity_message + '</h3>');

	} else {
		this.changeButtonText(e);
		var entries = [];

		for (var i = 0; i < this.item_ids.length; i++) {
			var entry = this.getEntry(this.item_ids[i]);

			if (entry !== null) {
				entries.push(entry);
			}
		}

		this.addEntriesToCart(entries);
	}
}

// }}}
});

// {{{ StoreProductPageLightBox.prototype.configure

StoreProductPageLightBox.prototype.configure = function()
{
	this.xml_rpc_client = new XML_RPC_Client('xml-rpc/cart');
	this.add_button_id = 'add_button';
	this.cart_message_id = 'product_page_cart';
	this.cart_header_id = 'cart_link';
}

// }}}
// {{{ StoreProductPageLightBox.prototype.changeButtonText

StoreProductPageLightBox.prototype.changeButtonText = function(e)
{
	var button = document.getElementById(this.add_button_id);
	button.disabled = true;
	this.saveButtonValue(button);
	button.value = StoreProductPageLightBox.submit_message;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getEntry

StoreProductPageLightBox.prototype.getEntry = function(item_id)
{
	var quantity = document.getElementById(
		'quantity_quantity_renderer_' + item_id).value;

	if (parseInt(quantity) > 0) {
		var entry = {};
		entry.item_id = item_id;
		entry.quantity = quantity;
	} else {
		var entry = null;
	}

	return entry;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.addEntriesToCart

StoreProductPageLightBox.prototype.addEntriesToCart = function(entries)
{
	var that = this;
	function callBack(response)
	{
		if (response.request_id == that.current_request) {
			that.mini_cart_entry_count = response.product_entries;
			that.displayResponse(response);
			that.status = 'open';
			that.resetForm();
		}
	}

	this.status = 'opening';
	this.current_request++;

	this.xml_rpc_client.callProcedure(
		'addEntries', callBack,
		[this.current_request, entries, this.source_category, true],
		['int', 'array', 'int', 'boolean']);

	this.openMiniCart('<h2>' + StoreProductPageLightBox.submit_message + '</h2>');
}

// }}}
// {{{ StoreProductPageLightBox.prototype.initMiniCart

StoreProductPageLightBox.prototype.initMiniCart = function()
{
}

// }}}
// {{{ StoreProductPageLightBox.prototype.openMiniCart

StoreProductPageLightBox.prototype.openMiniCart = function(contents)
{
	this.setMiniCartContent(contents);

	YAHOO.util.Dom.setStyle(this.mini_cart, 'opacity', 0);
	this.mini_cart.style.display = 'block';
	this.positionMiniCart();

	YAHOO.util.Dom.setStyle(this.mini_cart_content, 'height',
		this.getContentHeight(contents) + 'px');

	var cart_animation = new YAHOO.util.Anim(
		this.mini_cart,
		{ opacity: { from: 0, to: 1 }},
		0.3);

	cart_animation.animate();
}

// }}}
// {{{ StoreProductPageLightBox.prototype.loadMiniCart

StoreProductPageLightBox.prototype.loadMiniCart = function(e)
{
	YAHOO.util.Event.stopPropagation(e);
	YAHOO.util.Event.preventDefault(e);

	var that = this;
	function callBack(response)
	{
		if (response.request_id == that.current_request) {
			that.mini_cart_entry_count = response.product_entries;
			that.displayResponse(response);
			that.status = 'open';
		}
	}

	this.current_request++;
	this.xml_rpc_client.callProcedure(
		'getCartInfo', callBack,
		[this.current_request, this.product_id, true],
		['int', 'int', 'boolean']);

	this.openMiniCart(StoreProductPageLightBox.loading_message);
}

// }}}
// {{{ StoreProductPageLightBox.prototype.displayResponse

StoreProductPageLightBox.prototype.displayResponse = function(response)
{
	if (response.mini_cart) {
		this.setMiniCartContentWithAnimation(response.mini_cart);
	}

	this.updateCartMessage(response.cart_message);

	var cart_link = document.getElementById(this.cart_header_id);
	cart_link.innerHTML = response.cart_link;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.updateCartMessage

StoreProductPageLightBox.prototype.updateCartMessage = function(cart_message)
{
	var div = document.getElementById(this.cart_message_id);

	if (cart_message) {
		if (div.innerHTML == '') {
			YAHOO.util.Dom.setStyle(div, 'opacity', 0);
			div.innerHTML = cart_message;

			var animation = new YAHOO.util.Anim(
				div,
				{ opacity: { to: 1 }},
				0.3);

			animation.animate();
			
		} else {
			div.innerHTML = cart_message;
		}
	} else if (div.innerHTML != '') {
		var animation = new YAHOO.util.Anim(
			div,
			{ opacity: { to: 0 }},
			0.3);

		animation.onComplete.subscribe(function() {
			div.innerHTML = '';
		});

		animation.animate();
	}

	var cart_links = YAHOO.util.Dom.getElementsByClassName(
		'product-page-cart-link');

	if (cart_links.length > 0) {
		YAHOO.util.Event.on(cart_links, 'click', this.loadMiniCart, this, true);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.setMiniCartContent

StoreProductPageLightBox.prototype.setMiniCartContent = function(contents)
{
	this.positionMiniCart();

	this.mini_cart_content.innerHTML = contents;

	// activate any 'remove' buttons
	var remove_buttons = YAHOO.util.Dom.getElementsByClassName(
		'store-remove', 'input', this.mini_cart);

	if (remove_buttons.length != 0) {
		YAHOO.util.Event.on(remove_buttons, 'click',
			this.removeEntry, this, true);
	}

	// activate any 'close' links
	var close_buttons = YAHOO.util.Dom.getElementsByClassName(
		'store-close-cart', 'a', this.mini_cart);

	if (close_buttons.length != 0) {
		YAHOO.util.Event.on(close_buttons, 'click',
			this.closeMiniCart, this, true);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.setMiniCartContentWithAnimation

StoreProductPageLightBox.prototype.setMiniCartContentWithAnimation =
	function(contents)
{
	var old_height = this.mini_cart_content.offsetHeight;
	var new_height = this.getContentHeight(contents);

	var content_animation = new YAHOO.util.Anim(
		this.mini_cart_content,
		{ height: { to: new_height }},
		0.3);

	// to avoid content that overflows the div, change the content of the
	// div before the content_animation of the content is shorter, otherwise, set it
	// after the content_animation.

	var that = this;
	content_animation.onComplete.subscribe(function() {
		if (old_height < new_height) {
			that.setMiniCartContent(contents);
		}
	});

	content_animation.animate();

	if (old_height >= new_height) {
		this.setMiniCartContent(contents);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getContentHeight

StoreProductPageLightBox.prototype.getContentHeight = function(contents)
{
	var hidden_div = document.createElement('div');
	hidden_div.style.visiblility = 'hidden';
	hidden_div.height = 0;

	var hidden_content_div = document.createElement('div');
	hidden_content_div.innerHTML = contents;
	hidden_div.appendChild(hidden_content_div);
	this.mini_cart_content.appendChild(hidden_div);

	var new_height = hidden_content_div.offsetHeight;
	this.mini_cart_content.removeChild(hidden_div);

	return new_height;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getContainerTop

StoreProductPageLightBox.prototype.getContainerTop = function(contents)
{
	var content_height = this.getContentHeight(contents);

	var container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_content.offsetHeight + content_height)

	return Math.max(((YAHOO.util.Dom.getViewportHeight() -
		container_height) / 2), 0);
}

// }}}
// {{{ StoreProductPageLightBox.prototype.removeEntry

StoreProductPageLightBox.prototype.removeEntry = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	var button = YAHOO.util.Event.getTarget(e);
	var parts = button.id.split('_');
	var entry_id = parts[parts.length - 1];

	var that = this;
	function callBack(response)
	{
		if (response.request_id == that.current_request) {
			that.displayResponse(response);
		}
	}

	this.current_request++;
	this.xml_rpc_client.callProcedure(
		'removeEntry', callBack,
		[this.current_request, entry_id],
		['int', 'int']);

	this.mini_cart_entry_count--;

	if (this.mini_cart_entry_count <= 0) {
		this.setMiniCartContentWithAnimation('<div class="empty-message">' +
			StoreProductPageLightBox.empty_message + '</div>');

		this.updateCartMessage('');
	} else {
		var tr = this.getParentNode(button, 'tr');
		this.removeRow(tr, button);
		this.hideAddedMessage();
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.removeRow

StoreProductPageLightBox.prototype.removeRow = function(tr, button)
{
	var rows = tr.parentNode.childNodes;
	var index = null;

	for (var i = 0; i < rows.length; i++) {
		var remove_buttons = YAHOO.util.Dom.getElementsByClassName(
			'store-remove', 'input', rows[i]);

		if (remove_buttons.length > 0 && remove_buttons[0].id == button.id) {
			var index = i;
			break;
		}
	}

	if (index !== null) {
		var animation = new YAHOO.util.Anim(
			tr,
			{ opacity: { to: 0 }},
			0.3);

		var that = this;
		animation.onComplete.subscribe(function() {
			tr.parentNode.deleteRow(index);
			that.setMiniCartContentWithAnimation(
				that.mini_cart_content.innerHTML);
		});

		animation.animate();
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.resetForm

StoreProductPageLightBox.prototype.resetForm = function()
{
	this.restoreButtonValue(
		document.getElementById(this.add_button_id));

	// reset quantites
	for (var i = 0; i < this.quantity_boxes.length; i++) {
		this.quantity_boxes[i].value = 0;
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.closeMiniCart

StoreProductPageLightBox.prototype.closeMiniCart = function(e)
{
	if (e) {
		YAHOO.util.Event.preventDefault(e);
	}

	if (this.status == 'open') {
		var animation = new YAHOO.util.Anim(
			this.mini_cart,
			{ opacity: { to: 0 }},
			0.3);

		var that = this;
		animation.onComplete.subscribe(function() {
			if (that.status == 'closing') {
				that.mini_cart.style.display = 'none';
				that.status = 'closed';
			}
		});

		this.status = 'closing';
		animation.animate();
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.bodyCloseMiniCart

StoreProductPageLightBox.prototype.bodyCloseMiniCart = function(e)
{
	this.closeMiniCart();
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getParentNode

StoreProductPageLightBox.prototype.getParentNode = function(node, tag)
{
	if (node.tagName == tag.toUpperCase()) {
		return node;
	} else {
		return this.getParentNode(node.parentNode, tag);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.saveButtonValue

StoreProductPageLightBox.prototype.saveButtonValue = function(button)
{
	var value = {
		id: button.id,
		value: button.value
	}

	this.button_values.push(value);
}

// }}}
// {{{ StoreProductPageLightBox.prototype.restoreButtonValue

StoreProductPageLightBox.prototype.restoreButtonValue = function(button)
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
// {{{ StoreProductPageLightBox.prototype.handleWindowChange

StoreProductPageLightBox.prototype.handleWindowChange = function(contents)
{
	if (this.status == 'open') {
		this.positionMiniCart();
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.positionMiniCart

StoreProductPageLightBox.prototype.positionMiniCart = function()
{
	var region = YAHOO.util.Dom.getRegion(this.cart_header_id);
	var offset = -5; // accounts for the whitespace where the shadow appears

	var scroll_top = -1 * (YAHOO.util.Dom.getDocumentScrollTop() -
		region.bottom - offset);

	var pos = Math.max(scroll_top, offset);
	this.mini_cart.style.top = pos + 'px';

	this.mini_cart.style.right =
		(YAHOO.util.Dom.getViewportWidth() - region.right) + 'px';
}

// }}}
// {{{ StoreProductPageLightBox.prototype.hideAddedMessage

StoreProductPageLightBox.prototype.hideAddedMessage = function()
{
	var messages = YAHOO.util.Dom.getElementsByClassName(
		'added-message', 'div', this.mini_cart);

	for (var i = 0; i < messages.length; i++) {
		var animation = new YAHOO.util.Anim(
			messages[i],
			{ opacity: { to: 0 }},
			0.3);

		var that = this;
		animation.onComplete.subscribe(function() {
			messages[i].parentNode.removeChild(messages[i]);
		});

		animation.animate();
	}
}

// }}}
