<?php

require_once 'Store/pages/StoreCheckoutAddressVerificationPage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Billing address verification page of checkout
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutBillingAddressVerificationPage extends
	StoreCheckoutAddressVerificationPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-billing-address-verification.xml';
	}

	// }}}
	// {{{ protected function getWidgetPrefix()

	protected function getWidgetPrefix()
	{
		return 'billing_';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		$this->address = $this->app->session->order->billing_address;
	}

	// }}}
}

?>
