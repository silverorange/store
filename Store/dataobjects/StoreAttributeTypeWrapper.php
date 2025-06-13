<?php

/**
 * A recordset wrapper class for StoreAttributeType objects.
 *
 * @copyright 2008-2016 silverorange
 *
 * @see       StoreAttributeType
 */
class StoreAttributeTypeWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get('StoreAttributeType');
    }
}
