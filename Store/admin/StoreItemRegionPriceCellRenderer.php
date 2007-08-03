<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * A cell renderer that displays the price for an item in a region
 *
 * If the item has no price then 'n/a' is displayed
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemRegionPriceCellRenderer extends SwatMoneyCellRenderer
{
	public $enabled = true;

	/**
	 * Creates an item price cell renderer
	 */
	public function __construct()
	{
		parent::__construct();
		$this->addStyleSheet(
			'packages/swat/styles/swat-null-text-cell-renderer.css');
	}

	public function render()
	{
		if (!$this->enabled) {
			$span = new SwatHtmlTag('span');
			$span->class = 'item-disabled';
			$span->open();
		}

		if ($this->value === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-null-text-cell-renderer';
			$span_tag->setContent(Store::_('<n/a>'));
			$span_tag->display();
		} else {
			parent::render();
		}

		if (!$this->enabled)
			$span->close();
	}
}

?>
