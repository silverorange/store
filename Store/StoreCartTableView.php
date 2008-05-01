<?php

require_once 'Swat/SwatTableView.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * A custom table view for the cart which allows for a column for product
 * images.
 *
 * @package   Store
 * @copyright 2004-2008 silverorange
 */
class StoreCartTableView extends SwatTableView
{
	// {{{ public function getVisibleColumnCount()

	public function getVisibleColumnCount()
	{
		return parent::getVisibleColumnCount() + 1;
	}

	// }}}
	// {{{ public function getXhtmlColspan()

	public function getXhtmlColspan()
	{
		return parent::getXhtmlColspan() + 1;
	}

	// }}}
	// {{{ protected function displayHeader()

	/**
	 * Displays the column headers for this table-view
	 *
	 * Each column is asked to display its own header.
	 * Rows in the header are outputted inside a <thead> HTML tag.
	 */
	protected function displayHeader()
	{
		echo '<thead>';
		echo '<tr>';

		echo '<th>&nbsp;</th>';

		foreach ($this->columns as $column)
			$column->displayHeaderCell();

		echo '</tr>';
		echo '</thead>';
	}

	// }}}
}

?>
