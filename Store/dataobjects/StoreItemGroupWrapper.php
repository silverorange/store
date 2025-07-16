<?php

/**
 * A recordset wrapper class for StoreItemGroup objects.
 *
 * @copyright 2006-2016 silverorange
 *
 * @see       StoreItemGroup
 */
class StoreItemGroupWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(StoreItemGroup::class);

        $this->index_field = 'id';
    }
}
