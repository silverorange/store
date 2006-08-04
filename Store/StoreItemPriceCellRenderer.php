<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Renders item prices
 *
 * Outputs "Free" if value is 0. When displaying free, a CSS class called
 * store-free is appended to the list of TD classes.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemPriceCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->value > 0)
			parent::render();
		else
			echo Store::_('Free!');

	}

	// }}}
	// {{{ public function getTdAttributes()

	public function getTdAttributes()
	{
		$attributes = parent::getTdAttributes();
		$attributes['class'].= ' store-free';
		return $attributes;
	}

	// }}}
}

?>
