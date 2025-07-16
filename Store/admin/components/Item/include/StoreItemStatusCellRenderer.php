<?php

/**
 * Cell renderer that displays a summary of the status of an item.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemStatusCellRenderer extends SwatCellRenderer
{
    /**
     * @var StoreItemStatus
     */
    public $status;

    /**
     * @var bool
     */
    public $available;

    public function render()
    {
        if (!$this->available) {
            $span_tag = new SwatHtmlTag('span');
            $span_tag->style = 'text-decoration: line-through;';
            $span_tag->open();
        }

        $this->displayTitle();

        if (!$this->available) {
            $span_tag->close();
        }
    }

    protected function displayTitle()
    {
        echo SwatString::minimizeEntities($this->status->title);
    }
}
