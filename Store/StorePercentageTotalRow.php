<?php

/**
 * Displays totals in a special row in a table view.
 *
 * @copyright 2006-2016 silverorange
 */
class StorePercentageTotalRow extends SwatTableViewRow
{
    /**
     * Title of this total row.
     *
     * @var string
     */
    public $title;

    /**
     * Link href.
     *
     * If not specified, the title is displayed as a span. If specified, the
     * title is displayed as an anchor element with this value as the href
     * attribute value.
     *
     * @var string
     */
    public $link;

    /**
     * Link title.
     *
     * If the {@link StoreTotalRow::$link} is set, this value will be used as
     * the anchor element's title attribute value.
     *
     * @var string
     */
    public $link_title;

    /**
     * The total value to display for this row.
     *
     * If the value is null, this row is not displayed.
     *
     * @var float
     */
    public $value;

    /**
     * Optional number of additional columns that exist to the right of the
     * total column.
     *
     * Dy default, no additional columns are displayed and the total values are
     * in the last column of the table.
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Optional note to display with the title.
     *
     * @var string
     */
    public $note;

    /**
     * Optional content type for {@link StoreTotalRow::$note}.
     *
     * Defaults to text/plain, use text/xml for XHTML fragments.
     *
     * @var string
     */
    public $note_content_type = 'text/plain';

    /**
     * Whether or not to show a colon following the title.
     *
     * By default, a colon is displayed following the row title.
     *
     * @var bool
     */
    public $show_colon = true;

    /**
     * Percentage cell renderer used to display the value.
     *
     * @var SwatPercentageCellRenderer
     */
    protected $percentage_cell_renderer;

    public function __construct()
    {
        parent::__construct();
        $this->percentage_cell_renderer = new SwatPercentageCellRenderer();
        $this->addStyleSheet('packages/store/styles/store-total-row.css');
    }

    public function display()
    {
        if ($this->value === null) {
            $this->visible = false;
        }

        if (!$this->visible) {
            return;
        }

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

    protected function displayHeader()
    {
        $colspan = $this->view->getXhtmlColspan();
        $th_tag = new SwatHtmlTag('th');
        $th_tag->colspan = $colspan - 1 - $this->offset;

        $th_tag->open();
        $this->displayTitle();
        $th_tag->close();
    }

    protected function displayTitle()
    {
        if ($this->link === null) {
            $title = SwatString::minimizeEntities($this->title);
        } else {
            $anchor_tag = new SwatHtmlTag('a');
            $anchor_tag->href = $this->link;
            $anchor_tag->title = $this->link_title;
            $anchor_tag->setContent($this->title);
            $title = $anchor_tag->__toString();
        }

        if ($this->note !== null) {
            $span = new SwatHtmlTag('span');
            $span->class = 'note';
            $span->setContent(
                sprintf('(%s)', $this->note),
                $this->note_content_type
            );

            $title .= ' ' . $span->__toString();
        }

        if ($this->show_colon) {
            printf(Store::_('%s:'), $title);
        } else {
            echo $title;
        }
    }

    protected function displayTotal()
    {
        $td_tag = new SwatHtmlTag('td');
        $td_tag->class = $this->getCSSClassString();
        $td_tag->open();
        $this->displayValue();
        $td_tag->close();
    }

    protected function displayValue()
    {
        $this->percentage_cell_renderer->value = $this->value;
        $this->percentage_cell_renderer->render();
    }

    protected function displayBlank()
    {
        if ($this->offset > 0) {
            $td_tag = new SwatHtmlTag('td');
            $td_tag->colspan = $this->offset;
            $td_tag->setContent('&nbsp;');
            $td_tag->display();
        }
    }

    protected function getCSSClassNames()
    {
        $classes = [];

        // renderer inheritance classes
        $classes = array_merge(
            $classes,
            $this->percentage_cell_renderer->getInheritanceCSSClassNames()
        );

        // renderer base classes
        $classes = array_merge(
            $classes,
            $this->percentage_cell_renderer->getBaseCSSClassNames()
        );

        // user specified classes
        return array_merge($classes, $this->classes);
    }
}
