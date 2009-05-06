<?php

require_once 'Site/pages/SiteUiPage.php';
require_once 'Store/StorePaymentRequest.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Base class for checkout pages
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutPage extends SiteUiPage
{
	// {{{ protected properties

	protected $base_ui_xml = 'Store/pages/checkout.xml';

	// }}}
	// {{{ public function setUI()

	public function setUI($ui = null)
	{
		$this->ui = $ui;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		if (!$this->app->session->isActive())
			$this->app->relocate('cart');

		if (!$this->checkCart())
			$this->app->relocate('cart');

		$this->app->session->activate();

		// initialize session variable to track checkout progress
		if (!isset($this->app->session->checkout_progress))
			$this->resetProgress();

		if ($this->app->session->isLoggedIn())
			$this->app->session->checkout_with_account = true;

		$this->initDataObjects();
		$this->checkProgress();

		$this->loadUI();
		$this->initInternal();
		$this->ui->init();
	}

	// }}}
	// {{{ protected function loadUI()

	protected function loadUI()
	{
		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->base_ui_xml);

		/**
		 * only load the page's xml if it actually exists. This allows
		 * subclasses to use StoreCheckoutPage, but not define any extra xml
		 * (for example: a payment processing landing page that executes some
		 * code and then relocates).
		 */
		$form = $this->ui->getWidget('form');
		$xml  = $this->getUiXml();
		if ($xml !== null)
			$this->ui->loadFromXML($xml, $form);
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$form = $this->ui->getWidget('form');
		$form->action = $this->source;
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array();
	}

	// }}}
	// {{{ protected function initDataObjects()

	protected function initDataObjects()
	{
		$this->app->checkout->initDataObjects();
	}

	// }}}
	// {{{ protected function checkCart()

	protected function checkCart()
	{
		// cart doesn't matter if we have an invoice
		if (isset($this->app->session->order) &&
			$this->app->session->order->isFromInvoice())
			return true;

		// no cart, no checkout
		if (count($this->app->cart->checkout->getAvailableEntries()) <= 0)
			return false;

		return true;
	}

	// }}}
	// {{{ protected function checkProgress()

	/**
	 * Enforces dependencies for progressing through the checkout
	 *
	 * If a dependency is not met for this page, the user is redicted to the
	 * unmet dependency page.
	 */
	protected function checkProgress()
	{
		foreach ($this->getProgressDependencies() as $dependency) {
			if (!$this->app->checkout->hasProgressDependency($dependency)) {
				$this->app->relocate($dependency);
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function updateProgress()

	protected function updateProgress()
	{
		$this->app->checkout->addProgress($this->getSource());
	}

	// }}}
	// {{{ protected function resetProgress()

	protected function resetProgress()
	{
		$this->app->checkout->resetProgress();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->app->session->order->isFromInvoice()) {
			$entry = $this->layout->navbar->getEntryByPosition(1);
			$entry->link = sprintf('checkout/invoice%s',
				$this->app->session->order->invoice->id);
		}
	}

	// }}}
}

?>
