<?php

require_once 'Swat/SwatTextCellRenderer.php';

/**
 * Cell renderer for quantity discount quantities
 *
 * This cell renderer renders text as well as a region of text encapsulated in
 * a named span tag that can be updated via JavaScript.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemQuantityCellRenderer extends SwatTextCellRenderer
{
	public $secondary_text = '';

	public function render()
	{
		parent::render();

		echo ' ';

		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'store-item-quantity-cell-renderer-'.$this->id;
		$span_tag->setContent($this->secondary_text);
		$span_tag->display();
	}
}

?>
