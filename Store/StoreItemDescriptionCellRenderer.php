<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Swat/SwatString.php';

/**
 * Combines item SKU and description into one cell renderer
 *
 * Outputs the item SKU in a span with the class "item-sku" if there is a
 * description, and separates the description from the item SKU with a dash.
 *
 * For example:
 *    SKU
 *    <span>SKU</span> - Desc
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreItemDescriptionCellRenderer extends SwatCellRenderer
{
	public $sku = null;
	public $description = null;

	public function render()
	{
		if (strlen($this->description) == 0)
			echo '<span class="item-sku">',
				SwatString::minimizeEntities($this->sku),
				'</span>';
		else
			echo '<span class="item-sku">',
				SwatString::minimizeEntities($this->sku),
				'</span> - ',
				SwatString::minimizeEntities($this->description);
	}
}

?>
