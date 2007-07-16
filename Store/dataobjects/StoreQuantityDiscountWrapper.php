<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreQuantityDiscount.php';

/**
 * A recordset wrapper class for StoreQuantityDiscount objects
 *
 * @package   Store 
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityDiscountWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreQuantityDiscount');

		$this->index_field = 'id';
	}

	// }}}
}

?>
