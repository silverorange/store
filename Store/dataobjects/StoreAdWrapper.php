<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreAd.php';

/**
 * A recordset wrapper class for StoreAd objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAd
 */
class StoreAdWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('StoreAd');

		$this->index_field = 'id';
	}

	// }}}
}

?>
