<?php

/**
 * Renders item prices and can optionally strike-through the price if it's not
 * available in the current region.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdminItemPriceCellRenderer extends StoreItemPriceCellRenderer
{
    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->addStyleSheet(
            'packages/store/admin/styles/' .
            'store-admin-item-price-cell-renderer.css'
        );
    }

    /**
     * Enabled.
     *
     * @var bool
     */
    public $enabled;

    public function render()
    {
        if (!$this->enabled) {
            $span_tag = new SwatHtmlTag('span');
            $span_tag->class = 'store-item-price-disabled';
            $span_tag->open();
            parent::render();
            $span_tag->close();
        } else {
            parent::render();
        }
    }

    protected function isFree()
    {
        return false;
    }
}
