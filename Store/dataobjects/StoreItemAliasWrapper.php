<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItemAlias.php';

/**
 * A recordset wrapper class for ItemAlias objects
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemAlias
 */
class StoreItemAliasWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreItemAlias');

		$this->index_field = 'id';
	}

	// }}}
}

?>
