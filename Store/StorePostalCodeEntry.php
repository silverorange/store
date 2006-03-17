<?php

require_once 'Swat/SwatEntry.php';

/**
 * A widget for entering a postal code
 *
 * This widget validates and formats postal codes entered by users. To properly
 * validate a postal code, the widget needs to know the country. Set the
 * {@link StorePostalCodeEntry::$country} property to a known ISO-3611 code to
 * validate postal codes for a particular country.
 *
 * @package   store
 * @copyright 2006 silverorange
 */
class StorePostalCodeEntry extends SwatEntry
{
	/**
	 * The country to validate the postal code in
	 *
	 * This should be a valid ISO-3611 two-digit country code.
	 *
	 * @var string
	 */
	public $country;

	/**
	 * Processes this postal code entry widget
	 *
	 * The postal code is validated and formatted correctly.
	 */
	public function process()
	{
		parent::process();

		switch ($this->country) {
		case 'CA':
			$this->validateCA();
			break;
		case 'US':
			$this->validateUS();
			break;
		case 'UK':
			$this->validateUK();
			break;
		}
	}

	/**
	 * Validates a Canadian postal code
	 */
	private function validateCA()
	{
		$value = trim($this->value);

		if (strlen($value) == 0)
			return;

		// common mis-written/mis-typped letter translations taken from
		// Canada Post
		$trans = array(
			'o' => '0',
			'O' => '0',
			'D' => '0',
			'I' => '1',
			'l' => '1',
			'F' => '7',
			'f' => '7',
			'U' => '0',
			'u' => '0',
			'q' => '2',
			'Q' => '2'
		);

		$value = str_replace('-', '', $value);
		$value = str_replace(' ', '', $value);

		$value = strtoupper($value);
		$value = strtr($value, $trans);

		if (preg_match('/^[ABCEGHJ-NPRSTVXY]\d[A-Z]\d[A-Z]\d$/u', $value) == 0) {
			$message = new SwatMessage('The <strong>%s</strong> field must '.
				'be a valid Canadian postal code.', SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		}

		if (strlen($value) > 3)
			$value = substr($value, 0, 3).' '.substr($value, 3, 3);

		$this->value = $value;
	}

	/**
	 * Validates a United States ZIP Code
	 */
	private function validateUS()
	{
		$value = trim($this->value);

		if (strlen($value) == 0)
			return;

		// matches ZIP or ZIP+4 codes
		if (preg_match('/^\d{5}((-| )\d{4})?$/u', $value) == 0) {
			$message = new SwatMessage('The <strong>%s</strong> field must '.
				'be a valid US ZIP Code.', SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		}

		$this->value = $value;
	}

	/**
	 * Validates a United Kingdom postcode
	 */
	private function validateUK()
	{
		$value = trim($this->value);

		if (strlen($value) == 0)
			return;

		// taken from Wikipedia (http://en.wikipedia.org/wiki/UK_postcodes)
		$regex = '/^[A-PR-UWYZ]\d\d?\d[ABD-HJLNP-UW-Z]{2}|'.
			'[A-PR-UWYZ][A-HK-Y]\d\d?\d[ABD-HJLNP-UW-Z]{2}|'.
			'[A-PR-UWYZ]\d[A-HJKSTUW]\d[ABD-HJLNP-UW-Z]{2}|'.
			'[A-PR-UWYZ][A-HK-Y]\d[A-HJKRSTUW]\d[ABD-HJLNP-UW-Z]{2}|'.
			'GIR0AA$/u';

		if (preg_match($regex, $value) == 0) {
			$message = new SwatMessage('The <strong>%s</strong> field must '.
				'be a valid United Kingdom postcode.', SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		}

		$this->value = $value;
	}
}

?>
