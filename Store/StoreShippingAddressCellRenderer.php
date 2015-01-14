<?php

require_once 'Store/StoreAddressCellRenderer.php';

/**
 * An billing address cell renderer that displays a message if the address is
 * the same as the shipping address
 *
 * @package   Store
 * @copyright 2006-2015 silverorange
 */
class StoreShippingAddressCellRenderer extends StoreAddressCellRenderer
{
	// {{{ public properties

	public $billing_address = null;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible) {
			return;
		}

		$same_address = ($this->address instanceof StoreAddress &&
			$this->billing_address instanceof StoreAddress &&
			(($this->billing_address->id === null &&
				$this->address->id === null &&
				$this->address === $this->billing_address) ||
			($this->address->id !== null &&
				$this->billing_address->id !== null &&
				$this->billing_address->id == $this->address->id)));

		if ($same_address) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-null-text-cell-renderer';
			$span_tag->setContent(Store::_('<ship to billing address>'));
			$span_tag->display();
			SwatCellRenderer::render();
		} else {
			parent::render();
		}
	}

	// }}}
}

?>
