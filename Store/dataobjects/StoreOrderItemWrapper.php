<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreOrderItem.php';

/**
 * A recordset wrapper class for StoreOrderItem objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreOrderItem
 */
class StoreOrderItemWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class = SwatDBClassMap::get('StoreOrderItem');
	}

	// }}}
}

?>
