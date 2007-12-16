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
	 * Original value
	 *
	 * The original price of the item prior to discounts.
	 *
	 * @var float
	 */
	public $original_value;

	/**
	 * The items quantity discount object to render
	 *
	 * @var StoreQuantityDiscount
	 */
	public $quantity_discounts;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();

		$this->addStyleSheet(
			'packages/store/styles/store-item-price-cell-renderer.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		if ($this->original_value !== null &&
			$this->original_value != $this->value) {
			$this->renderOriginalValue();
			echo ' ';
		} elseif ($this->value == 0) {
			parent::render();
		} else {
			ob_start();
			parent::render();
			$price = ob_get_clean();

			printf(Store::_('%s each'), $price);
		}

		if ($this->quantity_discounts !== null)
			foreach ($this->quantity_discounts as $quantity_discount)
				$this->renderDiscount($quantity_discount);
	}

	// }}}
	// {{{ protected function renderValue()

	protected function renderValue($value, $original_value = null)
	{
		$class_value = $this->value;

		if ($value != $original_value) {
			$this->value = $original_value;
			$span = new SwatHtmlTag('span');
			$span->class = 'store-sale-discount-price';
			$span->open();
			parent::render();
			$span->close();
		}

		$this->value = $value;
		parent::render();

		$this->value = $class_value;
	}

	// }}}
	// {{{ protected function renderOriginalValue()

	protected function renderOriginalValue()
	{
		ob_start();
		$this->renderValue($this->value, $this->original_value);
		$price = ob_get_clean();

		printf(Store::_('%s each'), $price);

		if ($this->value > 0) {
			$savings_renderer = new StoreSavingsCellRenderer();
			$savings_renderer->value =
				round(1 - ($this->value / $this->original_value), 2);

			$span = new SwatHtmlTag('span');
			$span->open();
			echo ' ';
			$savings_renderer->render();
			$span->close();
		}
	}

	// }}}
	// {{{ private function renderDiscount()

	private function renderDiscount(StoreQuantityDiscount $quantity_discount)
	{
		$value = $quantity_discount->getDisplayPrice();
		$original_value = $quantity_discount->getPrice();

		ob_start();
		$this->renderValue($value, $original_value);
		$price = ob_get_clean();

		$div = new SwatHtmlTag('div');
		$div->open();
		printf(Store::_('%s or more: %s each'),
			$quantity_discount->quantity, $price);

		echo ' ';

		$savings_renderer = new StoreSavingsCellRenderer();
		$savings_renderer->value =
			round(1 - ($value / $quantity_discount->item->getPrice()), 2);
		$savings_renderer->render();

		$div->close();
	}

	// }}}
}

?>
