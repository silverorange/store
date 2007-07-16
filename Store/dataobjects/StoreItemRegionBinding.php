<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * Dataobject for item region bindings
 *
 * @package   Store
 * @copyright silverorange 2006
 */
class StoreItemRegionBinding extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Price of the item
	 *
	 * @var float
	 */
	public $price;

	/**
	 * If the item should be available
	 *
	 * @var boolean
	 */
	public $enabled;


	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('region',
			SwatDBClassMap::get('StoreRegion'));

		$this->registerInternalProperty('item',
			SwatDBClassMap::get('StoreItem'));

		$this->table = 'ItemRegionBinding';
	}

	// }}}
}

?>
