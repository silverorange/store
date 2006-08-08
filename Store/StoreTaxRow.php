<?php

require_once 'Store/StoreTotalRow.php';

/**
 * Displays taxes in a special row at the bottom of a table view.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreTaxRow extends StoreTotalRow
{
	// {{{ public function display()

	public function display()
	{
		// taxes are never free
		if ($this->value === null || $this->value <= 0)
			$this->visible = false;

		parent::display();
	}

	// }}}
}

?>
