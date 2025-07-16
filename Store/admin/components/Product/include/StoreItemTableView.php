<?php

/**
 * A table view that displays categories with no products in a special way.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemTableView extends SwatTableView
{
    protected function getRowClasses($row, $count)
    {
        $classes = parent::getRowClasses($row, $count);

        if (!$row->enabled) {
            $classes[] = 'item-disabled';
        }

        return $classes;
    }
}
