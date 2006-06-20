<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';
require_once 'Swat/SwatConfirmationButton.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatControl.php';

/**
 * A viewer for an payment method object.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

class StorePaymentMethodView extends SwatControl
{
	// {{{ public properties

	public $payment_method;

	// }}}
	// {{{ private properties

	private $remove_button;
	private $edit_link = 'account/paymentmethod%s';

	// }}}
	// {{{ public function init

	public function init()
	{
		$this->remove_button =
			new SwatConfirmationButton($this->id);
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->remove_button->process();
	}

	// }}}
	// {{{ public function hasBeenClicked()

	public function hasBeenClicked()
	{
		return $this->remove_button->hasBeenClicked();
	}

	// }}}
	// {{{ public function getHtmlHeadEntries()

	public function getHtmlHeadEntries()
	{
		$out = new SwatHtmlHeadEntrySet($this->html_head_entries);
		$out->addEntrySet($this->remove_button->getHtmlHeadEntries());

		return $out;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		ob_start();
		$payment_method_text = ob_get_clean();

		$div = new SwatHtmlTag('div');
		$div->class = 'store-payment-method';

		$controls = new SwatHtmlTag('div');
		$controls->class = 'store-payment-method-controls';

		$edit_link = new SwatToolLink();
		$edit_link->href = sprintf($this->edit_link, $this->payment_method->id);
		$edit_link->title = 'Edit Payment Method';
		$edit_link->setFromStock('edit');

		$this->remove_button->title = 'Remove Payment Method';
		$this->remove_button->class = 'store-remove';
		$this->remove_button->confirmation_message = sprintf(
			"Are you sure you want to remove the following payment method?\n\n%s",
			$payment_method_text);

		$div->open();
			$controls->open();
				$edit_link->display();
				$this->remove_button->display();
			$controls->close();
			$this->payment_method->display();
		$div->close();
	}

	// }}}
}

?>
