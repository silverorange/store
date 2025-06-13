<?php

/**
 * A table-view column that displays item ids in the tr tag.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemColumn extends SwatTableViewColumn
{
    public function getTrAttributes($row)
    {
        $attributes = [];

        if (isset($row->item, $row->item->id)) {
            $attributes['id'] = $this->id . '_' . $row->id;
        }

        return $attributes;
    }
}
