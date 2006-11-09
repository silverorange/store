<?php

require_once 'Admin/AdminTitleLinkCellRenderer.php';
require_once 'Swat/SwatString.php';

/**
 * Cell renderer that displays product titles with no items in a special
 * way
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductTitleCellRenderer extends AdminTitleLinkCellRenderer
{
	public $item_count = 0;

	public function render()
	{
		if ($this->item_count == 0) {
			$this->text = SwatString::minimizeEntities($this->text);
			$this->content_type = 'text/xml';
			$this->text.= sprintf(' <span>&lt;%s&gt;</span>',
				Store::_('no items'));

		} else {
			$this->content_type = 'text/plain';
		}

		parent::render();
	}
}

?>
