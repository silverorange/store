<?php

/**
 * A recordset wrapper class for StoreCartEntry objects.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartEntryWrapper extends SwatDBRecordsetWrapper
{
    // {{{ protected function init()

    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get('StoreCartEntry');

        $this->index_field = 'id';
    }

    // }}}
}
