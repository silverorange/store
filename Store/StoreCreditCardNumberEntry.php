<?php

require_once 'Swat/SwatEntry.php';
require_once 'Validate/Finance/CreditCard.php';

/**
 * A widget for basic validation of a credit card
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCreditCardNumberEntry extends SwatEntry
{
	// {{{ public properties

	/**
	 * Whether or not to show a blank_value
	 *
	 * @var boolean
	 */
	public $show_blank_value = false;

	/**
	 * The value to display as place holder for the credit card number
	 *
	 * @var string
	 */
	public $blank_value = '****************';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new credit card entry widget
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->size = 17;
	}

	// }}}
	// {{{ public function process()
	
	public function process()
	{
		parent::process();

		if ($this->value === null)
			return;

		if ($this->show_blank_value && $this->value == $this->blank_value) {
			$this->value = null;
			return;
		}

		$value = str_replace(array('-', ' '), '', $this->value);

		if (!Validate_Finance_CreditCard::number($value)) {
			$msg = Swat::_('The credit card number you have entered is not valid.
				Please check to make sure you have entered it correctly.');

			$this->addMessage(new SwatMessage($msg, SwatMessage::ERROR));
		}

		$this->value = $value;
	}

	// }}}
	// {{{ protected function getInputTag()

	protected function getInputTag()
	{
		$tag = parent::getInputTag();
		$tag->autocomplete = 'off';

		return $tag;
	}

	// }}}
	// {{{ protected function getDisplayValue()

	protected function getDisplayValue()
	{
		if ($this->show_blank_value && $this->value === null)
			return $this->blank_value;
		else
			return $this->value;
	}

	// }}}
}

?>
