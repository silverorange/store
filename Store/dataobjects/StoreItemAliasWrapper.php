<?php

/**
 * A recordset wrapper class for ItemAlias objects.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreItemAlias
 */
class StoreItemAliasWrapper extends SwatDBRecordsetWrapper
{
    // {{{ protected function init()

    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get('StoreItemAlias');

        $this->index_field = 'id';
    }

    // }}}
}
