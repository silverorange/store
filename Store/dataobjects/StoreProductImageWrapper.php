<?php

/**
 * A recordset wrapper class for StoreProductImage objects.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreProductImage
 */
class StoreProductImageWrapper extends SiteImageWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get('StoreProductImage');
    }
}
