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
	public $payment_method;
	private $remove_button;
	private $edit_link = 'account/paymentmethod%s';

	public function init()
	{
		$this->remove_button =
			new SwatConfirmationButton($this->id);
	}

	public function process()
	{
		$this->remove_button->process();
	}

	public function hasBeenClicked()
	{
		return $this->remove_button->hasBeenClicked();
	}

	public function getHtmlHeadEntries()
	{
		$out = new SwatHtmlHeadEntrySet($this->html_head_entries);
		$out->addEntrySet($this->remove_button->getHtmlHeadEntries());

		return $out;
	}

	public function display()
	{
		ob_start();
		$this->payment_method->displayAsText();
		$payment_method_text = ob_get_clean();

		$div = new SwatHtmlTag('div');

		$div->open();

		$this->payment_method->display();

		$a = new SwatHtmlTag('a');
		$a->href = sprintf($this->edit_link, $this->payment_method->id);
		$a->setContent('Edit Payment Method');
		$a->display();

		$this->remove_button->title = 'Remove Payment Method';
		$this->remove_button->confirmation_message = sprintf(
			"Are you sure you want to remove the following payment method?\n\n%s",
			$payment_method_text);
		$this->remove_button->display();

		$div->close();
	}
}

?>
