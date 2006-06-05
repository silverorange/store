<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * Cell renderer for displaying payment details for an order
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePaymentCellRenderer extends SwatCellRenderer
{
	/**
	 * Whether or not this payment was made with a credit card
	 *
	 * @var boolean
	 */
	public $is_credit_card = true;

	/**
	 * The title of the method used for this payment
	 *
	 * @var string
	 */
	public $method_title;

	// credit card specific fields

	/**
	 * The format string used for displaying credit cards
	 *
	 * Use a different format string for displaying masked numbers.
	 *
	 * @var string
	 */
	public $credit_card_format;

	/**
	 * The expiry date of the credit card used
	 *
	 * @var SwatDate
	 */
	public $credit_card_expiry_date;

	/**
	 * The credit card number used
	 *
	 * This may be either a full credit card number or a partial number.
	 *
	 * @var string
	 */
	public $credit_card_number;

	public function render()
	{
		if ($this->is_credit_card)
			$this->renderCreditCard();
		else
			$this->renderCashOrCheque();
	}

	protected function renderCreditCard()
	{
		$br_tag = new SwatHtmlTag('br');

		if ($this->method_title !== null) {
			// TODO: in the future we may want to display an image logo here.
			echo $this->method_title;
			$br_tag->display();
		}

		$format = ($this->credit_card_format === null) ?
			'**** **** **** ####' : $this->credit_card_format;

		$code_tag = new SwatHtmlTag('code');
		$code_tag->setContent(SwatString::minimizeEntities(
			StorePaymentMethod::creditcardFormat($this->credit_card_number,
			$format)));

		$code_tag->display();

		$br_tag->display();

		if ($this->credit_card_expiry_date instanceof SwatDate)
			echo $this->credit_card_expiry_date->format(SwatDate::DF_CC_MY);
	}

	protected function renderCashOrCheque()
	{
		if ($this->method_title !== null)
			echo $this->method_title;
	}
}

?>
