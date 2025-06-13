<?php

/**
 * Combines item SKU and description into one cell renderer.
 *
 * Outputs the item SKU in a span with the class "item-sku" if there is a
 * description, and separates the description from the item SKU with a dash.
 *
 * For example:
 *    SKU
 *    <span>SKU</span> - Desc
 *
 * @copyright 2006-2016 silverorange
 */
class StoreItemDescriptionCellRenderer extends SwatCellRenderer
{
    // {{{ public properties

    public $sku;

    public $description;
    public $description_content_type = 'text/plain';

    // }}}
    // {{{ public function render()

    public function render()
    {
        if (!$this->visible) {
            return;
        }

        parent::render();

        if ($this->description == '') {
            echo '<span class="item-sku">',
            SwatString::minimizeEntities($this->sku),
            '</span>';
        } else {
            echo '<span class="item-sku">',
            SwatString::minimizeEntities($this->sku),
            '</span> - ';

            if ($this->description_content_type == 'text/xml') {
                echo $this->description;
            } else {
                echo SwatString::minimizeEntities($this->description);
            }
        }
    }

    // }}}
}
