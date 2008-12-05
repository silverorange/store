function StoreProductReviewView(id)
{
	this.id = id;
	this.container = document.getElementById(this.id);

	var descriptions = YAHOO.util.Dom.getElementsByClassName(
		'product-review-description', 'div', this.container);

	if (descriptions.length > 0) {
		this.description = descriptions[0];
	}

	var summaries = YAHOO.util.Dom.getElementsByClassName(
		'product-review-summary', 'div', this.container);

	if (summaries.length > 0) {
		this.summary = summaries[0];

		this.open_link = document.createElement('a');
		this.open_link.href = '#';
		this.open_link.appendChild(document.createTextNode(
			StoreProductReviewView.open_text));

		YAHOO.util.Event.on(this.open_link, 'click', function(e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.toggle();
		}, this, true);

		var paragraph = YAHOO.util.Dom.getLastChild(this.summary);
		paragraph.appendChild(this.open_link);

		this.close_link = document.createElement('a');
		this.close_link.href = '#';
		this.close_link.appendChild(document.createTextNode(
			StoreProductReviewView.close_text));

		YAHOO.util.Event.on(this.close_link, 'click', function(e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.toggle();
		}, this, true);

		var paragraph = document.createElement('p');
		paragraph.appendChild(this.close_link);
		this.description.appendChild(paragraph);
	}

	// prevent closing during opening animation and vice versa
	this.semaphore = false;

	// initial state
	this.opened = false;
	this.close();
}

StoreProductReviewView.open_text = 'read full comment';
StoreProductReviewView.close_text = 'show less';

StoreProductReviewView.prototype.toggle = function()
{
	if (this.opened) {
//		this.closeWithAnimation();
		this.close();
	} else {
//		this.openWithAnimation();
		this.open();
	}
}

StoreProductReviewView.prototype.openWithAnimation = function()
{
}

StoreProductReviewView.prototype.closeWithAnimation = function()
{
}

StoreProductReviewView.prototype.close = function()
{
	if (this.summary) {
		this.summary.style.display = 'block';
		this.description.style.display = 'none';
	}
	this.opened = false;
}

StoreProductReviewView.prototype.open = function()
{
	if (this.summary) {
		this.summary.style.display = 'none';
		this.description.style.display = 'block';
	}
	this.opened = true;
}
