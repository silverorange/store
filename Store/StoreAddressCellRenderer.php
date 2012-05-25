<?php

require_once 'Store/dataobjects/StoreAddress.php';
require_once 'Swat/SwatCellRenderer.php';

/**
 * A cell renderer for rendering address objects
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
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

	/**
	 * Whether or not to display the address in condensed format or not. If
	 * false, call the normal display.
	 *
	 * @var boolean
	 */
	public $condensed = true;

	// }}}
	// {{{ public function render()

	/**
	 * Renders an address
	 */
	public function render(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		parent::render($context);

		if ($this->address instanceof StoreAddress) {
			ob_start();
			if ($this->condensed) {
				$this->address->displayCondensed();
			} else {
				$this->address->display();
			}
			$context->out(ob_get_clean());
		}
	}

	// }}}
}

?>
