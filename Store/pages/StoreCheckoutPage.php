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

		// enforce dependencies for progressing through the checkout
		foreach ($this->getProgressDependencies() as $dependency) {
			if (!in_array($dependency,
				$this->app->session->checkout_progress->getArrayCopy())) {
				$this->app->relocate($dependency);
			}
		}

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

		$form = $this->ui->getWidget('form');
		$this->ui->loadFromXML($this->getUiXml(), $form);
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
		// Clear existing transaction from session unless it is a 3-D Secure
		// transaction
		if (isset($this->app->session->transaction) &&
			$this->app->session->transaction->request_type !=
			StorePaymentRequest::TYPE_3DS_AUTH) {

			unset($this->app->session->transaction);
		}

		if (!isset($this->app->session->account)) {
			unset($this->app->session->account);
			$account_class = SwatDBClassMap::get('StoreAccount');
			$this->app->session->account = new $account_class();
			$this->app->session->account->setDatabase($this->app->db);
			$this->resetProgress();
		}

		// Clear placed order from the session if there is no 3-D Secure
		// transaction in progress.
		if (!isset($this->app->session->order) ||
			($this->app->session->order->id !== null &&
			!isset($this->app->session->transaction))) {

			unset($this->app->session->order);
			$order_class = SwatDBClassMap::get('StoreOrder');
			$this->app->session->order = new $order_class();
			$this->app->session->order->setDatabase($this->app->db);
			$this->resetProgress();
		}
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

	// process phase
	// {{{ protected function updateProgress()

	protected function updateProgress()
	{
		if (!isset($this->app->session->checkout_progress)) {
			$this->app->session->checkout_progress = new ArrayObject();
		}

		$this->app->session->checkout_progress[] = $this->getSource();
	}

	// }}}
	// {{{ protected function resetProgress()

	protected function resetProgress()
	{
		$this->app->session->checkout_progress = new ArrayObject();
		$this->app->session->checkout_with_account = false;
		$this->app->session->checkout_email = null;
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
