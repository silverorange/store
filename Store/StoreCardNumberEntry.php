<?php

require_once 'Swat/SwatEntry.php';
require_once 'Validate/Finance/CreditCard.php';
require_once 'Store/dataobjects/StoreCardType.php';
require_once 'Store/dataobjects/StoreCardTypeWrapper.php';

/**
 * A widget for basic validation of a debit or credit card
 *
 * @package   Store
 * @copyright 2006-2015 silverorange
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

	// }}}
	// {{{ protected properties

	/**
	 * Selected card type as determined during the process step
	 *
	 * @var StoreCardType
	 *
	 * @see StoreCardNumberEntry::getCardType()
	 * @see StoreCardNumberEntry::processCardType()
	 */
	protected $card_type;

	/**
	 * Valid card types for this entry
	 *
	 * @var StoreCardTypeWrapper
	 *
	 * @see StoreCardNumberEntry::setCardTypes()
	 */
	protected $card_types;

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
		if ($this->isProcessed()) {
			return;
		}

		parent::process();

		$data = &$this->getForm()->getFormData();

		if (isset($data[$this->id.'_blank_value'])
			&& $this->value == $data[$this->id.'_blank_value']) {
				$this->value = null;
				$this->show_blank_value = true;
		}

		if ($this->value === null) {
			return;
		}

		// remove spaces and dashes from value
		$this->value = str_replace(array('-', ' '), '', $this->value);

		if (!Validate_Finance_CreditCard::number($this->value)) {
			$message = Store::_(
				'The %s field is not a valid card number. Please ensure '.
				'it is entered correctly.'
			);

			$this->addMessage(new SwatMessage($message, 'error'));
		}

		if (!$this->hasMessage() &&
			$this->card_types instanceof StoreCardTypeWrapper) {
			$this->processCardType();
		}
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible) {
			return;
		}

		parent::display();

		// add a hidden field to track how the widget was displayed
		if ($this->show_blank_value) {
			$this->getForm()->addHiddenField(
				$this->id.'_blank_value',
				$this->getBlankValue()
			);
		}
	}

	// }}}
	// {{{ public function setCardTypes()

	public function setCardTypes(StoreCardTypeWrapper $card_types)
	{
		$this->card_types = $card_types;
	}

	// }}}
	// {{{ public function getCardType()

	/**
	 * @return StoreCardType
	 */
	public function getCardType()
	{
		return $this->card_type;
	}

	// }}}
	// {{{ protected function processCardType()

	protected function processCardType()
	{
		$info = StoreCardType::getInfoFromCardNumber($this->value);
		$message = null;

		if ($info !== null) {
			$found = false;

			foreach ($this->card_types as $card_type) {
				if ($card_type->shortname == $info->shortname) {
					$found = true;
					$this->card_type = $card_type;
					break;
				}
			}

			if (!$found) {
				$message = sprintf(
					'Sorry, we don’t accept %s payments.',
					SwatString::minimizeEntities($info->description)
				);
			}
		} else {
			$message = 'Sorry, we don’t accept your card type.';
		}

		if ($message !== null) {
			$message = new SwatMessage(
				sprintf(
					'%s %s',
					$message,
					StoreCardType::getAcceptedCardTypesMessage(
						$this->card_types
					)
				),
				'error'
			);

			$this->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function getBlankValue()

	protected function getBlankValue()
	{
		$length = 16;
		$blank_value = str_repeat('●', $length);
		return $blank_value;
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
		$value = $this->value;

		if ($this->show_blank_value && $this->value === null) {
			$value = $this->getBlankValue();
		}

		return $value;
	}

	// }}}
}

?>
