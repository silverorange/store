<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreAttributeType.php';

/**
 * A recordset wrapper class for StoreAttributeType objects
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @see       StoreAttributeType
 */
class StoreAttributeTypeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreAttributeType');
	}

	// }}}
}

?>
