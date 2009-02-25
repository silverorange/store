<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Processes the order payment response
 *
 * This page serves as the landing page for processing authorized payments.
 * This page is responsible for:
 *
 *  1. updating the order object with information returned by the payment
 *     provider,
 *  2. updating the checkout progress appropriately, and
 *  3. relocating to the appropriate checkout page.
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutPaymentProcessPage extends StoreCheckoutPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		// this page does not have a ui
		return null;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		try {
			$this->processPayment();
			$this->updateProgress();
		} catch (Exception $e) {
			$this->logException($e);
			$this->handleException($e);
		}

		$this->relocate();
	}

	// }}}
	// {{{ abstract protected function processPayment()

	abstract protected function processPayment();

	// }}}
	// {{{ abstract protected function relocate()

	abstract protected function relocate();

	// }}}
	// {{{ protected function logException()

	protected function logException(Exception $e)
	{
		if (!($e instanceof SwatException)) {
			$e = new SwatException($e);
		}

		$e->process(false);
	}

	// }}}
	// {{{ protected funciton handleException()

	protected function handleException(Exception $e)
	{
	}

	// }}}
	// {{{ protected function updateProgress()

	protected function updateProgress()
	{
		if (!isset($this->app->session->checkout_progress))
			$this->app->session->checkout_progress = new ArrayObject();

		$this->app->session->checkout_progress[] = 'checkout/first';
	}

	// }}}
}

?>
