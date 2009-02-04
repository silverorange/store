<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StorePaymentMethodTransaction.php';

/**
 * A recordset wrapper class for StorePaymentMethodTransaction objects
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentMethodTransaction
 */
class StorePaymentMethodTransactionWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('StorePaymentMethodTransaction');

		$this->index_field = 'id';
	}

	// }}}
}

?>
