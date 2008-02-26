<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StorePriceRange.php';

/**
 * A recordset wrapper class for StorePriceRange objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @see       StorePriceRange
 */
class StorePriceRangeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StorePriceRange');
	}

	// }}}
}

?>
