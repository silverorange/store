<?php

/**
 * A payment method for an account for an e-commerce web application.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentMethod
 *
 * @property StoreAccount $account
 */
class StoreAccountPaymentMethod extends StorePaymentMethod
{
    protected function init()
    {
        parent::init();
        $this->table = 'AccountPaymentMethod';
        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(StoreAccount::class)
        );
    }
}
