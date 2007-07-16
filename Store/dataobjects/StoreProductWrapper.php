<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreProductWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreProduct');
		$this->index_field = 'id';
	}

	// }}}
}

?>
