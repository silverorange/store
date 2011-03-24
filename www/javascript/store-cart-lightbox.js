/**
 * A lightbox that displays items in the user's cart
 *
 * This should be instansiated using: StoreCartLightBox.getInstance();
 */
function StoreCartLightBox(available_entry_count, all_entry_count)
{
	this.status           = 'closed';
	this.product_id       = 0;
	this.current_request  = 0;
	this.analytics        = null;

	// accounts for the whitespace where the shadow appears
	this.cart_container_offset = -5;

	this.all_entry_count = all_entry_count;
	this.available_entry_count = available_entry_count;

	this.entries_added_event =
		new YAHOO.util.CustomEvent('entries_added', this);

	this.entry_removed_event =
		new YAHOO.util.CustomEvent('entry_removed', this);

	this.cart_empty_event =
		new YAHOO.util.CustomEvent('cart_empty', this);

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

StoreCartLightBox.instance = null;
StoreCartLightBox.submit_message = 'Updating Cart…';
StoreCartLightBox.loading_message = 'Loading…';
StoreCartLightBox.item_count_message_singular = '(1 item)';
StoreCartLightBox.item_count_message_plural   = '(%s items)';
StoreCartLightBox.empty_content = '<h3>Your Shopping Cart is Empty</h3>';

// static method to call an instance of StoreCartLightBox
// {{{ StoreCartLightBox.getInstance
StoreCartLightBox.getInstance = function(
	available_entry_count, all_entry_count)
{
	if (StoreCartLightBox.instance === null) {
		StoreCartLightBox.instance = new StoreCartLightBox(
			available_entry_count, all_entry_count);
	}

	return StoreCartLightBox.instance;
}

// }}}

// class methods
// {{{ StoreCartLightBox.prototype.init

StoreCartLightBox.prototype.init = function()
{
	this.configure();

	this.mini_cart = document.getElementById('store_cart_lightbox');
	this.content = document.getElementById('store_cart_lightbox_content');

	this.mini_cart.parentNode.removeChild(this.mini_cart);
	document.body.appendChild(this.mini_cart);

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

	YAHOO.util.Event.on(document, 'click', this.clickClose, this, true);
	YAHOO.util.Event.on(window, 'resize', this.handleWindowChange, this, true);

	this.activateLinks();
	this.preLoadImages();
}

// }}}
// {{{ StoreCartLightBox.prototype.configure

StoreCartLightBox.prototype.configure = function()
{
	this.xml_rpc_client = new XML_RPC_Client('xml-rpc/cart');
	this.cart_header_id = 'cart_link';
	this.cart_header_container_id = this.cart_header_id;
}

// }}}
// {{{ StoreCartLightBox.prototype.addEntries

StoreCartLightBox.prototype.addEntries = function(entries, source, source_category)
{
	var that = this;
	function callBack(response)
	{
		if (response.request_id == that.current_request) {
			that.addEntriesCallback(response);
			that.recordAnalytics('xml-rpc/mini-cart/add-entries');
		}
	}

	this.current_request++;

	this.xml_rpc_client.callProcedure(
		'addEntries', callBack,
		[this.current_request, entries, source, source_category, true],
		['int', 'array', 'int', 'int', 'boolean']);

	this.setContent(
		'<h3>' + StoreCartLightBox.submit_message + '</h3>');

	this.open(true);
}

// }}}
// {{{ StoreCartLightBox.prototype.load

StoreCartLightBox.prototype.load = function(e)
{
	YAHOO.util.Event.preventDefault(e);
	this.open();
}

// }}}
// {{{ StoreCartLightBox.prototype.open

StoreCartLightBox.prototype.open = function(is_status_opening)
{
	SwatZIndexManager.raiseElement(this.mini_cart);

	if (this.status != 'open' && this.status != 'opening') {
		YAHOO.util.Dom.setStyle(this.mini_cart, 'opacity', 0);
		this.mini_cart.style.display = 'block';
		this.position();

		this.content.style.height =
			this.getContentHeight(this.content.innerHTML) + 'px';

		var animation = new YAHOO.util.Anim(
			this.mini_cart,
			{ opacity: { from: 0, to: 1 }},
			0.3);

		animation.animate();

		if (is_status_opening) {
			this.status = 'opening';
		} else {
			this.status = 'open';
		}
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.displayResponse

StoreCartLightBox.prototype.displayResponse = function(response)
{
	if (this.all_entry_count == 0) {
		this.displayEmptyCartMessage();
	} else if (response.mini_cart) {
		this.setContentWithAnimation(response.mini_cart);
	}

	this.updateCartLink(response.cart_link);
	this.updateItemCount(response['total_entries']);
}

// }}}
// {{{ StoreCartLightBox.prototype.activateLinks

StoreCartLightBox.prototype.activateLinks = function()
{
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
// {{{ StoreCartLightBox.prototype.setContent

StoreCartLightBox.prototype.setContent = function(contents)
{
	this.position();

	this.content.innerHTML = contents;
	this.activateLinks();
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
	hidden_div.style.height = '0px';

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
		[this.current_request, entry_id, this.product_id],
		['int', 'int', 'int']);

	this.all_entry_count--;

	if (this.all_entry_count <= 0) {
		this.displayEmptyCartMessage();
	} else {
		var tr = this.getParentNode(button, 'tr');
		var div = this.getParentNode(button, 'div');
		if (YAHOO.util.Dom.hasClass(div, 'available')) {
			this.available_entry_count--;
			this.updateItemCount(this.available_entry_count);
		}

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
// {{{ StoreCartLightBox.prototype.clickClose

StoreCartLightBox.prototype.clickClose = function(e)
{
	if (YAHOO.util.Dom.hasClass(
		YAHOO.util.Event.getTarget(e), 'store-open-cart-link')) {
		return;
	}

	var ancestor = YAHOO.util.Dom.getAncestorByTagName(
		YAHOO.util.Event.getTarget(e), 'a');

	if (ancestor && YAHOO.util.Dom.hasClass(ancestor, 'store-open-cart-link')) {
		return;
	}

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
	if (this.status != 'closed') {
		this.position();
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.position

StoreCartLightBox.prototype.position = function()
{
	var region = YAHOO.util.Dom.getRegion(this.cart_header_container_id);
	var scroll_top;

	if (YAHOO.util.Dom.getStyle(this.mini_cart, 'position') == 'fixed') {
		scroll_top = YAHOO.util.Dom.getDocumentScrollTop();
	} else {
		scroll_top = 0;
	}

	var scroll_pos = region.bottom + this.cart_container_offset - scroll_top;
	var pos = Math.max(scroll_pos, this.cart_container_offset);
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

	// Reset opacity of parent element. Works around IE 6 rendering bug.
	this.mini_cart.style.filter = '';

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
	this.all_entry_count = response.total_entries + response.total_saved;
	this.available_entry_count = response.total_entries;
	this.displayResponse(response);
	this.status = 'open';
	this.entries_added_event.fire(response);
}

// }}}
// {{{ StoreCartLightBox.prototype.displayEmptyCartMessage

StoreCartLightBox.prototype.displayEmptyCartMessage = function()
{
	this.setContentWithAnimation('<div class="empty-content">' +
		StoreCartLightBox.empty_content + '</div>');

	this.cart_empty_event.fire();
}

// }}}
// {{{ StoreCartLightBox.prototype.updateItemCount

StoreCartLightBox.prototype.updateItemCount = function(item_count)
{
	var item_counts = YAHOO.util.Dom.getElementsByClassName(
		'item-count', '', this.mini_cart);

	var message = '';

	if (item_count === 1) {
		message = ' ' + StoreCartLightBox.item_count_message_singular;
	} else if (item_count > 1) {
		message = ' ' + StoreCartLightBox.item_count_message_plural.replace(
			/%s/, item_count);
	}

	for (var i = 0; i < item_counts.length; i++) {
		while (item_counts[i].firstChild) {
			item_counts[i].removeChild(item_counts[i].firstChild);
		}
		item_counts[i].appendChild(document.createTextNode(message));
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.updateCartLink

StoreCartLightBox.prototype.updateCartLink = function(link)
{
	var cart_link = document.getElementById(this.cart_header_id);
	cart_link.innerHTML = link;
}

// }}}
// {{{ StoreCartLightBox.prototype.recordAnalytics

StoreCartLightBox.prototype.recordAnalytics = function(uri)
{
	if (this.analytics == 'google_analytics') {
		_gaq.push(['_trackPageview'], uri);
	}
}

// }}}
// {{{ StoreCartLightBox.prototype.preLoadImages

StoreCartLightBox.prototype.preLoadImages = function()
{
	var preload = new Image();
	preload.src = 'packages/store/images/mini-cart-background.png';

	var preload_ie = new Image();
	preload_ie.src = 'packages/store/images/mini-cart-background-ie.png';
}

// }}}

