<?php

/**
 * A recordset wrapper class for StorePriceRange objects.
 *
 * @copyright 2007-2016 silverorange
 *
 * @see       StorePriceRange
 */
class StorePriceRangeWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get('StorePriceRange');
    }
}
