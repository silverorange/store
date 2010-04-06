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
	this.item_ids = [];

	this.submitted = false;

	var item;
	for (var i = 0; i < num_rows; i++) {
		item = new StoreQuickOrderItem(this, item_selector_id, i);
		this.items.push(item);
		this.item_ids.push(item_selector_id + '_' + i);
	}

	YAHOO.util.Event.onDOMReady(this.initSubmitButton, null, this);
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
 * Sets an event handler on the submit button to prevent submitting the form
 * twice.
 */
StoreQuickOrder.prototype.initSubmitButton = function()
{
	var form = document.getElementById('sku_renderer_0').form;
	var buttons = YAHOO.util.Dom.getElementsByClassName(
		'swat-button', 'input', form);

	var that = this;

	YAHOO.util.Event.on(form, 'submit', function (e) {
		for (var i = 0; i < buttons.length; i++) {
			YAHOO.util.Dom.addClass(buttons[i], 'swat-insensitive');
			buttons[i].disabled = true;
		}

		if (that.submitted) {
			YAHOO.util.Event.preventDefault(e);
		}

		that.submitted = true;
	});
}

/**
 * Gets an item selector on the quick-order by the item selector's widget id
 *
 * @var String id
 *
 * @return StoreQuickOrderItem the item selector or null if it does not exist.
 */
StoreQuickOrder.prototype.getItemSelector = function(id)
{
	var selector = null;

	for (var i = 0; i < this.item_ids.length; i++) {
		if (this.item_ids[i] == id) {
			selector = this.items[i];
			break;
		}
	}

	return selector;
}

/**
 * Creates a new item-row controller for the quick-order page
 *
 * @param StoreQuickOrder quick_order
 * @param String item_selector_id
 * @param String id
 */
function StoreQuickOrderItem(quick_order, item_selector_id, id)
{
	this.id = id;
	this.quick_order = quick_order;
	this.quick_order_id = quick_order.id;
	this.div = document.getElementById(
		item_selector_id + '_renderer_' + id);

	this.sequence = 0;
	this.displayed_sequence = 0;

	this.out_effect = new YAHOO.util.Anim(this.div,
		{ opacity: { from: 1, to: 0 } }, 0.5);

	this.out_effect.onComplete.subscribe(this.handleFadeOut, this, true);

	this.in_effect = new YAHOO.util.Anim(this.div,
		{ opacity: { from: 0, to: 1 } }, 1);

	this.sku = document.getElementById('sku_renderer_' + id);
	this.old_value = this.sku.value;

	YAHOO.util.Event.on(this.sku, 'keyup', this.handleSkuChange, this, true);
	YAHOO.util.Event.on(this.sku, 'blur',  this.handleSkuChange, this, true);

	this.timer = null;
	this.new_description = null;

	// clear default quantities if JavaScript is enabled
	this.quantity = document.getElementById('quantity_renderer_' + id);
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

	var value = target.value.replace(/^\s+|\s+$/g, ''); // trim whitespace

	if (value != this.old_value) {
		var sku = value;

		if (!this.quantity.value && sku.length > 0)
			this.quantity.value = '1';

		if (this.timer != null)
			clearTimeout(this.timer);

		this.timer = setTimeout(
			'StoreQuickOrder_staticTimeOut(' + this.quick_order.id + '_obj, ' +
				this.id + ');', StoreQuickOrder.timeout_delay);

		this.old_value = value;
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
	if (!YAHOO.env.ua.ie || YAHOO.env.ua.ie > 8) {
		// only animate if not IE < 9
		this.in_effect.animate();
	}
}

/**
 * Change the SKU to something else
 *
 * The item selector content is updated immediately after changing the SKU.
 *
 * @param value the new sku value.
 */
StoreQuickOrderItem.prototype.setSku = function(value)
{
	var value = value.replace(/^\s+|\s+$/g, ''); // trim whitespace
	this.sku.value = value;
	this.old_value = value;
	StoreQuickOrder_staticTimeOut(this.quick_order, this.id);
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

	item.div.firstChild.innerHTML =
		'<span class="store-quick-order-item-loading">' +
		StoreQuickOrder.loading_text + '</span>';

	function callBack(response)
	{
		if (response.sequence > item.displayed_sequence) {
			item.new_description = response.description;
			item.displayed_sequence = response.sequence;
			if (!YAHOO.env.ua.ie || YAHOO.env.ua.ie > 8) {
				// only animate if not IE < 9
				item.out_effect.animate();
			} else {
				item.handleFadeOut();
			}
		}
	}

	client.callProcedure('getItemDescription', callBack,
		[sku, replicator_id, item.sequence],
		['string', 'string', 'int']);

	clearTimeout(item.timer);
}
