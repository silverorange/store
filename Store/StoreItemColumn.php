<?php

require_once 'Swat/SwatTableViewColumn.php';

/**
 * A table-view column that displays item ids in the tr tag
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class ItemColumn extends SwatTableViewColumn
{
	// {{{ public function getTrAttributes()

	public function getTrAttributes($row)
	{
		$attributes = array();

		if (isset($row->item) && isset($row->item->id))
			$attributes['id'] = $this->id.'_'.$row->id;

		return $attributes;
	}

	// }}}
}

?>
