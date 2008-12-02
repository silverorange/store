<?php

require_once 'Swat/SwatEntry.php';
require_once 'Validate/Finance/CreditCard.php';
require_once 'Store/dataobjects/StoreCardType.php';

/**
 * A widget for basic validation of a debit or credit card
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
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
	// {{{ protected properties

	/**
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	protected $card_type;

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

		$this->value = str_replace(array('-', ' '), '', $this->value);

		if (!Validate_Finance_CreditCard::number($this->value)) {
			$message = Store::_('The %s field is not a valid card number. '.
				'Please ensure it is entered correctly.');

			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}

		if (!$this->hasMessage() && $this->db !== null)
			$this->processCardType();
	}

	// }}}
	// {{{ protected function processCardType()

	protected function processCardType()
	{
		$info = StoreCardType::getInfoFromCardNumber($this->value);
		$message = null;

		if ($info !== null) {
			$class_name = SwatDBClassMap::get('StoreCardType');
			$type = new $class_name();
			$type->setDatabase($this->db);
			$found = $type->loadFromShortname($info->shortname);

			if ($found)
				$this->card_type = $type->id;
			else
				$message = sprintf('Sorry, we don’t accept %s payments.',
					SwatString::minimizeEntities($info->description));
		} else {
			$message = 'Sorry, we don’t accept your card type.';
		}

		if ($message !== null) {
			$message = new SwatMessage(sprintf('%s %s', $message,
				$this->getAcceptedCardTypesMessage()), SwatMessage::ERROR);

			$this->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function getAcceptedCardTypesMessage()

	protected function getAcceptedCardTypesMessage()
	{
		$types = SwatDB::getOptionArray($this->db,
			'CardType', 'title', 'shortname', 'title');

		if (count($types) > 2) {
			array_push($types, sprintf('and %s',
				array_pop($types)));

			$type_list = implode(', ', $types);
		} else {
			$type_list = implode(' and ', $types);
		}

		return sprintf('We accept %s.', $type_list);
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
	// {{{ public function setDatabase()

	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function getCardType()

	public function getCardType()
	{
		return $this->card_type;
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
