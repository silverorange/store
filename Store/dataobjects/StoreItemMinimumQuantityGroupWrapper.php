<?php

/**
 * A recordset wrapper class for StoreItemMinimumQuantityGroup objects.
 *
 * @copyright 2009-2016 silverorange
 *
 * @see       StoreItemMinimumQuantityGroup
 */
class StoreItemMinimumQuantityGroupWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class =
            SwatDBClassMap::get('StoreItemMinimumQuantityGroup');

        $this->index_field = 'id';
    }
}
