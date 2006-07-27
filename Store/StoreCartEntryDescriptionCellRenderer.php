<?php

require_once 'Swat/SwatTextCellRenderer.php';

/** 
 * Cell renderer for cart entry description column in the cart
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class CartEntryDescriptionCellRenderer extends SwatTextCellRenderer
{
	// {{{ public function getCSSClassNames()

	protected function getCSSClassNames()
	{
		$classes = parent::getCSSClassNames();
		$classes[] = 'cart-entry-description';

		return $classes;
	}

	// }}}
}

?>
