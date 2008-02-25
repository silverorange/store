<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * Cell renderer for rendering a payment method
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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

	/**
	 * Whether or not to show additional details for card-type payment methods
	 *
	 * @var boolean
	 */
	public $display_details = true;

	/**
	 * The Crypt_GPG object to use for decryption
	 *
	 * @var Crypt_GPG
	 */
	public $gpg;

	/**
	 * The passphrase to decrypt with
	 *
	 * @var string
	 */
	public $passphrase;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->payment_method instanceof StorePaymentMethod) {
			$this->payment_method->display($this->display_details, $this->gpg,
				$this->passphrase);

		} else {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-none';
			$span_tag->setContent(Store::_('<none>'));
			$span_tag->display();
		}
	}

	// }}}
}

?>
