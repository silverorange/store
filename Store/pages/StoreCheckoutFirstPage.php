<?php

require_once 'Store/pages/StoreCheckoutStepPage.php';
require_once 'Store/pages/StoreCheckoutBasicInfoPage.php';
require_once 'Store/pages/StoreCheckoutBillingAddressPage.php';
require_once 'Store/pages/StoreCheckoutShippingAddressPage.php';
require_once 'Store/pages/StoreCheckoutPaymentMethodPage.php';

/**
 * First step of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCheckoutFirstPage extends StoreCheckoutStepPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = dirname(__FILE__).'/checkout-first.xml';

		foreach ($this->instantiateEmbeddedEditPages() as $page)
			$this->registerEmbeddedEditPage($page);
	}

	// }}}
	// {{{ protected function instantiateEmbeddedEditPages()

	protected function instantiateEmbeddedEditPages()
	{
		$pages = array(
			new StoreCheckoutBasicInfoPage($this->app, $this->layout),
			new StoreCheckoutBillingAddressPage($this->app, $this->layout),
			new StoreCheckoutShippingAddressPage($this->app, $this->layout),
			new StoreCheckoutPaymentMethodPage($this->app, $this->layout),
		);

		return $pages;
	}

	// }}}

	// init phase
	// {{{ protected function loadUI()

	protected function loadUI()
	{
		parent::loadUI();

		$pages = $this->getEmbeddedEditPages();
		foreach ($pages as $page) {
			$container = $this->getContainer($page);
			$this->ui->loadFromXML($page->getXml(), $container);
		}
	}

	// }}}
	// {{{ private function getContainer()

	private function getContainer($page)
	{
		$class = get_class($page);

		if (preg_match('/Checkout(.*)Page$/', $class, $matches)) {
			$container_id = preg_replace('/([A-Z])/e',
				"'_'.strtolower('\\1')", $matches[1]).'_container';

			$container_id = substr($container_id, 1);
		} else {
			throw new StoreException(
				"Unable to guess container for page $class");
		}

		$container = $this->ui->getWidget($container_id);
		return $container;
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// passwords only required on checkout/first
		$this->ui->getWidget('password')->required = true;
		$this->ui->getWidget('confirm_password')->required = true;
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout');
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('form');
		if ($form->isProcessed()) {
			if ($form->hasMessage()) {
				$msg = new SwatMessage(Store::_('There is a problem with the '.
					'information submitted.'), SwatMessage::ERROR);

				$msg->secondary_content = Store::_('Please address the fields '.
					'highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($msg);
			} else {
				$this->updateProgress();
				$this->app->relocate('checkout/confirmation');
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->data->title = Store::_('Checkout');
		$this->layout->navbar->popEntry();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-first-page.css',
			Store::PACKAGE_ID));


		if ($this->app->session->checkout_with_account) {
			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/store/javascript/store-checkout-first-page.js',
				Store::PACKAGE_ID));

			$this->layout->startCapture('content');
			$this->displayJavaScript();
			$this->layout->endCapture();
		}
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		// if there are no saved addresses, add a side-by-side class to the frame
		// if there are saved addresses, add a stacked class to the frame
		$address_list = $this->ui->getWidget('billing_address_list');
		$billing_container = $this->ui->getWidget('billing_address_container');
		$shipping_container = $this->ui->getWidget('shipping_address_container');

		if (!$address_list->visible) {
			$billing_container->classes[]  = 'checkout-column-left';
			$shipping_container->classes[] = 'checkout-column-right';
		} else {
			$billing_container->classes[]  = 'checkout-no-column';
			$shipping_container->classes[] = 'checkout-no-column';
		}

		// if there are no saved payment methods, add a side-by-side class
		// if there are saved payment methods, add a stacked class
		$payment_method_list = $this->ui->getWidget('payment_method_list');
		$payment_method_container = 
			$this->ui->getWidget('payment_method_container');

		if (!$payment_method_list->visible)
			$payment_method_container->classes[]  = 'checkout-column-left';
		else
			$payment_method_container->classes[]  = 'checkout-no-column';

		// note in XML only applies when editing basic info off confirmation
		$this->ui->getWidget('confirm_password_field')->note = null;
	}

	// }}}
	// {{{ protected function displayJavaScript()

	protected function displayJavaScript()
	{
		echo '<script type="text/javascript">', "\n";

		printf("var checkout_first_page = ".
			"new StoreCheckoutFirstPage('%s', '%s', '%s');\n",
			$this->ui->getWidget('fullname')->id,
			$this->ui->getWidget('billing_address_fullname')->id,
			$this->ui->getWidget('credit_card_fullname')->id);

		echo '</script>';
	}

	// }}}
}

?>
