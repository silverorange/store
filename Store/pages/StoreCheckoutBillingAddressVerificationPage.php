<?php

/**
 * Billing address verification page of checkout.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutBillingAddressVerificationPage extends StoreCheckoutAddressVerificationPage
{
    // {{{ public function getUiXml()

    public function getUiXml()
    {
        return __DIR__ . '/checkout-billing-address-verification.xml';
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
