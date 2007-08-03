<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreInvoice.php';

/**
 * A recordset wrapper class for StoreInvoice objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @see       StoreInvoice
 */
class StoreInvoiceWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreInvoice');

		$this->index_field = 'id';
	}

	// }}}
}

?>
