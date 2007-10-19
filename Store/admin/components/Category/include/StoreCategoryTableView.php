<?php

require_once 'Swat/SwatTableView.php';

/**
 * A table view that displays categories with no products in a special way
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryTableView extends SwatTableView
{
	// {{{ protected function getRowClasses()

	protected function getRowClasses($row, $count)
	{
		$classes = parent::getRowClasses($row, $count);

 		if ($row->product_count == 0)
			$classes[] = 'category-no-products';

 		if (!$row->currently_visible && !$row->always_visible)
			$classes[] = 'category-not-visible';

		return $classes;
	}

	// }}}
}

?>
