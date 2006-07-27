<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Renders item prices. Outputs "Free" if value is 0.
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class ItemPriceCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->value > 0)
			parent::render();
		else
			echo 'Free!';

	}

	// }}}
	// {{{ public function getTdAttributes()

	public function getTdAttributes()
	{
		if ($this->value > 0)
			return array('class' => 'veseys-free swat-money-cell-renderer');
		else
			return array('class' => 'swat-money-cell-renderer');
	}

	// }}}
}

?>
