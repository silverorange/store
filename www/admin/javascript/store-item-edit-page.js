/**
 * Controls enabling/disabling price replicators for the item edit page
 *
 * @param String form_id the id of the edit form.
 * @param Array price_replicators a list of replicator ids for the price
 *                                 fields.
 */
function ItemEditPage(form_id, price_replicators)
{
	this.price_replicators = [];
	for (var i = 0; i < price_replicators.length; i++) {
		this.price_replicators[i] =
			new ItemRegionReplicator(price_replicators[i]);
	}

	var form = document.getElementById(form_id);
	YAHOO.util.Event.addListener(form, 'submit', ItemEditPage.handleSubmit,
		this);
}

ItemEditPage.handleSubmit = function(event, page)
{
	// sensitize prices so they send data even if they are desensitized
	for (var i = 0; i < page.price_replicators.length; i++)
		page.price_replicators[i].sensitize(false);
}

/**
 * A single price replicator on the item edit page
 *
 * @param number id the replicator id of this price replicator.
 */
function ItemRegionReplicator(id)
{
	this.id = id;
	this.enabled = document.getElementById('enabled_price_replicator' + id);
	this.price = document.getElementById('price_price_replicator' + id);

	YAHOO.util.Event.addListener(this.enabled, 'click',
		ItemRegionReplicator.handleClick, this);

	// initialize
	if (this.enabled.checked)
		this.sensitize(false);
	else
		this.desensitize();
}

ItemRegionReplicator.handleClick = function(event, replicator)
{
	if (replicator.enabled.checked)
		replicator.sensitize(true);
	else
		replicator.desensitize();
}

ItemRegionReplicator.prototype.sensitize = function(focus)
{
	this.price.disabled = false;
	this.special_shipping_amount.disabled = false;

	if (focus) {
		YAHOO.util.Dom.removeClass(this.price, 'swat-insensitive');
		this.price.focus();
	}
}

ItemRegionReplicator.prototype.desensitize = function()
{
	this.price.disabled = true;
	YAHOO.util.Dom.addClass(this.price, 'swat-insensitive');
}
