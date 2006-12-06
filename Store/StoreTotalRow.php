<?php

require_once 'Swat/SwatTableViewRow.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Displays totals in a special row at the bottom of a table view.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreTotalRow extends SwatTableViewRow
{
	// {{{ public properties

	public $title = null;
	public $link = null;
	public $link_title = null;
	public $value = null;
	public $offset = 0;
	public $note = null;

	// }}}
	// {{{ protected properties

	protected $money_cell_renderer;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->money_cell_renderer = new SwatMoneyCellRenderer();
		$this->addStyleSheet('packages/store/styles/store-total-row.css',
			 Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if ($this->value === null)
			$this->visible = false;

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
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$column_count = $this->view->getVisibleColumnCount();
		$th_tag = new SwatHtmlTag('th');
		$th_tag->colspan = $column_count - 1 - $this->offset;

		$th_tag->open();

		if ($this->link === null) {
			echo SwatString::minimizeEntities($this->title);
		} else {
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->link;
			$anchor_tag->title = $this->link_title;
			$anchor_tag->setContent($this->title);
			$anchor_tag->display();
		}
		echo ':';

		if ($this->note !== null) {
			$div = new SwatHtmlTag('div');
			$div->class = 'note';
			$div->setContent($this->note);
			$div->display();
		}

		$th_tag->close();
	}

	// }}}
	// {{{ protected function displayTotal()

	protected function displayTotal()
	{
		$column_count = $this->view->getVisibleColumnCount();

		$td_tag = new SwatHtmlTag('td');
		$td_tag->class = $this->getCSSClassString();
		$td_tag->open();

		if ($this->value > 0) {
			$this->money_cell_renderer->value = $this->value;
			$this->money_cell_renderer->render();
		} else {
			echo Store::_('FREE');
		}

		$td_tag->close();
	}

	// }}}
	// {{{ protected function displayBlank()

	protected function displayBlank()
	{
		if ($this->offset > 0) {
			$td_tag = new SwatHtmlTag('td');
			$td_tag->colspan = $this->offset;
			$td_tag->setContent('&nbsp;');
			$td_tag->display();
		}
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	protected function getCSSClassNames()
	{
		$classes = array();

		// renderer inheritance classes
		$classes = array_merge($classes,
			$this->money_cell_renderer->getInheritanceCSSClassNames());

		// renderer base classes
		$classes = array_merge($classes,
			$this->money_cell_renderer->getBaseCSSClassNames());

		if ($this->value == 0) {
			$classes[] = 'store-free';
		}

		// user specified classes
		$classes = array_merge($classes, $this->classes);

		return $classes;
	}

	// }}}
}

?>
