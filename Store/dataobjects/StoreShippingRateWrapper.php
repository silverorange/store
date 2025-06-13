<?php

/**
 * A recordset wrapper class for StoreShippingRate objects.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreShippingRate
 */
class StoreShippingRateWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get('StoreShippingRate');
    }
}
