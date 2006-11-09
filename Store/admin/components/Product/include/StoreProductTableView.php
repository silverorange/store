<?php

require_once 'Swat/SwatTableView.php';

/**
 * A table view that displays products with no items in a special way
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductTableView extends SwatTableView
{
	// {{{ protected function getRowClasses()

	protected function getRowClasses($row, $count)
	{
		$classes = parent::getRowClasses($row, $count);

		if ($row->item_count == 0)
			$classes[] = 'product-no-items';

		if ($row->count_disabled == $row->item_count)
			$classes[] = 'product-disabled';

		return $classes;
	}

	// }}}
}

?>
