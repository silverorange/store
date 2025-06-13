<?php

/**
 * A recordset wrapper class for StorePaymentMethodTransaction objects.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentMethodTransaction
 */
class StorePaymentMethodTransactionWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->row_wrapper_class =
            SwatDBClassMap::get('StorePaymentMethodTransaction');

        $this->index_field = 'id';
    }
}
