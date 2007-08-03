<?php

require_once 'StorePriceCellRenderer.php';

/**
 * Renders item prices, including any quantity discounts
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemPriceCellRenderer extends StorePriceCellRenderer
{
	// {{{ public properties

	/**
	 * The items quantity discount object to render
	 *
	 * @var StoreQuantityDiscount
	 */
	public $quantity_discounts;

	// }}}
	// {{{ public function render()

	public function render()
	{
		parent::render();

		if ($this->quantity_discounts !== null)
			foreach ($this->quantity_discounts as $quantity_discount)
				$this->renderDiscount($quantity_discount);
	}

	// }}}
	// {{{ private function renderDiscount()

	private function renderDiscount(StoreQuantityDiscount $quantity_discount)
	{
		$this->value = $quantity_discount->getPrice();
		$div = new SwatHtmlTag('div');

		$div->open();
		printf(Store::_('%s or more: '), $quantity_discount->quantity);
		parent::render();
		$div->close();
	}

	// }}}
}

?>
