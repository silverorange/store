<?php

require_once 'Swat/SwatTextCellRenderer.php';

/** 
 * Cell renderer for cart entry description column in the cart
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartEntryDescriptionCellRenderer extends SwatTextCellRenderer
{
	// {{{ public function getCSSClassNames()

	protected function getCSSClassNames()
	{
		$classes = parent::getCSSClassNames();
		$classes[] = 'store-cart-entry-description';

		return $classes;
	}

	// }}}
}

?>
