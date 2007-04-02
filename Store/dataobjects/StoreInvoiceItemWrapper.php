<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreInvoiceItem.php';

/**
 * A recordset wrapper class for StoreInvoiceItem objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @see       StoreInvoiceItem
 */
class StoreInvoiceItemWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreInvoiceItem');
	}

	// }}}
}

?>
