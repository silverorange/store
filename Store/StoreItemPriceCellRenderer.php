<?php

require_once 'Store/StorePriceCellRenderer.php';
require_once 'Store/StoreSavingsCellRenderer.php';

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
		$original_price = $this->value;
		$this->value = $quantity_discount->getPrice();
		$savings_renderer = new StoreSavingsCellRenderer();
		$savings_renderer->value = 1 - ($this->value / $original_price);

		ob_start();
		parent::render();
		$price = ob_get_clean();

		$this->value = $original_price;

		$div = new SwatHtmlTag('div');
		$div->open();
		printf(Store::_('%s or more: %s each'),
			$quantity_discount->quantity, $price);

		$savings_renderer->render();

		$div->close();
	}

	// }}}
}

?>
