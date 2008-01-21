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

	public function __construct(StoreOrder $order)
	{
		$this->order = $order;
	}

	public function getInlineJavaScript()
	{
		$order = $this->order;
		$billing_address = $order->billing_address;

		ob_start();

		echo "pageTracker._addTrans(\n",
			"\t", SwatString::quoteJavaScriptString($order->id), "\n",
			"\t", SwatString::quoteJavaScriptString($order->id), "\n", // TODO
			"\t", SwatString::quoteJavaScriptString($order->total), "\n",
			"\t", SwatString::quoteJavaScriptString($order->tax_total), "\n",
			"\t", SwatString::quoteJavaScriptString(
				$order->shipping_total), "\n",
			"\t", SwatString::quoteJavaScriptString(
				$billing_address->city), "\n";

		if ($billing_address->provstate === null)
			echo "\t", SwatString::quoteJavaScriptString(
				$billing_address->provstate_other), "\n";
		else
			echo "\t", SwatString::quoteJavaScriptString(
				$billing_address->provstate->title), "\n";

		echo "\t", SwatString::quoteJavaScriptString(
			$billing_address->country->title), "\n);\n";

		foreach ($order->items as $item) {
			echo "\npageTracker._addItem(\n",
				"\t", SwatString::quoteJavaScriptString($order->id), "\n",
				"\t", SwatString::quoteJavaScriptString($item->sku), "\n",
				"\t", SwatString::quoteJavaScriptString(
					$item->product_title), "\n",
				"\t", SwatString::quoteJavaScriptString(
					$item->product_title), "\n", // TODO
				"\t", SwatString::quoteJavaScriptString($item->price), "\n",
				"\t", SwatString::quoteJavaScriptString($item->quantity), "\n",
				");\n";
		}

		echo "\n\npageTracker._trackTrans();";

		return ob_get_clean();
	}
}

?>
