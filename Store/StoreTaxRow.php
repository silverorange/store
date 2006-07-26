<?php

require_once 'Swat/SwatTableViewRow.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Displays taxes in a special row at the bottom of a table view.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreTaxRow extends SwatTableViewRow
{
	// {{{ public properties

	public $title;
	public $value;
	public $offset = 0;

	// }}}
	// {{{ public function display()

	public function display()
	{
		// taxes are never free
		if ($this->value === null || $this->value <= 0)
			$this->visible = false;

		if (!$this->visible)
			return;

		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->class = 'store-total-row';
		$tr_tag->id = $this->id;

		$column_count = $this->view->getVisibleColumnCount();
		$th_tag = new SwatHtmlTag('th');
		$th_tag->colspan = $column_count - 1 - $this->offset;
		$th_tag->setContent($this->title.':');

		$tr_tag->open();
		$th_tag->display();

		$renderer = new SwatMoneyCellRenderer();
		$renderer->value = $this->value;

		$td_tag = new SwatHtmlTag('td', $renderer->getTdAttributes());
		$td_tag->open();
		$renderer->render();
		$td_tag->close();

		if ($this->offset > 0) {
			$td_tag = new SwatHtmlTag('td');
			$td_tag->colspan = $this->offset;
			$td_tag->setContent('&nbsp;');
			$td_tag->display();
		}

		$tr_tag->close();
	}

	// }}}
}

?>
