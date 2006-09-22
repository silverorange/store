<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';
require_once 'Swat/SwatConfirmationButton.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatControl.php';

/**
 * A viewer for an payment method object.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
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
	// {{{ public function init()

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
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->remove_button->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		ob_start();
		$this->payment_method->displayAsText();
		$payment_method_text = ob_get_clean();

		$div = new SwatHtmlTag('div');
		$div->class = $this->getCssClassString();

		$controls = new SwatHtmlTag('div');
		$controls->class = 'store-payment-method-view-controls';

		$edit_link = new SwatToolLink();
		$edit_link->link = sprintf($this->edit_link, $this->payment_method->id);
		$edit_link->title = Store::_('Edit Payment Method');
		$edit_link->setFromStock('edit');

		$this->remove_button->title = Store::_('Remove');
		$this->remove_button->classes[] = 'store-remove';
		$this->remove_button->confirmation_message = sprintf("%s\n\n%s",
			Store::_('Are you sure you want to remove the following payment '.
				'method?'), $payment_method_text);

		$div->open();
		$this->payment_method->display();
		$controls->open();
		$edit_link->display();
		$this->remove_button->display();
		$controls->close();
		$div->close();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this entry widget
	 *
	 * @return array the array of CSS classes that are applied to this entry
	 *                widget.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('store-payment-method-view');
		$classes = array_merge($classes, $this->classes);
		return $classes;
	}

	// }}}
}

?>
