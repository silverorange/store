<?php

require_once 'StorePriceCellRenderer.php';

/**
 * Renders item prices, including any quantity discounts
 *
 * @package   Store
 * @copyright 2006 silverorange
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
		$price = SwatString::minimizeEntities(SwatString::moneyFormat(
			$quantity_discount->price, $this->locale, $this->display_currency));

		$div = new SwatHtmlTag('div');
		$div->open();
		printf('%s %s %s', $quantity_discount->quantity, Store::_('or more:'), 
			$price);
		$div->close();
	}

	// }}}
}

?>
