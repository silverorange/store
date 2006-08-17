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
class StorePriceCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->value === null)
			return;

		if ($this->isFree())
			echo Store::_('Free!');
		else
			parent::render();

	}

	// }}}
	// {{{ public function getDataSpecificCSSClassNames()

	public function getDataSpecificCSSClassNames()
	{
		$classes = array();

		if ($this->isFree())
			$classes[] = 'store-free';

		return $classes;
	}

	// }}}
	// {{{ protected function isFree()

	protected function isFree()
	{
		return ($this->value <= 0);
	}

	// }}}
}

?>
