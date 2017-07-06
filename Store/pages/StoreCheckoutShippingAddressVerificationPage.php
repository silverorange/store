<?php

/**
 * Shipping address verification page of checkout
 *
 * @package   Store
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutShippingAddressVerificationPage extends
	StoreCheckoutAddressVerificationPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/checkout-shipping-address-verification.xml';
	}

	// }}}
	// {{{ protected function getWidgetPrefix()

	protected function getWidgetPrefix()
	{
		return 'shipping_';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		if ($this->app->session->order->shipping_address !==
			$this->app->session->order->billing_address)
				$this->address = $this->app->session->order->shipping_address;
	}

	// }}}
}

?>
