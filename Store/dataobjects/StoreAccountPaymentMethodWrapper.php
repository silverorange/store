<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';

/**
 * A recordset wrapper class for StoreAccountPaymentMethod objects
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountAddress
 */
class StoreAccountPaymentMethodWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class =
			SwatDBClassMap::get('StoreAccountPaymentMethod');
	}

	// }}}
}

?>
