<?php

require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Generates Google Analytics order transaction tracking code for an order
 *
 * @package   Store
 * @copyright 2008 silverorange
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
		$order = $this->order;
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

		ob_start();

		echo "\n\npageTracker._addTrans(\n",
			"\t", SwatString::quoteJavaScriptString($order->id), ",\n",
			"\t", SwatString::quoteJavaScriptString($this->affiliation), ",\n",
			"\t", SwatString::quoteJavaScriptString($order->total), ",\n";
			"\t", SwatString::quoteJavaScriptString($tax_total), ",\n",
			"\t", SwatString::quoteJavaScriptString($shipping_total), ",\n",
			"\t", SwatString::quoteJavaScriptString(
				$billing_address->city), ",\n",
			"\t", SwatString::quoteJavaScriptString($provstate_title), ",\n",
			"\t", SwatString::quoteJavaScriptString(
				$billing_address->country->title), "\n);\n";

		foreach ($order->items as $item) {
			echo "\npageTracker._addItem(\n",
				"\t", SwatString::quoteJavaScriptString($order->id), ",\n",
				"\t", SwatString::quoteJavaScriptString($item->sku), ",\n",
				"\t", SwatString::quoteJavaScriptString(
					$item->product_title), ",\n",
				"\t", SwatString::quoteJavaScriptString(
					SwatString::condense($item->getDescription())), ",\n",
				"\t", SwatString::quoteJavaScriptString($item->price), ",\n",
				"\t", SwatString::quoteJavaScriptString($item->quantity), "\n",
				");\n";
		}

		echo "\n\npageTracker._trackTrans();";

		return ob_get_clean();
	}
}

?>
