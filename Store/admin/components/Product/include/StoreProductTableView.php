<?php

/**
 * A table view that displays products with no items in a special way.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductTableView extends SwatTableView
{
    // {{{ protected function getRowClasses()

    protected function getRowClasses($row, $count)
    {
        $classes = parent::getRowClasses($row, $count);

        if ($row->item_count == 0) {
            $classes[] = 'product-no-items';
        }

        if ($row->count_unavailable == $row->item_count) {
            $classes[] = 'product-disabled';
        }

        if (!$row->currently_visible) {
            $classes[] = 'product-not-visible';
        }

        return $classes;
    }

    // }}}
}
