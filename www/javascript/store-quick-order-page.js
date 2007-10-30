/**
 * Controls the asynchronous loading of item descriptions for items on a
 * catalog quick-order page
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */

/**
 * Creates a new quick-order page controller object
 *
 * @param String id
 * @param String item_selector_id
 * @param Number num_rows
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

/**
 * How long before the server call is made after you press a key
 *
 * @var Number
 */
StoreQuickOrder.timeout_delay = 250;

/**
 * Text to display when loading item description
 *
 * @var String
 */
StoreQuickOrder.loading_text = 'loading …';

/**
 * Creates a new item-row controller for the quick-order page
 *
 * @param String quick_order_id
 * @param String item_selector_id
 * @param String id
 */
function StoreQuickOrderItem(quick_order_id, item_selector_id, id)
{
	this.id = id;
	this.quick_order_id = quick_order_id;
	this.div = document.getElementById(item_selector_id + '_' + id);
	this.sequence = 0;
	this.displayed_sequence = 0;

	this.out_effect = new StoreOpacityAnimation(this.div,
		{ opacity: { from: 1, to: 0 } }, 0.5);

	this.out_effect.onComplete.subscribe(this.handleFadeOut, this, true);

	this.in_effect = new StoreOpacityAnimation(this.div,
		{ opacity: { from: 0, to: 1 } }, 1);

	this.sku = document.getElementById('sku_' + id);
	this.old_value = this.sku.value;

	YAHOO.util.Event.addListener(this.sku, 'keyup', this.handleSkuChange,
		this, true);

	YAHOO.util.Event.addListener(this.sku, 'blur', this.handleSkuChange,
		this, true);

	this.timer = null;
	this.new_description = null;

	// clear default quantities if JavaScript is enabled
	this.quantity = document.getElementById('quantity_' + id);
	if (this.quantity.value == '1') {
		// find containing TR tag
		var parent = this.quantity.parentNode;
		while (parent && parent.tagName != 'TR')
			parent = parent.parentNode;

		// only clear values on quantity fields that don't have errors
		if (!YAHOO.util.Dom.hasClass(parent, 'swat-error'))
			this.quantity.value = '';
	}
}

/**
 * Handles keyup and blur events on this item's sku field
 *
 * When a keyup or blur event is received and the content has changed, the
 * timeout timer is reset. If the sku field has content and the quantity field
 * is empty, the quantity field is set to 1.
 *
 * @param Event e
 */
StoreQuickOrderItem.prototype.handleSkuChange = function(e)
{
	var target = YAHOO.util.Event.getTarget(e);
	if (target.value != this.old_value) {
		var sku = target.value;

		if (!this.quantity.value && sku.length > 0)
			this.quantity.value = '1';

		if (this.timer != null)
			clearTimeout(this.timer);

		this.timer = setTimeout(
			'StoreQuickOrder_staticTimeOut(' + this.quick_order_id + '_obj, ' +
				this.id + ');', StoreQuickOrder.timeout_delay);

		this.old_value = target.value;
	}
}

/**
 * Handles the completing of fadeout animations on the item-selector field
 * of a StoreQuickOrderItem object
 *
 * After the fadeout is complete, the item-selector content is replaced and
 * faded-in.
 *
 * @param String type
 * @param Array args
 */
StoreQuickOrderItem.prototype.handleFadeOut = function(type, args)
{
	if (this.new_description != null)
		this.div.innerHTML = this.new_description;

	this.new_description = null;
	this.in_effect.animate();
}

/**
 * Handles a timeout on the sku field timer
 *
 * When a timeout occurs, a RPC call is made to get the item(s) matching the
 * entered sku.
 *
 * @param StoreQuickOrder quick_order
 * @param String replicator_id
 */
function StoreQuickOrder_staticTimeOut(quick_order, replicator_id)
{
	var client = new XML_RPC_Client('xml-rpc/quickorder');
	var item = quick_order.items[replicator_id];
	var sku = item.sku.value;
	item.sequence++;

	item.div.innerHTML = '<span class="store-quick-order-item-loading">' +
		StoreQuickOrder.loading_text + '</span>';

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

	clearTimeout(item.timer);
}
