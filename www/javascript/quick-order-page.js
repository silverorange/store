/**
 * Controls the asynchronous loading of item descriptions for items on a
 * catalogue quick-order page
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
function StoreQuickOrder(id, num_rows)
{
	var self = this;
	this.id = id;
	this.boxes = [];
	this.descriptions = [];
	this.timers = [];

	function keyUpEvent(event)
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

		if (source.value != source._old_value) {
			var sku = source.value;
			var replicator_id = source._replicator_id;

			if (self.timers[replicator_id] != null)
				window.clearInterval(self.timers[replicator_id]);

			self.timers[replicator_id] = window.setTimeout(
				'StoreQuickOrder_staticTimeOut(' + self.id + '_obj, ' +
					replicator_id + ');', StoreQuickOrder.timeout_delay);

			source._old_value = source.value;
		}

		return true;
	}

	var box;
	var description;
	var is_ie = (document.addEventListener) ? false : true;
	for (var i = 0; i < num_rows; i++) {
		description = document.getElementById('description_' + i);
		description._sequence = 0;
		description._displayed_sequence = 0;
		description._effect = new fx.Opacity(description, {duration: 1000});
		this.descriptions[i] = description;

		box = document.getElementById('sku_' + i);
		box._replicator_id = i;
		box._old_value = box.value;
		if (is_ie)
			box.attachEvent('onkeyup', keyUpEvent);
		else
			box.addEventListener('keyup', keyUpEvent, true);

		this.boxes[i] = box;
		this.timers[i] = null;
	}
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
	var sku = quick_order.boxes[replicator_id].value;
	var description = quick_order.descriptions[replicator_id];
	description._sequence++;

	description.innerHTML = '<span class="loading">loading …</span>';

	function callBack(response)
	{
		if (response.sequence > description._displayed_sequence) {
			description._effect.custom(0, 1);
			description.innerHTML = response.description;
			description._displayed_sequence = response.sequence;
		}
	}

	client.callProcedure('getItemDescription',
		[sku, replicator_id, description._sequence],
		callBack);

	window.clearInterval(quick_order.timers[replicator_id]);
}
