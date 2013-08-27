/**
 * Controls enabling/disabling price replicators for the item edit page
 *
 * @param String form_id the id of the edit form.
 * @param Array price_replicators a list of replicator ids for the price
 *                                 fields.
 */
function StoreItemEditPage(form_id, price_replicators)
{
	this.price_replicators = [];
	for (var i = 0; i < price_replicators.length; i++) {
		this.price_replicators[i] =
			this.getItemRegionReplicator(price_replicators[i]);
	}

	var form = document.getElementById(form_id);
	YAHOO.util.Event.addListener(form, 'submit', StoreItemEditPage.handleSubmit,
		this);
}

StoreItemEditPage.prototype.getItemRegionReplicator = function(id)
{
	return new StoreItemRegionReplicator(id);
}

StoreItemEditPage.handleSubmit = function(event, page)
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
function StoreItemRegionReplicator(id)
{
	this.id = id;
	this.enabled = document.getElementById('enabled_' + id);
	this.price = document.getElementById('price_' + id);
	this.price_field = document.getElementById('price_field_' + id);

	YAHOO.util.Event.addListener(this.enabled, 'click',
		StoreItemRegionReplicator.handleClick, this);

	// initialize
	if (this.enabled.checked)
		this.sensitize(false);
	else
		this.desensitize();
}

StoreItemRegionReplicator.handleClick = function(event, replicator)
{
	if (replicator.enabled.checked)
		replicator.sensitize(true);
	else
		replicator.desensitize();
}

StoreItemRegionReplicator.prototype.sensitize = function(focus)
{
	this.price.disabled = false;

	if (focus) {
		YAHOO.util.Dom.removeClass(this.price_field, 'swat-insensitive');
		YAHOO.util.Dom.removeClass(this.price, 'swat-insensitive');
		this.price.focus();
	}
}

StoreItemRegionReplicator.prototype.desensitize = function()
{
	this.price.disabled = true;
	YAHOO.util.Dom.addClass(this.price_field, 'swat-insensitive');
	YAHOO.util.Dom.addClass(this.price, 'swat-insensitive');
}
