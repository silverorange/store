<?php

require_once 'Store/pages/StoreCheckoutAggregateStepPage.php';
require_once 'Store/pages/StoreCheckoutBillingAddressVerificationPage.php';
require_once 'Store/pages/StoreCheckoutShippingAddressVerificationPage.php';

/**
 * Second step of checkout
 *
 * @package   Store
 * @copyright 2009 silverorange
 */
class StoreCheckoutAddressVerificationStepPage extends StoreCheckoutAggregateStepPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-address-verification-step.xml';
	}

	// }}}
	// {{{ protected function instantiateEmbeddedEditPages()

	protected function instantiateEmbeddedEditPages()
	{
		$page = new SitePage($this->app, $this->layout);

		$pages = array(
			new StoreCheckoutBillingAddressVerificationPage($page),
			new StoreCheckoutShippingAddressVerificationPage($page),
		);

		return $pages;
	}

	// }}}

	// init phase
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout/first');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$billing_container =
			$this->ui->getWidget('billing_address_verification_container');

		$shipping_container =
			$this->ui->getWidget('shipping_address_verification_container');

		if (!$billing_container->visible && !$shipping_container->visible)
			$this->relocate();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		/*
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/checkout-address-verification-step-page.css',
			Store::PACKAGE_ID));
		*/
	}

	// }}}
}

?>
