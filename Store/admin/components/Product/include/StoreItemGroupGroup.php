<?php

require_once 'Swat/SwatTableViewGroup.php';

/**
 * A special table-view group for displaying item-groups
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupGroup extends SwatTableViewGroup
{
	/**
	 * An array containing the number of items in each group
	 *
	 * @var array
	 */
	public $group_info;
	
	protected function displayRenderersInternal($row)
	{
		// the empty groupnum is set to zero in the sql select statement.
		foreach ($this->renderers as $renderer) {

			// make groups with 1 item have insensitive order links
			if ($renderer->id == 'order') {
				$renderer->sensitive = $renderer->sensitive &&
					($this->group_info[$row->item_group_id] > 1);
			}

			// only show title and order renderers for empty the group.
			// show all renderers for the other groups.
			if ($row->item_group_id != 0
				|| $renderer->id == 'title' || $renderer->id == 'order') {
				$renderer->render();
			}
		}
	}
}

?>
