<?php

require_once 'Store/StoreTotalRow.php';

/**
 * Displays totals in a special row at the bottom of a table view.
 *
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreInvoiceTotalRow extends StoreTotalRow
{
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->class = 'store-total-row';
		$tr_tag->id = $this->id;

		$tr_tag->open();

		$this->displayHeader();
		$this->displayTotal();
		$this->displayBlank();

		$tr_tag->close();
	}

	// }}}
	// {{{ protected function displayTotal()

	protected function displayTotal()
	{
		$td_tag = new SwatHtmlTag('td');
		$td_tag->class = $this->getCSSClassString();
		$td_tag->open();

		if ($this->locale !== null)
			$this->money_cell_renderer->locale = $this->locale;

		if ($this->value === null) {
			echo Store::_('TBD');
		} else {
			$this->money_cell_renderer->value = $this->value;
			$this->money_cell_renderer->render();
		}

		$td_tag->close();
	}

	// }}}
}

?>
