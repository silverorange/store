/**
 * Controls the asynchronous loading of item descriptions for items on a
 * catalogue quick-order page
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
function StoreQuickOrder(id, num_rows)
{
	this.id = id;
	this.items = [];

	var item;
	for (var i = 0; i < num_rows; i++) {
		item = new StoreQuickOrderItem(this.id, i);
		this.items.push(item);
	}
}

function StoreQuickOrderItem_keyUpEvent(event)
{
	if (typeof event == 'undefined')
		var event = window.event;

	var source;
	if (typeof event.target != 'undefined')
		source = event.target;
	else if (typeof event.srcElement != 'undefined')
		source = event.srcElement;
	else
		return true;
	
	var item = source._object;

	if (source.value != item.old_value) {
		var sku = source.value;

		if (item.timer != null)
			window.clearInterval(item.timer);

		item.timer = window.setTimeout(
			'StoreQuickOrder_staticTimeOut(' + item.quick_order_id + '_obj, ' +
				item.id + ');', StoreQuickOrder.timeout_delay);

		item.old_value = source.value;
	}

	return true;
}

function StoreQuickOrderItem(quick_order_id, id)
{
	var self = this;
	var is_ie = (document.addEventListener) ? false : true;

	this.id = id;
	this.quick_order_id = quick_order_id;
	this.div = document.getElementById('description_' + id);
	this.sequence = 0;
	this.displayed_sequence = 0;
	this.out_effect = new fx.Opacity(this.div, {duration: 500,
		onComplete: function() { self.fadeIn(); }});

	this.in_effect = new fx.Opacity(this.div, {duration: 1000});

	this.sku = document.getElementById('sku_' + id);
	this.sku._object = this;
	this.old_value = this.sku.value;
	if (is_ie)
		this.sku.attachEvent('onkeyup', StoreQuickOrderItem_keyUpEvent);
	else
		this.sku.addEventListener('keyup', StoreQuickOrderItem_keyUpEvent, true);

	this.timer = null;
	this.new_description = null;

	this.quantity = document.getElementById('quantity_' + id);
	if (this.quantity.value == '1')
		this.quantity.value = '';
}

StoreQuickOrderItem.prototype.fadeIn = function()
{
	if (!this.quantity.value && this.new_description.length > 0)
		this.quantity.value = '1';

	if (this.new_description != null)
		this.div.innerHTML = this.new_description;

	this.new_description = null;

	this.in_effect.custom(0, 1);
}

StoreQuickOrderItem.prototype.fadeOut = function()
{
	this.out_effect.custom(1, 0);
}

/**
 * How long before the server call is made after you press a key
 *
 * @var integer
 */
StoreQuickOrder.timeout_delay = 250;

function StoreQuickOrder_staticTimeOut(quick_order, replicator_id)
{
	var client = new XML_RPC_Client('xml-rpc/quickorder');
	var item = quick_order.items[replicator_id];
	var sku = item.sku.value;
	item.sequence++;

	item.div.innerHTML = '<span class="loading">loading …</span>';

	function callBack(response)
	{
		if (response.sequence > item.displayed_sequence) {
			item.fadeOut();
			item.new_description = response.description;
			item.displayed_sequence = response.sequence;
		}
	}

	client.callProcedure('getItemDescription',
		[sku, replicator_id, item.sequence], callBack);

	window.clearInterval(item.timer);
}
