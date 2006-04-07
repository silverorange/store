<?php

require_once 'Store/dataobjects/StoreAddress.php';
require_once 'Swat/SwatCellRenderer.php';

/**
 * A cell renderer for rendering store address objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAddressCellRenderer extends SwatCellRenderer
{
	/**
	 * The store address object to render
	 *
	 * @var StoreAddress
	 */
	public $address;

	/**
	 * Renders a store address
	 */
	public function render()
	{
		if ($this->address instanceof StoreAddress)
			$this->address->displayCondensed();
	}
}

?>
