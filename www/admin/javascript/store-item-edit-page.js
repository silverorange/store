/**
 * Enables/disables the limited stock quantity input based on a the status
 * radio list
 *
 * Also sets up price replicators.
 *
 * @param String form_id the id of the edit form.
 * @param String limited_stock_id the id of the limited stock quantity input.
 * @param String radio_button_id the id of the controlling radio button in the
 *                                status radio list.
 * @param Array price_replicators a list of replicator ids for the price
 *                                 fields.
 */
function ItemEditPage(form_id, limited_stock_id, radio_button_id,
	price_replicators)
{
	var self = this;
	var is_ie = (document.addEventListener) ? false : true;

	this.price_replicators = [];
	for (var i = 0; i < price_replicators.length; i++) {
		this.price_replicators[i] =
			new ItemRegionReplicator(price_replicators[i]);
	}

	this.form = document.getElementById(form_id);
	this.limited_stock_status = document.getElementById(radio_button_id);
	this.limited_stock_quantity = document.getElementById(limited_stock_id);

	function handleClick(event)
	{
		if (self.limited_stock_status.checked)
			self.sensitizeLimitedStock(true);
		else
			self.desensitizeLimitedStock();
	}

	function handleSubmit(event)
	{
		// sensitize prices so they send data even if they are desensitized
		for (var i = 0; i < self.price_replicators.length; i++)
			self.price_replicators[i].sensitize(false);
	}

	var radio_buttons =
		document.getElementsByName(this.limited_stock_status.name);

	if (is_ie) {
		for (var i = 0; i < radio_buttons.length; i++)
			radio_buttons[i].attachEvent('onclick', handleClick);

		this.form.attachEvent('onsubmit', handleSubmit);
	} else {
		for (var i = 0; i < radio_buttons.length; i++)
			radio_buttons[i].addEventListener('click', handleClick, false);

		this.form.addEventListener('submit', handleSubmit, false);
	}

	// initialize
	// set to null so initilization from form values works properly
	this.limited_stock_sensitive = null;
	if (this.limited_stock_status.checked)
		this.sensitizeLimitedStock(false);
	else
		this.desensitizeLimitedStock();
}

ItemEditPage.prototype.sensitizeLimitedStock = function(focus)
{
	// check !== true on purpose so initilization from form values works
	if (this.limited_stock_sensitive !== true) {
		this.limited_stock_quantity.disabled = false;
		this.limited_stock_quantity.className =
			this.limited_stock_quantity.className.replace(
				/ *swat-insensitive/, '');

		if (focus)
			this.limited_stock_quantity.focus();

		this.limited_stock_sensitive = true;
	}
}

ItemEditPage.prototype.desensitizeLimitedStock = function()
{
	// check !== false on purpose so initilization from form values works
	if (this.limited_stock_sensitive !== false) {
		this.limited_stock_quantity.disabled = true;
		this.limited_stock_quantity.className += ' swat-insensitive';
		this.limited_stock_sensitive = false;
	}
}

/**
 * A single price replicator on the item edit page
 *
 * @param number id the replicator id of this price replicator.
 */
function ItemRegionReplicator(id)
{
	var self = this;
	var is_ie = (document.addEventListener) ? false : true;

	this.id = id;
	this.enabled = document.getElementById('enabled_price_replicator' + id);
	this.price = document.getElementById('price_price_replicator' + id);
	this.special_shipping_amount = document.getElementById(
		'special_shipping_amount_special_shipping_amount_replicator' + id);

	function handleClick(event)
	{
		if (self.enabled.checked)
			self.sensitize(true);
		else
			self.desensitize();
	}

	if (is_ie)
		this.enabled.attachEvent('onclick', handleClick);
	else
		this.enabled.addEventListener('click', handleClick, false);

	// initialize
	if (this.enabled.checked)
		this.sensitize(false);
	else
		this.desensitize();
}

ItemRegionReplicator.prototype.sensitize = function(focus)
{
	this.price.disabled = false;
	this.special_shipping_amount.disabled = false;

	if (focus) {
		this.price.className = this.price.className.replace(
			/ *swat-insensitive/, '');

		this.special_shipping_amount.className =
			this.special_shipping_amount.className.replace(
				/ *swat-insensitive/, '');

		this.price.focus();
	}
}

ItemRegionReplicator.prototype.desensitize = function()
{
	this.price.disabled = true;
	this.price.className += ' swat-insensitive';
	this.special_shipping_amount.disabled = true;
	this.special_shipping_amount.className += ' swat-insensitive';
}
