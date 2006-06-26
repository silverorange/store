<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreAccount.php';

/**
 * A recordset wrapper class for StoreAccount objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreAccountWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreAccount');

		$this->index_field = 'id';
	}

	// }}}
}

?>
