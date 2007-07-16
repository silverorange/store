<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItemGroup.php';

/**
 * A recordset wrapper class for StoreItemGroup objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreItemGroup
 */
class StoreItemGroupWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreItemGroup');

		$this->index_field = 'id';
	}

	// }}}
}

?>
