/**
 * A lightbox that displays items in the user's cart 
 *
 * This should be instansiated using: StoreCartLightBox.getInstance();
 */
function StoreCartLightBox()
{
	this.status = 'closed';
	this.product_id      = 0;
	this.source_category = 0;
	this.current_request = 0;

	this.entries_added_event =
		new YAHOO.util.CustomEvent('entries_added', this);

	this.entry_removed_event =
		new YAHOO.util.CustomEvent('entry_removed', this);

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

StoreCartLightBox.instance = null;
StoreCartLightBox.submit_message = 'Updating Cart…';
StoreCartLightBox.loading_message = '<h2>Loading…</h2>';
StoreCartLightBox.empty_message = '<h2>Your Shopping Cart is Empty</h2>';

// static method to call an instance of StoreCartLightBox
// {{{ StoreCartLightBox.getInstance
StoreCartLightBox.getInstance = function()
{
	if (StoreCartLightBox.instance === null) {
		StoreCartLightBox.instance = new StoreCartLightBox();
	}

	return StoreCartLightBox.instance;
}

// }}}

// class methods
// {{{ StoreCartLightBox.prototype.init

StoreCartLightBox.prototype.init = function()
{
	this.configure();
	this.draw();

	this.entry_count = 0;

	var cart_links = YAHOO.util.Dom.getElementsByClassName(
		'store-open-cart-link');

	if (cart_links.length > 0) {
		YAHOO.util.Event.on(cart_links, 'click', this.load, this, true);
	}

	YAHOO.util.Event.on(window, 'scroll', this.handleWindowChange, this, true);

	// make any click on the body close the mini-cart, except for the
	// mini-cart itself
	YAHOO.util.Event.on(this.mini_cart, 'click',
		function(e) {
			YAHOO.util.Event.stopPropagation(e);
	});

	YAHOO.util.Event.on(document.body, 'click', this.bodyClose, this, true);
	YAHOO.util.Event.on(window, 'resize', this.handleWindowChange, this, true);
}

// }}}
// {{{ StoreCartLightBox.prototype.draw

StoreCartLightBox.prototype.draw = function()
{
	this.mini_cart = document.createElement('div');
	this.mini_cart.id = 'store_product_cart';

	// make the cart positioned visible, but off the page to preload images
	this.mini_cart.style.right = '-1000px';
	this.mini_cart.style.display = 'block';

	var div_top = document.createElement('div');
	div_top.id = 'store_product_cart_top';
	this.mini_cart.appendChild(div_top);

	var div_body = document.createElement('div'); 
	div_body.id = 'store_product_cart_body';
	this.content = document.createElement('div');
	this.content.id = 'store_product_cart_content';
	div_body.appendChild(this.content);
	this.mini_cart.appendChild(div_body);

	var div_bottom = document.createElement('div');
	div_bottom.id = 'store_product_cart_bottom';
	this.mini_cart.appendChild(div_bottom);

	document.body.appendChild(this.mini_cart);
}

// }}}
// {{{ StoreCartLightBox.prototype.configure

StoreCartLightBox.prototype.configure = function()
{
	this.xml_rpc_client = new XML_RPC_Client('xml-rpc/cart');
	this.cart_header_id = 'cart_link';
}

// }}}
// {{{ StoreCartLightBox.prototype.addEntries

StoreCartLightBox.prototype.addEntries = function(entries)
{
	var that = this;
	function callBack(response)
	{
		if (response.request_id == that.current_request) {
			that.addEntriesCallback(response);;
		}
	}

	this.status = 'opening';
	this.current_request++;

	this.xml_rpc_client.callProcedure(
		'addEntries', callBack,
		[this.current_request, entries, this.source_category, true],
		['int', 'array', 'int', 'boolean']);

	this.open('<h2>' + StoreCartLightBox.submit_message + '</h2>');
}

// }}}
// {{{ StoreCartLightBox.prototype.load

StoreCartLightBox.prototype.load = function(e)
{
	YAHOO.util.Event.stopPropagation(e);
	YAHOO.util.Event.preventDefault(e);

	var that = this;
	function callBack(response)
	{
		if (response.request_id == that.current_request) {
			that.entry_count = response.total_entries +
				response.total_saved;

			that.displayResponse(response);
			that.status = 'open';
		}
	}

	this.current_request++;
	this.xml_rpc_client.callProcedure(
		'getCartInfo', callBack,
		[this.current_request, this.product_id, true],
		['int', 'int', 'boolean']);

	this.open(StoreCartLightBox.loading_message);
}

// }}}
// {{{ StoreCartLightBox.prototype.open

StoreCartLightBox.prototype.open = function(contents)
{
	this.setContent(contents);

	YAHOO.util.Dom.setStyle(this.mini_cart, 'opacity', 0);
	this.mini_cart.style.display = 'block';
	this.position();

	YAHOO.util.Dom.setStyle(this.content, 'height',
		this.getContentHeight(contents) + 'px');

	var cart_animation = new YAHOO.util.Anim(
		this.mini_cart,
		{ opacity: { from: 0, to: 1 }},
		0.3);

	cart_animation.animate();
}

// }}}
// {{{ StoreCartLightBox.prototype.displayResponse

StoreCartLightBox.prototype.displayResponse = function(response)
{
	if (response.mini_cart) {
		this.setContentWithAnimation(response.mini_cart);
	}

	var cart_link = document.getElementById(this.cart_header_id);
	cart_link.innerHTML = response.cart_link;
}

// }}}
// {{{ StoreCartLightBox.prototype.setContent

StoreCartLightBox.prototype.setContent = function(contents)
{
	this.position();

	this.content.innerHTML = contents;

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
			this.close, this, true);
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.setContentWithAnimation

StoreCartLightBox.prototype.setContentWithAnimation =
	function(contents)
{
	var old_height = this.content.offsetHeight;
	var new_height = this.getContentHeight(contents);

	var content_animation = new YAHOO.util.Anim(
		this.content,
		{ height: { to: new_height }},
		0.3);

	// to avoid content that overflows the div, change the content of the
	// div before the content_animation of the content is shorter, otherwise, set it
	// after the content_animation.

	var that = this;
	content_animation.onComplete.subscribe(function() {
		if (old_height < new_height) {
			that.setContent(contents);
		}
	});

	content_animation.animate();

	if (old_height >= new_height) {
		this.setContent(contents);
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.getContentHeight

StoreCartLightBox.prototype.getContentHeight = function(contents)
{
	var hidden_div = document.createElement('div');
	hidden_div.style.visiblility = 'hidden';
	hidden_div.height = 0;

	var hidden_content_div = document.createElement('div');
	hidden_content_div.innerHTML = contents;
	hidden_div.appendChild(hidden_content_div);
	this.content.appendChild(hidden_div);

	var new_height = hidden_content_div.offsetHeight;
	this.content.removeChild(hidden_div);

	return new_height;
}

// }}}
// {{{ StoreCartLightBox.prototype.getContainerTop

StoreCartLightBox.prototype.getContainerTop = function(contents)
{
	var content_height = this.getContentHeight(contents);

	var container_height = (this.mini_cart.offsetHeight -
		this.content.offsetHeight + content_height)

	return Math.max(((YAHOO.util.Dom.getViewportHeight() -
		container_height) / 2), 0);
}

// }}}
// {{{ StoreCartLightBox.prototype.removeEntry

StoreCartLightBox.prototype.removeEntry = function(e)
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
			that.entry_removed_event.fire(response);
		}
	}

	this.current_request++;
	this.xml_rpc_client.callProcedure(
		'removeEntry', callBack,
		[this.current_request, entry_id],
		['int', 'int']);

	this.entry_count--;

	if (this.entry_count <= 0) {
		this.displayEmptyCartMessage();
	} else {
		var tr = this.getParentNode(button, 'tr');
		this.removeRow(tr, button);
		this.hideAddedMessage();
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.removeRow

StoreCartLightBox.prototype.removeRow = function(tr, button)
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
			that.setContentWithAnimation(
				that.content.innerHTML);
		});

		animation.animate();
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.close

StoreCartLightBox.prototype.close = function(e)
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
// {{{ StoreCartLightBox.prototype.bodyClose

StoreCartLightBox.prototype.bodyClose = function(e)
{
	this.close();
}

// }}}
// {{{ StoreCartLightBox.prototype.getParentNode

StoreCartLightBox.prototype.getParentNode = function(node, tag)
{
	if (node.tagName == tag.toUpperCase()) {
		return node;
	} else {
		return this.getParentNode(node.parentNode, tag);
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.saveButtonValue

StoreCartLightBox.prototype.saveButtonValue = function(button)
{
	var value = {
		id: button.id,
		value: button.value
	}

	this.button_values.push(value);
}

// }}}
// {{{ StoreCartLightBox.prototype.restoreButtonValue

StoreCartLightBox.prototype.restoreButtonValue = function(button)
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
// {{{ StoreCartLightBox.prototype.handleWindowChange

StoreCartLightBox.prototype.handleWindowChange = function(contents)
{
	if (this.status == 'open') {
		this.position();
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.position

StoreCartLightBox.prototype.position = function()
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
// {{{ StoreCartLightBox.prototype.hideAddedMessage

StoreCartLightBox.prototype.hideAddedMessage = function()
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
// {{{ StoreCartLightBox.prototype.addEntriesCallback

StoreCartLightBox.prototype.addEntriesCallback = function(response)
{
	this.entry_count = response.total_entries + response.total_saved;
	this.displayResponse(response);
	this.status = 'open';
	this.entries_added_event.fire(response);
}

// }}}
// {{{ StoreCartLightBox.prototype.displayEmptyCartMessage

StoreCartLightBox.prototype.displayEmptyCartMessage = function()
{
	this.setContentWithAnimation('<div class="empty-message">' +
		StoreCartLightBox.empty_message + '</div>');
}

// }}}
