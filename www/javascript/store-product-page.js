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
		quantity_box = document.getElementById(
			'quantity_quantity_renderer_' + item_ids[i]);

		if (quantity_box)
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

	if (page.quantity_boxes.length > 0 && no_quantities) {
		YAHOO.util.Event.preventDefault(event);
		alert(StoreProductPage.enter_quantity_message);
	}
}

/**
 * Handles loading more product reviews
 *
 * @param Number product_id
 * @param Number offset
 * @param String replicator_id
 *
 * @copyright 2008 silverorange
 */
function StoreProductReviewPage(product_id, offset, replicator_id, message,
	show_more)
{
	this.product_id    = product_id;
	this.offset        = offset;
	this.replicator_id = replicator_id;
	this.message       = message;
	this.show_more     = show_more;

	if (this.show_more) {
		YAHOO.util.Event.onDOMReady(this.initReviews, this, true);
	}
}

StoreProductReviewPage.prototype.initReviews = function()
{
	var show_more_link = document.getElementById('product_reviews_frame').firstChild;
	if (show_more_link) {
		YAHOO.util.Event.on(show_more_link, 'click', function(e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.loadAllReviews();
		}, this, true);
	}
}

StoreProductReviewPage.prototype.loadAllReviews = function()
{
	if (!this.show_more)
		return;

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
	}

	// Make remote call to get more reviews for the product at the specified
	// offset.
	var client = new XML_RPC_Client('xml-rpc/product-reviews');
	client.callProcedure('getReviews', callBack,
		[this.product_id, 0, this.offset],
		['int', 'int', 'int']);
}
