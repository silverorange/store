<?php

require_once 'Swat/SwatNullTextCellRenderer.php';
require_once 'Swat/SwatString.php';

/**
 * Cell renderer that displays a summary of statuses of items in a product
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductStatusCellRenderer extends SwatNullTextCellRenderer
{
	// {{{ public properties

	public $count_available = 0;
	public $count_available_instock = 0;
	public $count_available_outofstock = 0;
	public $count_available_limitedstock = 0;
	public $count_available_backordered = 0;
	public $count_unavailable = 0;
	public $count_unavailable_instock = 0;
	public $count_unavailable_outofstock = 0;
	public $count_unavailable_limitedstock = 0;
	public $count_unavailable_backordered = 0;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if ($this->isSensitive() === false) {
			parent::render();
			return;
		}

		$div_tag = new SwatHtmlTag('div');

		if ($this->count_available > 0) {
			$div_tag->setContent(sprintf(Store::_('Available: %s'),
				implode(', ', $this->getAvailableDescriptions())),
				'text/xml');

			$div_tag->display();
		}

		if ($this->count_unavailable > 0) {
			$div_tag->setContent(sprintf(Store::_('Unavailable: %s'),
				implode(', ', $this->getUnavailableDescriptions())),
				'text/xml');

			$div_tag->display();
		}
	}

	// }}}
	// {{{ public function isSensitive()

	public function isSensitive()
	{
		return ($this->count_available + $this->count_unavailable > 0);
	}

	// }}}
	// {{{ protected function getAvailableDescriptions()

	protected function getAvailableDescriptions()
	{
		$descriptions = array();

		if ($this->count_available_instock > 0)
			$descriptions[] = sprintf(Store::_('%s in-stock'),
				SwatString::numberFormat($this->count_available_instock));

		if ($this->count_available_outofstock > 0)
			$descriptions[] = sprintf(Store::_('%s out-of-stock'),
				SwatString::numberFormat($this->count_available_outofstock));

		if ($this->count_available_limitedstock > 0)
			$descriptions[] = sprintf(Store::_('%s limited-stock'),
				SwatString::numberFormat($this->count_available_limitedstock));

		if ($this->count_available_backordered > 0)
			$descriptions[] = sprintf(Store::_('%s back-ordered'),
				SwatString::numberFormat($this->count_available_backordered));

		return $descriptions;
	}

	// }}}
	// {{{ protected function getUnavailableDescriptions()

	protected function getUnavailableDescriptions()
	{
		$descriptions = array();

		if ($this->count_unavailable_instock > 0)
			$descriptions[] = '<span class="product-not-visible">'.
				sprintf(Store::_('%s disabled'),
					SwatString::numberFormat(
						$this->count_unavailable_instock)).'</span>';

		if ($this->count_unavailable_outofstock > 0)
			$descriptions[] = sprintf(Store::_('%s out-of-stock'),
				SwatString::numberFormat($this->count_unavailable_outofstock));

		if ($this->count_unavailable_limitedstock > 0)
			$descriptions[] = sprintf(Store::_('%s limited-stock'),
				SwatString::numberFormat($this->count_unavailable_limitedstock));

		if ($this->count_unavailable_backordered > 0)
			$descriptions[] = sprintf(Store::_('%s back-ordered'),
				SwatString::numberFormat($this->count_unavailable_backordered));

		return $descriptions;
	}

	// }}}
}

?>
