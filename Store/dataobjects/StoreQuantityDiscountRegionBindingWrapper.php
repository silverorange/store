<?php

/**
 * A recordset wrapper class for QuantityDiscountRegionBinding objects.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityDiscountRegionBindingWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class =
            SwatDBClassMap::get(StoreQuantityDiscountRegionBinding::class);
    }
}
