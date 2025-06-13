<?php

/**
 * Second step of checkout.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutAddressVerificationStepPage extends StoreCheckoutAggregateStepPage
{
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/checkout-address-verification-step.xml';
    }

    // }}}
    // {{{ protected function instantiateEmbeddedEditPages()

    protected function instantiateEmbeddedEditPages()
    {
        $page = new SitePage($this->app, $this->layout);

        return [
            new StoreCheckoutBillingAddressVerificationPage($page),
            new StoreCheckoutShippingAddressVerificationPage($page),
        ];
    }

    // }}}

    // init phase
    // {{{ protected function getProgressDependencies()

    protected function getProgressDependencies()
    {
        return [$this->getCheckoutSource() . '/first'];
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

        if (!$billing_container->visible && !$shipping_container->visible) {
            $this->relocate();
        }
    }

    // }}}
}
