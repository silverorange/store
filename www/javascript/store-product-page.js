/**
 * Displays an alert if there are no quantities entered on the items table
 * on a product page
 */
function StoreProductPage()
{
	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

StoreProductPage.enter_quantity_message = 'Please enter a quantity.';

// {{{ StoreProductPage.prototype.init

StoreProductPage.prototype.init = function()
{
	this.form = document.getElementById('form');

	this.quantity_boxes = YAHOO.util.Dom.getElementsByClassName(
		'store-quantity-entry', 'input', this.form);

	YAHOO.util.Event.on(this.form, 'submit',
		this.handleFormSubmit, this, true);
}

// }}}
// {{{ StoreProductPage.prototype.handleFormSubmit

StoreProductPage.prototype.handleFormSubmit = function(e)
{
	if (!this.hasQuantity()) {
		YAHOO.util.Event.preventDefault(e);
		alert(StoreProductPage.enter_quantity_message);
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
