<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * Cell renderer for displaying payment details for an order
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePaymentMethodCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * The StorePaymentMethod dataobject to display 
	 *
	 * @var StorePaymentMethod
	 */
	public $payment_method;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		$this->payment_method->display();
	}

	// }}}
}

?>
