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

		this.animate_div = document.createElement('div');
		this.animate_div.className = 'product-review-animation';
		this.container.appendChild(this.animate_div);
		var padding_div = document.createElement('div');
		padding_div.className = 'product-review-animation-padding';
		padding_div.appendChild(this.summary);
		padding_div.appendChild(this.description);
		this.animate_div.appendChild(padding_div);
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
		this.closeWithAnimation();
	} else {
		this.openWithAnimation();
	}
}

StoreProductReviewView.prototype.openWithAnimation = function()
{
	if (this.semaphore)
		return;

	this.description.parentNode.insertBefore(this.summary, this.description);

	// get current height
	this.animate_div.style.overflow = '';
	var old_height = this.animate_div.offsetHeight;

	// get new display height
	this.description.parentNode.style.overflow = 'hidden';
	this.description.parentNode.style.height = old_height + 'px';
	this.description.style.visibility = 'hidden';
	this.description.style.overflow = 'hidden';
	this.description.style.display = 'block';
	this.description.style.height = 'auto';
	var new_height = this.description.offsetHeight;
	this.description.style.height = old_height + 'px';
	this.description.style.visibility = 'visible';
	this.summary.style.display = 'none';
	this.description.parentNode.style.height = '';
	this.description.parentNode.style.overflow = 'visible';

	var attributes = { height: { to: new_height, from: old_height } };
	var animation = new YAHOO.util.Anim(this.description, attributes, 0.5,
		YAHOO.util.Easing.easeOut);

	this.semaphore = true;
	animation.onComplete.subscribe(this.handleOpen, this, true);
	animation.animate();

	this.opened = true;
}

StoreProductReviewView.prototype.closeWithAnimation = function()
{
	if (this.semaphore)
		return;

	this.summary.parentNode.insertBefore(this.description, this.summary);

	// get current height
	this.animate_div.style.overflow = '';
	var old_height = this.animate_div.offsetHeight;

	// get new display height
	this.summary.parentNode.style.overflow = 'hidden';
	this.summary.parentNode.style.height = old_height + 'px';
	this.summary.style.visibility = 'hidden';
	this.summary.style.overflow = 'hidden';
	this.summary.style.display = 'block';
	this.summary.style.height = 'auto';
	var new_height = this.summary.offsetHeight;
	this.description.style.height = old_height + 'px';
	this.summary.style.visibility = 'visible';
	this.summary.style.display = 'none';
	this.summary.parentNode.style.height = '';

	var attributes = { height: { to: new_height, from: old_height } };
	var animation = new YAHOO.util.Anim(this.description, attributes, 0.5,
		YAHOO.util.Easing.easeOut);

	this.semaphore = true;
	animation.onComplete.subscribe(this.handleClose, this, true);
	animation.animate();

	this.opened = false;
}

StoreProductReviewView.prototype.close = function()
{
	if (this.semaphore)
		return;

	if (this.summary) {
		this.summary.style.display = 'block';
		this.description.style.display = 'none';
	}

	this.opened = false;
}

StoreProductReviewView.prototype.open = function()
{
	if (this.semaphore)
		return;

	if (this.summary) {
		this.description.style.display = 'block';
		this.summary.style.display = 'none';
	}

	this.opened = true;
}

StoreProductReviewView.prototype.handleClose= function()
{
	// allow font resizing to work again
	this.summary.style.height = 'auto';

	// re-set overflow to visible for styles that might depend on it
	this.summary.style.display = 'block';
	this.description.style.display = 'none';

	this.semaphore = false;
}

StoreProductReviewView.prototype.handleOpen = function()
{
	// allow font resizing to work again
	this.description.style.height = 'auto';

	// re-set overflow to visible for styles that might depend on it
	this.description.style.overflow = 'visible';

	this.semaphore = false;
}
