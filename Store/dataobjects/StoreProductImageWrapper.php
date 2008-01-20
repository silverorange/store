<?php

require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'Store/dataobjects/StoreProductImage.php';

/**
 * A recordset wrapper class for StoreProductImage objects
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @see       StoreProductImage
 */
class StoreProductImageWrapper extends SiteImageWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreProductImage');
	}

	// }}}
}

?>
