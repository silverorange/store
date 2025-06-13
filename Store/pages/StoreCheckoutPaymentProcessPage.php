<?php

/**
 * Processes the order payment response.
 *
 * This page serves as the landing page for processing authorized payments.
 * This page is responsible for:
 *
 *  1. updating the order object with information returned by the payment
 *     provider,
 *  2. updating the checkout progress appropriately, and
 *  3. relocating to the appropriate checkout page.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutPaymentProcessPage extends StoreCheckoutPage
{
    public function getUiXml()
    {
        // this page does not have a ui
        return null;
    }

    // process phase

    public function process()
    {
        try {
            $this->clearPaymentMethods();

            if ($this->processPayment()) {
                $this->updateProgress();
            } else {
                $this->cancelPayment();
            }
        } catch (Throwable $e) {
            $this->cancelPayment();

            if ($this->handleException($e)) {
                // log the exception
                if (!$e instanceof SwatException) {
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

    abstract protected function processPayment();

    abstract protected function relocate();

    protected function clearPaymentMethods()
    {
        if (!$this->app->config->store->multiple_payment_support) {
            $class_name = SwatDBClassMap::get('StoreOrderPaymentMethodWrapper');
            $this->app->session->order->payment_methods = new $class_name();
        }
    }

    protected function handleException(Throwable $e)
    {
        return false;
    }

    protected function updateProgress()
    {
        $this->app->checkout->setProgress($this->getCheckoutSource() . '/first');
    }

    protected function cancelPayment() {}
}
