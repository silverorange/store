<?php

/**
 * A table view row with an embedded button.
 *
 * @copyright 2006-2016 silverorange
 */
class StoreTableViewButtonRow extends SwatTableViewRow
{
    /**
     * Display the button in the left cell.
     */
    public const POSITION_LEFT = 0;

    /**
     * Display the button in the right cell.
     */
    public const POSITION_RIGHT = 1;

    /**
     * Button title.
     *
     * @var string
     */
    public $title;

    /**
     * How far from the right side the table the button should be displayed
     * measured in columns.
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Tab index.
     *
     * The ordinal tab index position of the XHTML input tag, or null.
     *
     * @var int
     */
    public $tab_index;

    /**
     * How many table-view columns the button should span.
     *
     * @var int
     */
    public $span = 1;

    /**
     * Whether to display the button in the left or right cell or the row.
     *
     * By default, the vutton displays in the left cell. Use the POSITION_*
     * constants to control the button position.
     *
     * @var int
     */
    public $position = self::POSITION_LEFT;

    /**
     * Whether or not the internal widgets used by this row have been created
     * or not.
     *
     * @var bool
     */
    protected $widgets_created = false;

    /**
     * Button displayed in this row.
     *
     * @var SwatButton
     */
    protected $button;

    public function init()
    {
        $this->createEmbeddedWidgets();
        $this->button->init();
    }

    public function process()
    {
        $this->createEmbeddedWidgets();
        $this->button->process();
    }

    public function display()
    {
        if (!$this->visible) {
            return;
        }

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
            $this->displayEmptyCell();
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
                $this->displayEmptyCell();
                $td_tag->close();
            }
        }

        $tr_tag->close();
    }

    public function hasBeenClicked()
    {
        $this->createEmbeddedWidgets();

        return $this->button->hasBeenClicked();
    }

    public function getHtmlHeadEntrySet()
    {
        $this->createEmbeddedWidgets();
        $set = parent::getHtmlHeadEntrySet();
        $set->addEntrySet($this->button->getHtmlHeadEntrySet());

        return $set;
    }

    /**
     * Displays the button contained by this row.
     */
    protected function displayButton()
    {
        // properties may have been modified since the widgets were created
        $this->button->title = $this->title;
        $this->button->tab_index = $this->tab_index;
        $this->button->display();
    }

    /**
     * Displays the empty cell in this row.
     */
    protected function displayEmptyCell()
    {
        echo '&nbsp;';
    }

    /**
     * Gets the array of CSS classes that are applied to this row.
     *
     * @return array the array of CSS classes that are applied to this row
     */
    protected function getCSSClassNames()
    {
        $classes = ['store-table-view-button-row'];

        return array_merge($classes, $this->classes);
    }

    protected function createEmbeddedWidgets()
    {
        if (!$this->widgets_created) {
            $this->button = new SwatButton($this->id . '_button');
            $this->button->parent = $this;
            $this->widgets_created = true;
        }
    }
}
