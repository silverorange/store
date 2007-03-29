<?php

require_once 'Swat/SwatEntry.php';
require_once 'Validate/Finance/CreditCard.php';

/**
 * A widget for basic validation of a debit or credit card
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCardNumberEntry extends SwatEntry
{
	// {{{ public properties

	/**
	 * Whether or not to show a blank_value
	 *
	 * @var boolean
	 */
	public $show_blank_value = false;

	/**
	 * The value to display as place holder for the card number
	 *
	 * @var string
	 */
	public $blank_value = '****************';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new card entry widget
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

		$data = &$this->getForm()->getFormData();

		if (isset($data[$this->id.'_blank_value'])
			&& $this->value == $data[$this->id.'_blank_value']) {
				$this->value = null;
				$this->show_blank_value = true;
		}

		if ($this->value === null)
			return;

		$value = str_replace(array('-', ' '), '', $this->value);

		if (!Validate_Finance_CreditCard::number($value)) {
			$message = Store::_('The %s field is not a valid card number. '.
				'Please ensure it is entered correctly.');

			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}

		$this->value = $value;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		if (!$this->visible)
			return;

		// add a hidden field to track how the widget was displayed
		if ($this->show_blank_value)
			$this->getForm()->addHiddenField(
				$this->id.'_blank_value', $this->blank_value);
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
