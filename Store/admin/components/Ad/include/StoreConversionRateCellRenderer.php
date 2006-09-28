<?php

require_once 'Swat/SwatPercentageCellRenderer.php';

/**
 * Displays conversion rates that are NaN as a none-styled dash
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreConversionRateCellRenderer extends SwatPercentageCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->value === null) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'swat-none';
			$div_tag->style = 'text-align: center;';
			$div_tag->setContent('—');
			$div_tag->display();
		} else {
			parent::render();
		}
	}

	// }}}
}

?>
