<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StorePaymentTransaction.php';

/**
 * A recordset wrapper class for StorePaymentTransaction objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentTransaction
 */
class StorePaymentTransactionWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			$this->class_map->resolveClass('StorePaymentTransaction');

		$this->index_field = 'id';
	}

	// }}}
}

?>
