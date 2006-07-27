<?php

require_once 'Swat/SwatTextCellRenderer.php';

/**
 * A cell renderer for displaying messages in the cart
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartMessageCellRenderer extends SwatTextCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		$div = new SwatHtmlTag('div');
		$div->class = 'store-cart-message';
		$div->setContent($this->text, 'text/xml');
		$div->display();
	}

	// }}}
}

?>
