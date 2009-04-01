<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethodWrapper.php';

/**
 * Cell renderer for rendering a payment method wrapper
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderPaymentMethodsCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * The StoreOrderPaymentMethodWrapper dataobject to display
	 *
	 * @var StoreOrderPaymentMethodWrapper
	 */
	public $payment_methods;

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
	 *
	 * @sensitive
	 */
	public $passphrase;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		parent::render();

		if ($this->payment_methods instanceof StoreOrderPaymentMethodWrapper &&
			count($this->payment_methods) > 0) {

			if (count($this->payment_methods) == 1) {
				$payment_method = $this->payment_methods->getFirst();
				if ($this->gpg instanceof Crypt_GPG)
					$payment_method->setGPG($this->gpg);

				$payment_method->display($this->display_details,
					$this->passphrase);

			} else {
				echo '<table><tbody>';

				foreach ($this->payment_methods as $payment_method) {
					if ($this->gpg instanceof Crypt_GPG)
						$payment_method->setGPG($this->gpg);

					echo '<tr><td>';
					$payment_method->display($this->display_details,
						$this->passphrase);

					echo '</td><td>';
					$payment_method->displayAmount();
					echo '</td></tr>';
				}

				echo '</tbody></table>';
			}
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
