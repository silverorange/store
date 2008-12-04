<?php

require_once 'Swat/SwatMoneyEntry.php';
require_once 'Swat/SwatRadioList.php';

/**
 * A custom radio list that has an embedded custom option
 *
 * @package   Store
 * @copyright 2007-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreGiftCertificateRadioList extends SwatRadioList
{
	// {{{ public properties

	public $custom_value = 'custom';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new radiolist
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addJavaScript(
			'packages/store/javascript/store-gift-certificate-radio-list.js');
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this radio list
	 *
	 * Ensures that a custom price is entered if a custom option is
	 * selected.
	 */
	public function process()
	{
		parent::process();

		if ($this->value === null)
			return;

		if ($this->value == $this->custom_value &&
			$this->getCompositeWidget('custom_price')->value === null) {
			$message = Store::_('Please enter a value for your custom gift '.
				'certificate.');

			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ public function getPrice()

	public function getPrice()
	{
		if ($this->value == $this->custom_value)
			$price = $this->getCompositeWidget('custom_price')->value;
		else
			$price = abs($this->value);

		return $price;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this radio list
	 */
	public function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function getState()

	public function getState()
	{
		$custom_price = $this->getCompositeWidget('custom_price')->getState();
		return array(
			'value' => $this->value,
			'custom_price' => $custom_price,
		);
	}

	// }}}
	// {{{ public function getState()

	public function setState($state)
	{
		$this->value = $state['value'];
		$this->getCompositeWidget('custom_price')->setState(
			$state['custom_price']);
	}

	// }}}
	// {{{ protected function displayOptionLabel()

	/**
	 * Displays an option in the radio list
	 *
	 * @param SwatOption $option
	 */
	protected function displayOptionLabel(SwatOption $option)
	{
		parent::displayOptionLabel($option);

		if ($option->value == $this->custom_value)
			$this->getCompositeWidget('custom_price')->display();

	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$entry = new SwatMoneyEntry($this->id.'_custom_price');
		$entry->minimum_value = 1;
		$entry->maximum_value = 1000000;

		$this->addCompositeWidget($entry, 'custom_price');
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required for this control
	 *
	 * @return string the inline JavaScript required for this control.
	 */
	protected function getInlineJavaScript()
	{
		$javascript = sprintf("var %s_obj = ".
			"new StoreGiftCertificateRadioList('%s', '%s');\n",
			$this->id, $this->id, $this->custom_value);

		return $javascript;
	}

	// }}}
}

?>
