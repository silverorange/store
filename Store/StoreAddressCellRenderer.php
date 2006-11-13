<?php

require_once 'Store/dataobjects/StoreAddress.php';
require_once 'Swat/SwatCellRenderer.php';

/**
 * A cell renderer for rendering address objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddressCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * The store address object to render
	 *
	 * @var StoreAddress
	 */
	public $address;

	// }}}
	// {{{ public function render

	/**
	 * Renders an address
	 */
	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->address instanceof StoreAddress)
			$this->address->displayCondensed();
	}

	// }}}
}

?>
