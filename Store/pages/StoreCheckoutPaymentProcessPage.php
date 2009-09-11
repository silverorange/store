<?php

require_once 'Store/pages/StoreCheckoutPage.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethodWrapper.php';

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
			if (!$this->app->config->store->multiple_payment_support) {
				$class_name = SwatDBClassMap::get('StoreOrderPaymentMethodWrapper');
				$this->app->session->order->payment_methods = new $class_name();
			}

			if ($this->processPayment()) {
				$this->updateProgress();
			} else {
				$this->cancelPayment();
			}
		} catch (Exception $e) {

			$this->cancelPayment();

			if ($this->handleException($e)) {
				// log the exception
				if (!($e instanceof SwatException)) {
					$e = new SwatException($e);
				}
				$e->process(false);
			} else {
				// exception was not handled, rethrow
				throw $e;
			}
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
	// {{{ protected funciton handleException()

	protected function handleException(Exception $e)
	{
		return false;
	}

	// }}}
	// {{{ protected function updateProgress()

	protected function updateProgress()
	{
		$this->app->checkout->setProgress('checkout/first');
	}

	// }}}
	// {{{ protected function cancelPayment()

	protected function cancelPayment()
	{
	}

	// }}}
}

?>
