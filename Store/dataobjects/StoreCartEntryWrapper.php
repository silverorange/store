<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCartEntry.php';

/**
 * A recordset wrapper class for StoreCartEntry objects
 *
 * This class contains cart functionality common to all sites. It is typically
 * extended on a per-site basis.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartEntryWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'StoreCartEntry';
	}

	// }}}
}

?>
