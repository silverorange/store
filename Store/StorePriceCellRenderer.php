<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Renders item prices
 *
 * Outputs "Free" if value is 0. When displaying free, a CSS class called
 * store-free is appended to the list of TD classes.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public properties

	public $discount = 0;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->value === null)
			return;

		if ($this->isFree()) {
			echo Store::_('Free!');
		} else {
			parent::render();

			if ($this->discount > 0)
				$this->displayDiscount();
		}
	}

	// }}}
	// {{{ public function displayDiscount()

	public function displayDiscount()
	{
		if ($this->discount == 0)
			return;

		$locale = SwatI18NLocale::get($this->locale);

		ob_start();

		echo SwatString::minimizeEntities(
			$locale->formatCurrency($this->discount,
				$this->international,
				array('fractional_digits' =>
				$this->decimal_places)));

		if (!$this->international && $this->display_currency)
			echo '&nbsp;', SwatString::minimizeEntities(
				$locale->getInternationalCurrencySymbol());

		$formatted_discount = ob_get_clean();

		echo '<div class="store-cart-discount">';

		printf(Store::_('You save %s'),
			$formatted_discount);

		echo '</div>';
	}

	// }}}
	// {{{ public function getDataSpecificCSSClassNames()

	public function getDataSpecificCSSClassNames()
	{
		$classes = array();

		if ($this->isFree())
			$classes[] = 'store-free';

		return $classes;
	}

	// }}}
	// {{{ protected function isFree()

	protected function isFree()
	{
		return ($this->value == 0);
	}

	// }}}
}

?>
