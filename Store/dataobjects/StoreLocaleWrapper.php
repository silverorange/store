<?php

/**
 * A recordset wrapper class for StoreLocale objects.
 *
 * @copyright 2006-2016 silverorange
 *
 * @see       StoreLocale
 */
class StoreLocaleWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->index_field = 'id';
        $this->row_wrapper_class = SwatDBClassMap::get(StoreLocale::class);
    }
}
