<?php

/**
 * A recordset wrapper class for StoreOrderAddress objects.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreOrderAddress
 */
class StoreOrderAddressWrapper extends SwatDBRecordsetWrapper
{
    // {{{ protected function init()

    protected function init()
    {
        parent::init();
        $this->index_field = 'id';
        $this->row_wrapper_class = SwatDBClassMap::get('StoreOrderAddress');
    }

    // }}}
}
