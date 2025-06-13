<?php

/**
 * A recordset wrapper class for StoreProvState objects.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreProvState
 */
class StoreProvStateWrapper extends SwatDBRecordsetWrapper
{
    // {{{ protected function init()

    protected function init()
    {
        parent::init();
        $this->index_field = 'id';
        $this->row_wrapper_class = SwatDBClassMap::get('StoreProvState');
    }

    // }}}
}
