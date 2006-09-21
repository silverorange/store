<?php

require_once 'Store/StoreAddressCellRenderer.php';

/**
 * An billing address cell renderer that displays a message if the address is
 * the same as the shipping address
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreShippingAddressCellRenderer extends StoreAddressCellRenderer
{
	// {{{ public properties

	public $billing_address = null;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if ($this->address->id == $this->billing_address->id) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-null-text-cell-renderer';
			$span_tag->setContent('<ship to billing address>');
			$span_tag->display();
		} else {
			parent::render();
		}
	}

	// }}}
}

?>
