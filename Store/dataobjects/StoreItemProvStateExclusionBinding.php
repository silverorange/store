<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreProvState.php';

/**
 * Dataobject for item provstate exclusion bindings
 *
 * @package   Store
 * @copyright 2012-2015 silverorange
 */
class StoreItemProvStateExclusionBinding extends SwatDBDataObject
{
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('provstate',
			SwatDBClassMap::get('StoreProvState'));

		$this->registerInternalProperty('item',
			SwatDBClassMap::get('StoreItem'));

		$this->table = 'ItemProvStateExclusionBinding';
	}

	// }}}
}

?>
