<?php

require_once 'Store/pages/StoreCheckoutAddressVerificationPage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Shipping address verification page of checkout
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutShippingAddressVerificationPage extends
	StoreCheckoutAddressVerificationPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-shipping-address-verification.xml';
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
