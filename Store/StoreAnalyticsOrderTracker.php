<?php

require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Generates Google Analytics order transaction tracking code for an order
 *
 * @package   Store
 * @copyright 2008-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://www.google.com/support/googleanalytics/bin/answer.py?answer=55528
 * @link      http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html
 */
class StoreAnalyticsOrderTracker
{
	// {{{ protected properties

	/**
	 * @var StoreOrder
	 */
	protected $order;

	protected $affiliation;

	// }}}
	// {{{ public function __construct()

	public function __construct(StoreOrder $order, $affiliation = null)
	{
		$this->order       = $order;
		$this->affiliation = $affiliation;
	}

	// }}}
	// {{{ public function getCommands()

	public function getCommands()
	{
		$commands = array($this->getOrderCommand());
		foreach ($this->order->items as $item) {
			$commands[] = $this->getOrderItemCommand($item);
		}

		$commands[] = '_trackTrans';

		return $commands;
	}

	// }}}
	// {{{ protected function getOrderCommand()

	protected function getOrderCommand()
	{
		$address         = $this->getAddress();
		$provstate_title = $this->getProvStateTitle($address);
		$order_total     = $this->getOrderTotal();

		/*
		 * Shipping and tax fields cannot be 0 according to Google Analytics
		 * support article:
		 * http://www.google.com/support/analytics/bin/answer.py?answer=72291
		 * These methods include a workaround.
		 */
		$tax_total       = $this->getTaxTotal();
		$shipping_total  = $this->getShippingTotal();

		return array(
			'_addTrans',
			$this->order->id,
			$this->affiliation,
			$order_total,
			$tax_total,
			$shipping_total,
			$address->city,
			$provstate_title,
			$address->country->title);
	}

	// }}}
	// {{{ protected function getOrderItemCommand()

	protected function getOrderItemCommand(StoreOrderItem $item)
	{
		return array(
			'_addItem',
			$this->order->id,
			$item->sku,
			$item->product_title,
			$item->getSourceCategoryTitle(),
			$item->price,
			$item->quantity);
	}
	// }}}
	// {{{ protected function getAddress()

	protected function getAddress()
	{
		return $this->order->billing_address;
	}
	// }}}
	// {{{ protected function getProvStateTitle()

	protected function getProvStateTitle(StoreOrderAddress $address)
	{
		return ($address->provstate === null) ?
			$address->provstate_other :
			$address->provstate->title;
	}
	// }}}
	// {{{ protected function getOrderTotal()

	protected function getOrderTotal()
	{
		return $this->order->total;
	}
	// }}}
	// {{{ protected function getTaxTotal()

	protected function getTaxTotal()
	{
		return ($this->order->tax_total == 0) ? '' : $this->order->tax_total;
	}
	// }}}
	// {{{ protected function getShippingTotal()

	protected function getShippingTotal()
	{
		return ($this->order->shipping_total == 0) ?
			'' : $this->order->shipping_total;
	}
	// }}}

}

?>
