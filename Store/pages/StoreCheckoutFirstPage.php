<?php

require_once 'Swat/SwatYUI.php';
require_once 'Store/pages/StoreCheckoutAggregateStepPage.php';
require_once 'Store/pages/StoreCheckoutBasicInfoPage.php';
require_once 'Store/pages/StoreCheckoutBillingAddressPage.php';
require_once 'Store/pages/StoreCheckoutShippingAddressPage.php';
require_once 'Store/pages/StoreCheckoutPaymentMethodPage.php';
require_once 'Store/pages/StoreCheckoutShippingTypePage.php';

/**
 * First step of checkout
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutFirstPage extends StoreCheckoutAggregateStepPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-first.xml';
	}

	// }}}
	// {{{ protected function instantiateEmbeddedEditPages()

	protected function instantiateEmbeddedEditPages()
	{
		$page = new SitePage($this->app, $this->layout);

		$pages = array(
			new StoreCheckoutBasicInfoPage($page),
			new StoreCheckoutBillingAddressPage($page),
			new StoreCheckoutShippingAddressPage($page),
			new StoreCheckoutPaymentMethodPage($page),
			new StoreCheckoutShippingTypePage($page),
		);

		return $pages;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// passwords only required on checkout/first
		if ($this->ui->hasWidget('password')) {
			$this->ui->getWidget('password')->required = true;
		}

		if ($this->ui->hasWidget('confirm_password')) {
			$this->ui->getWidget('confirm_password')->required = true;
		}

		if ($this->ui->hasWidget('payment_amount_field')) {
			$this->ui->getWidget('payment_amount_field')->visible = false;
		}
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if (property_exists($this->layout, 'navbar')) {
			$this->layout->data->title = Store::_('Checkout');
			$this->layout->navbar->popEntry();
		}

		if ($this->app->session->checkout_with_account) {
			$this->layout->startCapture('content');
			Swat::displayInlineJavaScript($this->getInlineJavaScript());
			$this->layout->endCapture();
		}
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildBillingAndShippingAddressUi();
		$this->buildShippingandPaymentUi();

		// note in XML only applies when editing basic info off confirmation
		$this->ui->getWidget('confirm_password_field')->note = null;
	}

	// }}}
	// {{{ protected function buildBillingAndShippingAddressUi()

	protected function buildBillingAndShippingAddressUi()
	{
		// if there are no saved addresses, add a side-by-side class to the
		// frame, if there are saved addresses, add a stacked class to the
		// frame

		$ui = $this->ui;

		if (!$ui->hasWidget('billing_address_list') ||
			!$ui->hasWidget('billing_address_container') ||
			!$ui->hasWidget('shipping_address_container')) {
			return;
		}

		$address_list       = $ui->getWidget('billing_address_list');
		$billing_container  = $ui->getWidget('billing_address_container');
		$shipping_container = $ui->getWidget('shipping_address_container');

		if (!$address_list->visible) {
			$billing_container->classes[]  = 'checkout-column-left';
			$shipping_container->classes[] = 'checkout-column-right';
		} else {
			$billing_container->classes[]  = 'checkout-no-column';
			$shipping_container->classes[] = 'checkout-no-column';
		}
	}

	// }}}
	// {{{ protected function buildShippingAndPaymentUi()

	protected function buildShippingAndPaymentUi()
	{
		/*
		 * if there are no saved payment methods, add a side-by-side class
		 * if there are saved payment methods, add a stacked class
		 *
		 * if payment methods are side-by-side, and there is a shipping-type
		 * container, don't put the shipping type in a right-column
		 */

		$ui = $this->ui;

		if (!$ui->hasWidget('payment_method_list') ||
			!$ui->hasWidget('payment_method_container')) {
			return;
		}

		$payment_method_list      = $ui->getWidget('payment_method_list');
		$payment_method_container = $ui->getWidget('payment_method_container');

		if ($ui->hasWidget('shipping_type_container')) {
			$shipping_type_container =
				$ui->getWidget('shipping_type_container');
		} else {
			$shipping_type_container = null;
		}

		if (!$payment_method_list->visible) {
			$payment_method_container->classes[] = 'checkout-column-left';
			if ($shipping_type_container instanceof SwatWidget) {
				$shipping_type_container->classes[]  = 'checkout-column-right';
			}
		} else {
			$payment_method_container->classes[] = 'checkout-no-column';
			if ($shipping_type_container instanceof SwatWidget) {
				$shipping_type_container->classes[]  = 'checkout-no-column';
			}
		}
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$id = 'checkout_first_page';
		return sprintf("var %s_obj = new StoreCheckoutFirstPage();",
			$id);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-first-page.css',
			Store::PACKAGE_ID));

		if ($this->app->session->checkout_with_account) {
			$yui = new SwatYUI(array('event'));
			$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/store/javascript/store-checkout-first-page.js',
				Store::PACKAGE_ID));
		}
	}

	// }}}
}

?>
