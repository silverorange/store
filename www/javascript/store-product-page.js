/**
 * Displays an alert if there are no quantities entered on the items table
 * on a product page
 *
 * @param integer the product id.
 * @param Array item_ids the array of item ids displayed on this product page.
 * @param integer the category the product belongs to.
 */
function StoreProductPage(product_id, item_ids, source_category)
{
	this.item_ids = item_ids;
	this.product_id = product_id;
	this.source_category = source_category;
	this.button_values = [];

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

StoreProductPage.xml_rpc_client = new XML_RPC_Client('xml-rpc/cart');
StoreProductPage.enter_quantity_message = 'Please enter a quantity.';
StoreProductPage.submit_message = 'Updating Cart…';
StoreProductPage.loading_message = 'Loading…';
StoreProductPage.close_text = 'Close';
StoreProductPage.add_button_id = 'add_button';
StoreProductPage.empty_message = '<h2>All Items Removed</h2>' +
	'You no longer have any items from this page in your cart.';

// {{{ StoreProductPage.prototype.init

StoreProductPage.prototype.init = function()
{
	this.form = document.getElementById('form');

	this.quantity_boxes = YAHOO.util.Dom.getElementsByClassName(
		'store-quantity-entry', 'input', this.form);

	YAHOO.util.Event.on(this.form, 'submit',
		this.handleFormSubmit, this, true);

	var cart_links = YAHOO.util.Dom.getElementsByClassName(
		'product-page-cart-link');

	var that = this;

	YAHOO.util.Event.on(cart_links, 'click', this.loadMiniCart, this, true);

	this.initMiniCart();
}

// }}}
// {{{ StoreProductPage.prototype.handleFormSubmit

StoreProductPage.prototype.handleFormSubmit = function(e)
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
// {{{ StoreProductPage.prototype.changeButtonText

StoreProductPage.prototype.changeButtonText = function(e)
{
	var button = document.getElementById(StoreProductPage.add_button_id);
	this.saveButtonValue(button);
	button.value = StoreProductPage.submit_message;
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
		entry.quantity = quantity;
	} else {
		var entry = null;
	}

	return entry;
}

// }}}
// {{{ StoreProductPage.prototype.addEntriesToCart

StoreProductPage.prototype.addEntriesToCart = function(entries)
{
	var that = this;
	function callBack(response)
	{
		// TODO
		// update layout cart icon/info
		that.setMiniCartContentWithAnimation(response.mini_cart);
		that.resetForm();
		that.mini_cart_entry_count = response.product_items;
	}

	StoreProductPage.xml_rpc_client.callProcedure(
		'addEntries', callBack,
		[entries, this.source_category, true],
		['array', 'int', 'boolean']);

	this.openMiniCart('<h3>' + StoreProductPage.submit_message + '</h3>');
}

// }}}
// {{{ StoreProductPage.prototype.initMiniCart

StoreProductPage.prototype.initMiniCart = function()
{
	this.mini_cart_entry_count = 0;

	this.mini_cart = document.createElement('div');
	this.mini_cart.id = 'store_product_cart';
	this.mini_cart.style.display = 'none';

	var close_tag = document.createElement('a');
	close_tag.id = 'store_product_cart_close';
	close_tag.href = '#';
	close_tag.appendChild(document.createTextNode(StoreProductPage.close_text));
	YAHOO.util.Event.on(close_tag, 'click', this.closeMiniCart, this, true);
	this.mini_cart.appendChild(close_tag);

	this.mini_cart_contents = document.createElement('div');
	this.mini_cart.appendChild(this.mini_cart_contents);

	document.body.appendChild(this.mini_cart);

	this.mini_cart_overlay = document.createElement('div');
	this.mini_cart_overlay.id = 'store_product_cart_overlay';
	this.mini_cart_overlay.style.display = 'none';

	YAHOO.util.Event.on(this.mini_cart_overlay,
		'click', this.closeMiniCart, this, true);

	document.body.appendChild(this.mini_cart_overlay);

	YAHOO.util.Event.on(window, 'resize', this.positionMiniCart, this, true);
}

// }}}
// {{{ StoreProductPage.prototype.openMiniCart

StoreProductPage.prototype.openMiniCart = function(contents)
{
	this.setMiniCartContent(contents);

	YAHOO.util.Dom.setStyle(this.mini_cart, 'opacity', 0);
	YAHOO.util.Dom.setStyle(this.mini_cart_overlay, 'opacity', 0);
	this.mini_cart.style.display = 'block';
	this.mini_cart_overlay.style.display = 'block';

	this.positionMiniCart();

	var cart_animation = new YAHOO.util.Anim(
		this.mini_cart,
		{ opacity: { from: 0, to: 1 }},
		0.3);

	var overlay_animation = new YAHOO.util.Anim(
		this.mini_cart_overlay,
		{ opacity: { from: 0, to: 0.6 }},
		0.3);

	overlay_animation.animate();
	cart_animation.animate();
}

// }}}
// {{{ StoreProductPage.prototype.loadMiniCart

StoreProductPage.prototype.loadMiniCart = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	var that = this;
	function callBack(response)
	{
		that.setMiniCartContentWithAnimation(response);
	}

	StoreProductPage.xml_rpc_client.callProcedure(
		'getMiniCart', callBack, [this.product_id], ['int']);

	this.openMiniCart('<h3>' + StoreProductPage.loading_message + '</h3>');
}

// }}}
// {{{ StoreProductPage.prototype.positionMiniCart

StoreProductPage.prototype.positionMiniCart = function()
{
	var cart_width = this.mini_cart.offsetWidth;

	this.mini_cart.style.left =
		((YAHOO.util.Dom.getViewportWidth() - cart_width) / 2) + 'px';

	this.mini_cart.style.top = this.getContainerTop(
		this.mini_cart_contents.innerHTML) + 'px';

	this.mini_cart_contents.style.height = this.getContentHeight(
		this.mini_cart_contents.innerHTML) + 'px';

	// if contents are taller than the window, switch to scrolling
	if (this.mini_cart.offsetHeight >= YAHOO.util.Dom.getViewportHeight()) {
		this.mini_cart_contents.style.overflow = 'scroll';
	} else {
		this.mini_cart_contents.style.overflow = 'visible';
	}
}

// }}}
// {{{ StoreProductPage.prototype.setMiniCartContent

StoreProductPage.prototype.setMiniCartContent = function(contents)
{
	this.mini_cart_contents.innerHTML = contents;
	this.positionMiniCart();

	// activate any 'remove' buttons
	var remove_buttons = YAHOO.util.Dom.getElementsByClassName(
		'store-remove', 'input', this.mini_cart_contents);

	for (var i = 0; i < remove_buttons.length; i++) {
		YAHOO.util.Event.on(remove_buttons[i], 'click',
			this.removeEntry, this, true);
	}

	// activate any 'close' links
	var close_buttons = YAHOO.util.Dom.getElementsByClassName(
		'store-close-cart', 'a', this.mini_cart_contents);

	for (var i = 0; i < close_buttons.length; i++) {
		YAHOO.util.Event.on(close_buttons[i], 'click',
			this.closeMiniCart, this, true);
	}
}

// }}}
// {{{ StoreProductPage.prototype.setMiniCartContentWithAnimation

StoreProductPage.prototype.setMiniCartContentWithAnimation = function(contents)
{
	var old_height = this.mini_cart_contents.offsetHeight;
	var new_height = this.getContentHeight(contents);

	var content_animation = new YAHOO.util.Anim(
		this.mini_cart_contents,
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

	var container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_contents.offsetHeight + new_height)

	var container_top = (YAHOO.util.Dom.getViewportHeight() -
		container_height) / 2;

	var container_animation = new YAHOO.util.Anim(
		this.mini_cart,
		{ top: { to: container_top }},
		0.3);

	container_animation.animate();

	if (old_height >= new_height) {
		this.setMiniCartContent(contents);
	}
}

// }}}
// {{{ StoreProductPage.prototype.getContentHeight

StoreProductPage.prototype.getContentHeight = function(contents)
{
	var hidden_div = document.createElement('div');
	hidden_div.style.overflow = 'none';
	hidden_div.style.visiblility = 'hidden';
	hidden_div.height = 0;

	var hidden_content_div = document.createElement('div');
	hidden_content_div.innerHTML = contents;
	hidden_div.appendChild(hidden_content_div);
	this.mini_cart_contents.parentNode.appendChild(hidden_div);

	var new_height = hidden_content_div.offsetHeight;
	this.mini_cart_contents.parentNode.removeChild(hidden_div);

	// if contents are taller than the window, restrict height to
	// the available space
	var new_container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_contents.offsetHeight + new_height);

	if (new_container_height > YAHOO.util.Dom.getViewportHeight()) {
		new_height = (YAHOO.util.Dom.getViewportHeight() -
			(this.mini_cart.offsetHeight -
			(this.mini_cart_contents.offsetHeight)));
	}

	return new_height;
}

// }}}
// {{{ StoreProductPage.prototype.getContainerTop

StoreProductPage.prototype.getContainerTop = function(contents)
{
	var content_height = this.getContentHeight(contents);

	var container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_contents.offsetHeight + content_height)

	return Math.max(((YAHOO.util.Dom.getViewportHeight() -
		container_height) / 2), 0);
}

// }}}
// {{{ StoreProductPage.prototype.removeEntry

StoreProductPage.prototype.removeEntry = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	var button = YAHOO.util.Event.getTarget(e);
	var parts = button.id.split('_');
	var entry_id = parts[parts.length - 1];

	var that = this;
	function callBack(response)
	{
		// update layout cart icon/display
	}

	StoreProductPage.xml_rpc_client.callProcedure(
		'removeEntry', callBack,
		[entry_id],
		['int']);

	this.mini_cart_entry_count--;

	if (this.mini_cart_entry_count <= 0) {
		this.setMiniCartContentWithAnimation(StoreProductPage.empty_message);
	} else {
		var tr = this.getParentNode(button, 'tr');
		this.removeRow(tr, button);
	}
}

// }}}
// {{{ StoreProductPage.prototype.removeRow

StoreProductPage.prototype.removeRow = function(tr, button)
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

		animation.onComplete.subscribe(function() {
			tr.parentNode.deleteRow(index);
		});

		animation.animate();
	}
}

// }}}
// {{{ StoreProductPage.prototype.resetForm

StoreProductPage.prototype.resetForm = function()
{
	this.restoreButtonValue(
		document.getElementById(StoreProductPage.add_button_id));

	// reset quantites
	for (var i = 0; i < this.quantity_boxes.length; i++) {
		this.quantity_boxes[i].value = 0;
	}
}

// }}}
// {{{ StoreProductPage.prototype.closeMiniCart

StoreProductPage.prototype.closeMiniCart = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	var animation = new YAHOO.util.Anim(
		[this.mini_cart, this.mini_cart_overlay],
		{ opacity: { to: 0 }},
		0.3);

	var that = this;
	animation.onComplete.subscribe(function() {
		that.mini_cart.style.display = 'none';
		that.mini_cart_overlay.style.display = 'none';
	});

	animation.animate();

}

// }}}
// {{{ StoreProductPage.prototype.getParentNode

StoreProductPage.prototype.getParentNode = function(node, tag)
{
	if (node.tagName == tag.toUpperCase()) {
		return node;
	} else {
		return this.getParentNode(node.parentNode, tag);
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
			break;
		}
	}
}

// }}}


/**
 * Handles loading more product reviews
 *
 * @param Number product_id
 * @param Number offset
 * @param String replicator_id
 *
 * @copyright 2008 silverorange
 */
function StoreProductReviewPage(product_id, offset, replicator_id,
	disclosure_id, message, show_all)
{
	this.product_id    = product_id;
	this.offset        = offset;
	this.replicator_id = replicator_id;
	this.disclosure_id = disclosure_id;
	this.message       = message;
	this.show_all      = show_all;
	this.loaded        = false;

	if (this.show_all) {
		YAHOO.util.Event.onDOMReady(this.initReviews, this, true);
	}
}

StoreProductReviewPage.prototype.initReviews = function()
{
	this.review_disclosure = document.getElementById(this.disclosure_id);

	// create show-all link
	this.show_all_link = document.createElement('a');
	this.show_all_link.className = 'store-product-review-all';
	this.show_all_link.href = '#';
	this.show_all_link.appendChild(document.createTextNode(this.message));

	// create show-all span
	this.show_all_span = document.createElement('span');
	this.show_all_span.className = 'store-product-review-all ' +
		'store-product-review-all-insensitive';

	this.show_all_span.appendChild(document.createTextNode(this.message));

	// add link to disclosure header
	this.review_disclosure.insertBefore(this.show_all_link,
		this.review_disclosure.firstChild);

	// set up event handler
	YAHOO.util.Event.on(this.show_all_link, 'click', function(e)
	{
		YAHOO.util.Event.preventDefault(e);
		this.loadAllReviews();
	}, this, true);
};

StoreProductReviewPage.prototype.loadAllReviews = function()
{
	if (!this.show_all || this.loaded)
		return;

	// insensitize show-all link and add loading class
	this.review_disclosure.replaceChild(this.show_all_span,
		this.show_all_link);

	YAHOO.util.Dom.addClass(this.show_all_span,
		'store-product-review-all-loading');

	var that = this;
	function callBack(response)
	{
		var reviews_replicator = document.getElementById(that.replicator_id);

		if (!reviews_replicator)
			return;

		var counter = 0;

		// The following code adds each review to the DOM after a timeout. This
		// creates a nice scrolling effect as reviews are loaded.
		var addReview = function()
		{
			// We add a div to the DOM here, as appending innerHTML will
			// recreate other elements defined in the innerHTML. Recreating
			// these elements would break JavaScript that refers to these
			// elements.
			var div = document.createElement('div');
			div.innerHTML += response[counter].content;
			reviews_replicator.appendChild(div);

			// when the review is avaialable, run its JavaScript
			YAHOO.util.Event.onAvailable(response[counter].id, function()
			{
				eval(this.javascript);
			}, response[counter], true);

			counter++;
			if (counter < response.length) {
				setTimeout(addReview, 10);
			}
		};

		// Add the first review, this in turn sets the timeout for the second
		// review to be added.
		addReview();

		that.loaded = true;

		// remove loading style from show-all span
		YAHOO.util.Dom.removeClass(that.show_all_span,
			'store-product-review-all-loading');
	}

	// Make remote call to get more reviews for the product at the specified
	// offset.
	var client = new XML_RPC_Client('xml-rpc/product-reviews');
	client.callProcedure('getReviews', callBack,
		[this.product_id, 0, this.offset],
		['int', 'int', 'int']);
};
