<?php

require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Generates Google Analytics order transaction tracking code for an order
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAnalyticsOrderTracker
{
	/**
	 * @var StoreOrder
	 */
	protected $order;

	protected $affiliation;

	public function __construct(StoreOrder $order, $affiliation = null)
	{
		$this->order = $order;
		$this->affiliation = $affiliation;
	}

	public function getInlineJavaScript()
	{
		$utm_content = $this->getOrder($this->order);
		foreach ($this->order->items as $item) {
			$utm_content.= "\n".$this->getOrderItem($this->order, $item);
		}
		$utm_content = SwatString::quoteJavaScriptString($utm_content);

		// {{{ returned JavaScript
		return <<<JAVASCRIPT
	var transaction_text = document.createTextNode($utm_content);

	var utm_trans = document.createElement('textarea');
	utm_trans.id = 'utmtrans';
	utm_trans.appendChild(transaction_text);

	var utm_form = document.createElement('form');
	utm_form.id = 'utmform';
	utm_form.name = 'utmform';
	utm_form.style.display = 'none';
	utm_form.appendChild(utm_trans);

	var body = document.getElementsByTagName('body')[0];
	body.appendChild(utm_form);

	YAHOO.util.Event.onAvailable('utmform', __utmSetTrans);

JAVASCRIPT;
		// }}}
	}

	// {{{ protected function getOrder()

	protected function getOrder(StoreOrder $order)
	{
		$billing_address = $order->billing_address;

		$provstate_title = ($billing_address->provstate === null) ?
			$billing_address->provstate_other :
			$billing_address->provstate->title;

		/*
		 * Shipping and tax fields cannot be 0 according to Google Analytics
		 * support article:
		 * http://www.google.com/support/analytics/bin/answer.py?answer=72291
		 * This is a workaround.
		 */
		$tax_total = ($order->tax_total == 0) ? '' : $order->tax_total;
		$shipping_total = ($order->shipping_total == 0) ?
			'' : $order->shipping_total;

		return sprintf('UTM:T|%s|%s|%s|%s|%s|%s|%s|%s',
			str_replace('|', '_', $order->id),
			str_replace('|', '_', $this->affiliation),
			str_replace('|', '_', $order->total),
			str_replace('|', '_', $tax_total),
			str_replace('|', '_', $shipping_total),
			str_replace('|', '_', $billing_address->city),
			str_replace('|', '_', $provstate_title),
			str_replace('|', '_', $billing_address->country->title));
	}

	// }}}
	// {{{ protected function getOrderItem()

	protected function getOrderItem(StoreOrder $order, StoreOrderItem $item)
	{
		return sprintf('UTM:I|%s|%s|%s|%s|%s|%s',
			str_replace('|', '_', $order->id),
			str_replace('|', '_', $item->sku),
			str_replace('|', '_', $item->product_title),
			str_replace('|', '_', $item->getSourceCategoryTitle()),
			str_replace('|', '_', $item->price),
			str_replace('|', '_', $item->quantity));
	}

	// }}}
}

?>
