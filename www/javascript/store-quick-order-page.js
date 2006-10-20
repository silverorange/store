/**
 * Controls the asynchronous loading of item descriptions for items on a
 * catalogue quick-order page
 *
 * @package   Store
 * @copyright 2006 silverorange
 */

function StoreQuickOrder(id, item_selector_id, num_rows)
{
	this.id = id;
	this.items = [];

	var item;
	for (var i = 0; i < num_rows; i++) {
		item = new StoreQuickOrderItem(this.id, item_selector_id, i);
		this.items.push(item);
	}
}

function StoreQuickOrderItem_keyUpEvent(event, item)
{
	var target = YAHOO.util.Event.getTarget(event);

	if (target.value != item.old_value) {
		var sku = target.value;

		if (!item.quantity.value && sku.length > 0)
			item.quantity.value = '1';

		if (item.timer != null)
			window.clearInterval(item.timer);

		item.timer = window.setTimeout(
			'StoreQuickOrder_staticTimeOut(' + item.quick_order_id + '_obj, ' +
				item.id + ');', StoreQuickOrder.timeout_delay);

		item.old_value = target.value;
	}
}

function StoreQuickOrderItem(quick_order_id, item_selector_id, id)
{
	this.id = id;
	this.quick_order_id = quick_order_id;
	this.div = document.getElementById(item_selector_id + '_' + id);
	this.sequence = 0;
	this.displayed_sequence = 0;

	this.out_effect = new YAHOO.util.Anim(this.div,
		{ opacity: { from: 1, to: 0 } }, 0.5);
	
	this.out_effect.onComplete.subscribe(StoreQuickOrderItem.handleFadeOut,
		this);

	this.in_effect = new YAHOO.util.Anim(this.div,
		{ opacity: { from: 0, to: 1 } }, 1);

	this.sku = document.getElementById('sku_' + id);
	this.old_value = this.sku.value;

	YAHOO.util.Event.addListener(this.sku, 'keyup',
		StoreQuickOrderItem_keyUpEvent, this);

	this.timer = null;
	this.new_description = null;

	this.quantity = document.getElementById('quantity_' + id);
	if (this.quantity.value == '1')
		this.quantity.value = '';
}

StoreQuickOrderItem.handleFadeOut = function(type, args, quick_order_item)
{
	if (quick_order_item.new_description != null)
		quick_order_item.div.innerHTML = quick_order_item.new_description;

	quick_order_item.new_description = null;
	quick_order_item.in_effect.animate();
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

	item.div.innerHTML = '<span class="store-quick-order-item-loading">' +
		'loading …</span>';

	function callBack(response)
	{
		if (response.sequence > item.displayed_sequence) {
			item.out_effect.animate();
			item.new_description = response.description;
			item.displayed_sequence = response.sequence;
		}
	}

	client.callProcedure('getItemDescription', callBack,
		[sku,      replicator_id, item.sequence],
		['string', 'string',      'int']);

	window.clearInterval(item.timer);
}
