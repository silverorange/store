<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StorePaymentTransaction.php';

/**
 * A recordset wrapper class for StorePaymentTransaction objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentTransaction
 */
class StorePaymentTransactionWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('StorePaymentTransaction');

		$this->index_field = 'id';
	}

	// }}}
}

?>
