<?php

require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'Store/dataobjects/StoreCategoryImage.php';

/**
 * A recordset wrapper class for StoreCategoryImage objects
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @see       StoreCategoryImage
 */
class StoreCategoryImageWrapper extends SiteImageWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreCategoryImage');
	}

	// }}}
}

?>
