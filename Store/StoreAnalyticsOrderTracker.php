<?php

require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Generates Google Analytics order transaction tracking code for an order
 *
 * @package   Store
 * @copyright 2008-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://www.google.com/support/googleanalytics/bin/answer.py?answer=55528
 * @link      http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html
 * @link      https://developers.facebook.com/docs/facebook-pixel/api-reference
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
	// {{{ public function getGoogleAnalyticsCommands()

	public function getGoogleAnalyticsCommands()
	{
		$commands = array($this->getGoogleAnalyticsOrderCommand());
		foreach ($this->order->items as $item) {
			$commands[] = $this->getGoogleAnalyticsOrderItemCommand($item);
		}

		$commands[] = '_trackTrans';

		return $commands;
	}

	// }}}
	// {{{ public function getFacebookPixelCommands()

	public function getFacebookPixelCommands()
	{
		$commands[] = array(
			'Purchase',
			sprintf(
				"{value: '%s', currency: 'USD'}",
				$this->getOrderTotal()
			)
		);

		return $commands;
	}

	// }}}
	// {{{ protected function getGoogleAnalyticsOrderCommand()

	protected function getGoogleAnalyticsOrderCommand()
	{
		$address         = $this->getAddress();
		$city            = $this->getCity($address);
		$provstate_title = $this->getProvStateTitle($address);
		$country_title   = $this->getCountryTitle($address);
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
			$city,
			$provstate_title,
			$country_title
		);
	}

	// }}}
	// {{{ protected function getAddress()

	protected function getAddress()
	{
		return $this->order->billing_address;
	}

	// }}}
	// {{{ protected function getCity()

	protected function getCity(StoreOrderAddress $address = null)
	{
		$city = '';

		if ($address instanceof StoreOrderAddress) {
			$city = $address->city;
		}

		return $city;
	}

	// }}}
	// {{{ protected function getProvStateTitle()

	protected function getProvStateTitle(StoreOrderAddress $address = null)
	{
		$title = '';

		if ($address instanceof StoreOrderAddress) {
			$title = ($address->provstate === null)
				? $address->provstate_other
				: $address->provstate->title;
		}

		return $title;
	}

	// }}}
	// {{{ protected function getCountryTitle()

	protected function getCountryTitle(StoreOrderAddress $address = null)
	{
		$title = '';

		if ($address instanceof StoreOrderAddress) {
			$title = $address->country->title;
		}

		return $title;
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
		return ($this->order->shipping_total == 0)
			? ''
			: $this->order->shipping_total;
	}

	// }}}
	// {{{ protected function getGoogleAnalyticsOrderItemCommand()

	protected function getGoogleAnalyticsOrderItemCommand(StoreOrderItem $item)
	{
		return array(
			'_addItem',
			$this->order->id,
			$this->getSku($item),
			$this->getProductTitle($item),
			$this->getCategoryTitle($item),
			$item->price,
			$item->quantity
		);
	}

	// }}}
	// {{{ protected function getSku()

	protected function getSku(StoreOrderItem $item)
	{
		return $item->sku;
	}

	// }}}
	// {{{ protected function getProductTitle()

	protected function getProductTitle(StoreOrderItem $item)
	{
		return $item->product_title;
	}

	// }}}
	// {{{ protected function getCategoryTitle()

	protected function getCategoryTitle(StoreOrderItem $item)
	{
		return $item->getSourceCategoryTitle();
	}

	// }}}
}

?>
