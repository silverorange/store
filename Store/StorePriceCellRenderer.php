<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Renders item prices
 *
 * Outputs "Free" if value is 0. When displaying free, a CSS class called
 * store-free is appended to the list of TD classes.
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $free_text;

	/**
	 * @var string
	 */
	public $free_text_content_type = 'text/plain';

	/**
	 * @var float
	 */
	public $discount = 0;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->free_text = Store::_('Free!');
	}

	// }}}
	// {{{ public function render()

	public function render(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		if ($this->value === null) {
			return;
		}

		if ($this->isFree()) {
			if ($this->free_text_content_type == 'text/xml') {
				$context->out($this->free_text);
			} else {
				$context->out(SwatString::minimizeEntities($this->free_text));
			}
		} else {
			parent::render($context);
		}

		if ($this->discount > 0) {
			$this->displayDiscount($context);
		}
	}

	// }}}
	// {{{ public function displayDiscount()

	public function displayDiscount(SwatDisplayContext $context)
	{
		if ($this->discount == 0) {
			return;
		}

		$locale = SwatI18NLocale::get($this->locale);
		$format = $this->getCurrencyFormat();

		ob_start();

		echo SwatString::minimizeEntities(
			$locale->formatCurrency(
				$this->discount,
				$this->international,
				$format
			)
		);

		if (!$this->international && $this->display_currency) {
			echo '&nbsp;'.SwatString::minimizeEntities(
				$locale->getInternationalCurrencySymbol()
			);
		}

		$formatted_discount = ob_get_clean();

		$context->out('<div class="store-cart-discount">');

		$context->out(
			sprintf(
				Store::_('You save %s'),
				$formatted_discount
			)
		);

		$context->out('</div>');
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
