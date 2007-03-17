/**
 * Displays an alert if there are no quantities entered on the items table
 * on a product page
 *
 * @param Array item_ids the array of item ids displayed on this product page.
 */
function StoreProductPage(item_ids)
{
	this.form = document.getElementById('form');
	this.quantity_boxes = [];

	var quantity_box;
	for (var i = 0; i < item_ids.length; i++) {
		quantity_box = document.getElementById('quantity_' + item_ids[i]);
		this.quantity_boxes.push(quantity_box);
	}

	if (this.form)
		YAHOO.util.Event.addListener(this.form, 'submit',
			StoreProductPage.handleFormSubmit, this);
}

StoreProductPage.enter_quantity_message = 'Please enter a quantity.';

StoreProductPage.handleFormSubmit = function(event, page)
{
	var no_quantities = true;

	// check if any quantity box has a value
	for (var i = 0; i < page.quantity_boxes.length; i++) {
		if (page.quantity_boxes[i].value != 0) {
			no_quantities = false;
			break;
		}
	}

	if (no_quantities) {
		YAHOO.util.Event.preventDefault(event);
		alert(StoreProductPage.enter_quantity_message);
	}
}
