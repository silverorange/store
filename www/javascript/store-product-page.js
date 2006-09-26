/**
 * Displays an alert if there are no quantities entered on the items table
 * on a product page
 *
 * @param Array item_ids the array of item ids displayed on this product page.
 * @param String form_id the id of the form used to submit item data for this
 *                        product page.
 */
function ProductPage(item_ids, form_id)
{
	var self = this;
	var is_ie = (document.addEventListener) ? false : true;

	this.form = document.getElementById(form_id);
	this.quantity_boxes = [];

	var quantity_box;
	for (var i = 0; i < item_ids.length; i++) {
		quantity_box = document.getElementById('quantity_' + item_ids[i]);
		this.quantity_boxes.push(quantity_box);
	}

	function handleSubmit(event)
	{
		var no_quantities = true;

		// check if any quantity box has a value
		for (var i = 0; i < self.quantity_boxes.length; i++) {
			if (self.quantity_boxes[i].value != 0) {
				no_quantities = false;
				break;
			}
		}

		event = event || window.event;

		// cancel form submit
		if (event && no_quantities) {
			if (event.preventDefault)
				event.preventDefault();
			else
				event.returnValue = false;

			alert('Please enter a quantity.');
		}

		return true;
	}

	// make sure a quantity is entered on form submit
	if (is_ie)
		this.form.attachEvent('onsubmit', handleSubmit);
	else
		this.form.addEventListener('submit', handleSubmit, false);
}
