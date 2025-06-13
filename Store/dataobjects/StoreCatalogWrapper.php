<?php

/**
 * A recordset wrapper class for StoreCatalog objects.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreCatalog
 */
class StoreCatalogWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->index_field = 'id';
        $this->row_wrapper_class = SwatDBClassMap::get('StoreCatalog');
    }
}
