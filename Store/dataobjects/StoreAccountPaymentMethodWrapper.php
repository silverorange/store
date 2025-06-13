<?php

/**
 * A recordset wrapper class for StoreAccountPaymentMethod objects.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreAccountAccountPaymentMethod
 */
class StoreAccountPaymentMethodWrapper extends StorePaymentMethodWrapper
{
    protected function init()
    {
        parent::init();
        $this->index_field = 'id';
        $this->row_wrapper_class =
            SwatDBClassMap::get('StoreAccountPaymentMethod');
    }
}
