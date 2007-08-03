<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreProductImage.php';

/**
 * A recordset wrapper class for StoreProductImage objects
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @see       StoreProductImage
 */
class StoreProductImageWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreProductImage');

		$this->index_field = 'id';
	}

	// }}}
}

?>
