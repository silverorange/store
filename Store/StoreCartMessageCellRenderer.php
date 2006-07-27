<?php

require_once 'Swat/SwatTextCellRenderer.php';

/**
 * A cart message cell renderer
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class CartMessageCellRenderer extends SwatTextCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		$div = new SwatHtmlTag('div');
		$div->class = 'veseys-cart-message';
		$div->setContent($this->text, 'text/xml');
		$div->display();
	}

	// }}}
}

?>
