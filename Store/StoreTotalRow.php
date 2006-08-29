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

		$column_count = $this->view->getVisibleColumnCount();
		$th_tag = new SwatHtmlTag('th');
		$th_tag->colspan = $column_count - 1 - $this->offset;

		$tr_tag->open();

		if ($this->link === null) {
			$th_tag->setContent($this->title.':');
			$th_tag->display();
		} else {
			$th_tag->open();
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->link;
			$anchor_tag->title = $this->link_title;
			$anchor_tag->setContent($this->title);
			$anchor_tag->display();
			echo ':';
			$th_tag->close();
		}

		$td_tag = new SwatHtmlTag('td');
		$td_tag->class = $this->getCSSClassString();
		$td_tag->open();

		if ($this->value > 0) {
			$this->money_cell_renderer->value = $this->value;
			$this->money_cell_renderer->render();
		} else {
			echo 'FREE';
		}

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
