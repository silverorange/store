<?php

/**
 * A currency cell renderer
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreYeOldePriceCellRenderer extends StoreItemPriceCellRenderer
{
	// {{{ public function getBaseCSSClassNames()

	/**
	 * Gets the base CSS class names for this cell renderer
	 *
	 * @return array the array of base CSS class names for this cell renderer.
	 */
	public function getBaseCSSClassNames()
	{
		$classes = parent::getBaseCSSClassNames();
		$classes[] = 'store-ye-olde-price-cell-renderer';
		return $classes;
	}

	// }}}
	// {{{ public function render()

	/**
	 * Renders the contents of this cell
	 *
	 * @see SwatCellRenderer::render()
	 */
	public function render()
	{
		if (!$this->visible)
			return;

		echo self::moneyFormat(
			$this->value, $this->locale, $this->display_currency);
	}

	// }}}
	// {{{ public static function moneyFormat()

	/**
	 * Renders the contents of this cell
	 */
	public static function moneyFormat(
		$value,
		$locale = null,
		$display_currency = false,
		$decimal_places = null
	) {
		$formatter = SwatI18NLocale::get($locale);

		$money = SwatString::minimizeEntities(
			$formatter->formatCurrency($value, $display_currency, array('fractional_digits' => $decimal_places))
		);

		if ($locale !== null) {
			$old_locale = setlocale(LC_ALL, 0);
			if (setlocale(LC_ALL, $locale) === false) {
				throw new SwatException(sprintf('Locale %s passed to the '.
					'moneyFormat() method is not valid for this operating '.
					'system.', $locale));
			}
		}

		$lc = localeconv();
		$decimal_point = $lc['mon_decimal_point'];

		// convert decimal character to UTF-8
		$character_set = nl_langinfo(CODESET);
		if ($character_set !== 'UTF-8') {
			$decimal_point = iconv($character_set, 'UTF-8', $decimal_point);
			if ($decimal_point === false)
				throw new SwatException(sprintf('Could not convert %s output '.
					'to UTF-8', $character_set));
		}

		if ($locale !== null)
			setlocale(LC_ALL, $old_locale);

		$search = sprintf('/%s([0-9][0-9])/u',
			preg_quote($decimal_point, '/'));

		$replace = sprintf('<sup>%s\1</sup>',
			$decimal_point);

		$money = preg_replace($search, $replace, $money);

		return $money;
	}

	// }}}
}

?>
