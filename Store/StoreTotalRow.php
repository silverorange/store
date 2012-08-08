<?php

require_once 'Swat/SwatTableViewRow.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Displays totals in a special row in a table view.
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreTotalRow extends SwatTableViewRow
{
	// {{{ public properties

	/**
	 * Title of this total row
	 *
	 * @var string
	 */
	public $title = null;

	/**
	 * Link href
	 *
	 * If not specified, the title is displayed as a span. If specified, the
	 * title is displayed as an anchor element with this value as the href
	 * attribute value.
	 *
	 * @var string
	 */
	public $link = null;

	/**
	 * Link title
	 *
	 * If the {@link StoreTotalRow::$link} is set, this value will be used as
	 * the anchor element's title attribute value.
	 *
	 * @var string
	 */
	public $link_title = null;

	/**
	 * If set, value will be used as the anchor element's target attribute
	 * value
	 *
	 * @var string
	 */
	public $link_target = null;

	/**
	 * Text to use when the total is zero
	 *
	 * Defaults to 'FREE'. Only displayed if {@link StoreTotalRow::$show_free}
	 * is true (as it is by default).
	 *
	 * @var string
	 */
	public $free_text = '';

	/**
	 * The total value to display for this row
	 *
	 * If the value is null, this row is not displayed. Use a value of 0 to
	 * display free values.
	 *
	 * @var float
	 */
	public $value = null;

	/**
	 * Optional number of additional columns that exist to the right of the
	 * total column
	 *
	 * Dy default, no additional columns are displayed and the total values are
	 * in the last column of the table.
	 *
	 * @var integer
	 */
	public $offset = 0;

	/**
	 * Optional note to display with the title
	 *
	 * @var string
	 */
	public $note = null;

	/**
	 * Optional content type for {@link StoreTotalRow::$note}
	 *
	 * Defaults to text/plain, use text/xml for XHTML fragments.
	 *
	 * @var string
	 */
	public $note_content_type = 'text/plain';

	/**
	 * Whether or not to show special text for free values
	 *
	 * By default, special text is shown for free values.
	 *
	 * @var boolean
	 */
	public $show_free = true;

	/**
	 * Whether or not to show a colon following the title
	 *
	 * By default, a colon is displayed following the row title.
	 *
	 * @var boolean
	 */
	public $show_colon = true;

	/**
	 * Optional locale for currency format
	 *
	 * If not specified, the value will be formatted using the current locale.
	 *
	 * @var string
	 */
	public $locale = null;

	// }}}
	// {{{ protected properties

	/**
	 * Money cell renderer used to display the value
	 *
	 * @var SwatMoneyCellRenderer
	 */
	protected $money_cell_renderer;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->free_text = Store::_('FREE');
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

		parent::display();

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
		$colspan = $this->view->getXhtmlColspan();
		$th_tag = new SwatHtmlTag('th');
		$th_tag->colspan = max(1, $colspan - 1 - $this->offset);

		$th_tag->open();
		$this->displayTitle();
		$th_tag->close();
	}

	// }}}
	// {{{ protected function displayTitle()

	protected function displayTitle()
	{
		if ($this->link === null) {
			$title = SwatString::minimizeEntities($this->title);
		} else {
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->link;
			$anchor_tag->title = $this->link_title;
			$anchor_tag->target = $this->link_target;
			$anchor_tag->setContent($this->title);
			$title = $anchor_tag->__toString();
		}

		if ($this->note !== null) {
			$span = new SwatHtmlTag('span');
			$span->class = 'note';
			$span->setContent(sprintf('(%s)', $this->note),
				$this->note_content_type);

			$title.= ' '.$span->__toString();
		}

		if ($this->show_colon) {
			printf(Store::_('%s:'), $title);
		} else {
			echo $title;
		}
	}

	// }}}
	// {{{ protected function displayTotal()

	protected function displayTotal()
	{
		$td_tag = new SwatHtmlTag('td');
		$td_tag->class = $this->getCSSClassString();
		$td_tag->open();
		$this->displayValue();
		$td_tag->close();
	}

	// }}}
	// {{{ protected function displayValue()

	protected function displayValue()
	{
		if ($this->locale !== null)
			$this->money_cell_renderer->locale = $this->locale;

		if ($this->show_free && $this->value <= 0) {
			echo SwatString::minimizeEntities($this->free_text);
		} else {
			$this->money_cell_renderer->value = $this->value;
			$this->money_cell_renderer->render();
		}
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
