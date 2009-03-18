<?php

require_once 'Swat/SwatEntry.php';
require_once 'Store/dataobjects/StoreCardType.php';

/**
 * A widget for basic validation of a credit card verification value
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCardVerificationValueEntry extends SwatEntry
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
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * @var StoreCardType
	 */
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
		$this->size = 4;
	}

	// }}}
	// {{{ public function setDatabase()

	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function setCardType()

	public function setCardType(StoreCardType $card_type)
	{
		$this->card_type = $card_type;
	}

	// }}}
	// {{{ public function getBlankValue()

	public function getBlankValue()
	{
		$length = $this->card_type->getCardVerificationValueLength();
		$blank_value = str_repeat('*', $length);

		return $blank_value;
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		if ($this->isProcessed())
			return;

		parent::process();

		$data = &$this->getForm()->getFormData();

		if (isset($data[$this->id.'_blank_value'])
			&& $this->value == $data[$this->id.'_blank_value']) {
				$this->value = null;
				$this->show_blank_value = true;
		}

		if ($this->value === null)
			return;

		if (!$this->hasMessage())
			$this->validate();
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
				$this->id.'_blank_value', $this->getBlankValue());
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		$message = null;

		// make sure it's all numeric
		if (preg_match('/\D/', $this->value) == 1) {
			$message = new SwatMessage(Store::_('The %s field must be a '.
				'number.', 'error'));
		}

		if ($this->card_type !== null) {
			$length = $this->card_type->getCardVerificationValueLength();
			if (strlen($this->value) != $length) {
				$message_content = sprintf(Store::_('The %%s field '.
					'for %s %s%s%s card must be a number %s digits long.'),
					($this->card_type->shortname == 'amex') ?
						Store::_('an') : Store::_('a'),
					'<strong>',
					$this->card_type->title,
					'</strong>',
					$this->card_type->getCardVerificationValueLength());

				$message = new SwatMessage($message_content, 'error');
				$message->content_type = 'text/xml';
			}
		}

		if ($message !== null)
			$this->addMessage($message);
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
			return $this->getBlankValue();
		else
			return $this->value;
	}

	// }}}
}

?>
