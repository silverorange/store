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
	public $quantitydiscounts;

	// }}}
	// {{{ public function render()

	public function render()
	{
		parent::render();
		if ($this->quantitydiscounts !== null)
			foreach ($this->quantitydiscounts as $quantitydiscount)
				$this->renderDiscount($quantitydiscount);
	}

	// }}}
	// {{{ private function renderDiscount()

	private function renderDiscount(StoreQuantityDiscount $quantitydiscount)
	{
		$price = SwatString::minimizeEntities(SwatString::moneyFormat(
			$quantitydiscount->price, $this->locale, $this->display_currency));

		printf('<br />%s %s %s', $quantitydiscount->quantity, 
			Store::_('or more:'), $price);
	}

	// }}}
}

?>
