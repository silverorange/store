<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCategoryImage.php';

/**
 * A recordset wrapper class for StoreCategoryImage objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreCategoryImage
 */
class StoreCategoryImageWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreCategoryImage');
		$this->index_field = 'id';
	}

	// }}}
}

?>
