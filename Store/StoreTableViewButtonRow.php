<?php

require_once 'Swat/SwatTableViewRow.php';
require_once 'Swat/SwatButton.php';

/**
 * A table view row with an embedded button
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
class StoreTableViewButtonRow extends SwatTableViewRow
{
	// {{{ class constants

	/**
	 * Display the button in the left cell
	 */
	const POSITION_LEFT = 0;

	/**
	 * Display the button in the right cell
	 */
	const POSITION_RIGHT = 1;

	// }}}
	// {{{ public properties

	/**
	 * Button title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * How far from the right side the table the button should be displayed
	 * measured in columns
	 *
	 * @var integer
	 */
	public $offset = 0;

	/**
	 * Tab index
	 *
	 * The ordinal tab index position of the XHTML input tag, or null.
	 *
	 * @var integer
	 */
	public $tab_index = null;

	/**
	 * How many table-view columns the button should span
	 *
	 * @var integer
	 */
	public $span = 1;

	/**
	 * Whether to display the button in the left or right cell or the row
	 *
	 * By default, the vutton displays in the left cell. Use the POSITION_*
	 * constants to control the button position.
	 * 
	 * @var integer
	 */
	public $position = self::POSITION_LEFT;

	// }}}
	// {{{ protected properties

	/**
	 * Whether or not the internal widgets used by this row have been created
	 * or not
	 *
	 * @var boolean
	 */
	protected $widgets_created = false;

	/**
	 * Button displayed in this row
	 *
	 * @var SwatButton
	 */
	protected $button = null;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->createEmbeddedWidgets();
		$this->button->init();
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->createEmbeddedWidgets();
		$this->button->process();
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		$this->createEmbeddedWidgets();

		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->id = $this->id;
		$tr_tag->class = $this->getCSSClassString();

		$colspan = $this->view->getXhtmlColspan();
		$td_tag = new SwatHtmlTag('td');
		$td_tag->colspan = $colspan - $this->offset;

		$tr_tag->open();

		if ($this->position === self::POSITION_LEFT || $this->offset == 0) {
			$td_tag->class = 'button-cell';
			$td_tag->open();
			$this->displayButton();
			$td_tag->close();
		} else {
			$td_tag->open();
			echo '&nbsp;';
			$td_tag->close();
		}

		if ($this->offset > 0) {
			$td_tag->colspan = $this->offset;

			if ($this->position === self::POSITION_RIGHT) {
				$td_tag->class = 'button-cell';
				$td_tag->open();
				$this->displayButton();
				$td_tag->close();
			} else {
				$td_tag->open();
				echo '&nbsp;';
				$td_tag->close();
			}	
		}

		$tr_tag->close();
	}

	// }}}
	// {{{ public function hasBeenClicked()

	public function hasBeenClicked()
	{
		$this->createEmbeddedWidgets();
		return $this->button->hasBeenClicked();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$this->createEmbeddedWidgets();
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->button->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
	// {{{ protected function displayButton()

	/**
	 * Displays the button contained by this row
	 */
	protected function displayButton()
	{
		// properties may have been modified since the widgets were created
		$this->button->title = $this->title;
		$this->button->tab_index = $this->tab_index;
		$this->button->display();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this row
	 *
	 * @return array the array of CSS classes that are applied to this row.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('store-table-view-button-row');
		$classes = array_merge($classes, $this->classes);
		return $classes;
	}

	// }}}
	// {{{ protected function createEmbeddedWidgets()

	protected function createEmbeddedWidgets()
	{
		if (!$this->widgets_created) {
			$this->button = new SwatButton($this->id.'_button');
			$this->button->parent = $this;
			$this->widgets_created = true;
		}
	}

	// }}}
}

?>
