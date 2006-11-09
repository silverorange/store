<?php

require_once 'Swat/SwatTableView.php';

/**
 * Custom table-view for quantity discounts that shows a "base" row at the top
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemQuantityDiscountTableView extends SwatTableView
{
	private $item_row;

	protected function displayHeader()
	{
		echo '<thead>';
		echo '<tr>';

		foreach ($this->getColumns() as $column)
			$column->displayHeaderCell();

		echo '</tr>';

		$this->displayBaseRow();

		echo '</thead>';
	}

	private function displayBaseRow()
	{
		if ($this->item_row === null)
			return;

		echo '<tr>';

		foreach ($this->getColumns() as $column) {
			if ($column->id == 'checkbox') {
				$td_tag = new SwatHtmlTag('td', $column->getTdAttributes());
				$td_tag->open();

				$strong_tag = new SwatHtmlTag('strong');
				$strong_tag->setContent(Store::_('Base:'));
				$strong_tag->display();

				$td_tag->close();
			} else {
				$column->display($this->item_row);
			}
		}

		echo '</tr>';
	}

	public function setItemRow($item_row)
	{
		$this->item_row = $item_row;
	}
}

?>
