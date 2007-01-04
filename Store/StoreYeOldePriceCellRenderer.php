<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Store/StoreItemPriceCellRenderer.php';

/**
 * A currency cell renderer
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreYeOldePriceCellRenderer extends StoreItemPriceCellRenderer
{
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

		$money = SwatString::minimizeEntities(
			SwatString::moneyFormat(
				$this->value, $this->locale, $this->display_currency));

		if ($this->locale !== null) {
			$old_locale = setlocale(LC_ALL, 0);
			if (setlocale(LC_ALL, $this->locale) === false) {
				throw new SwatException(sprintf('Locale %s passed to the '.
					'moneyFormat() method is not valid for this operating '.
					'system.', $this->locale));
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

		if ($this->locale !== null)
			setlocale(LC_ALL, $old_locale);

		$search = sprintf('/%s([0-9][0-9])/u',
			preg_quote($decimal_point, '/'));

		$replace = sprintf('<sup>%s\1</sup>',
			$decimal_point);

		$money = preg_replace($search, $replace, $money);

		echo $money;
	}

	// }}}
}

?>
