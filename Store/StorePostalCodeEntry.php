<?php

require_once 'Swat/SwatEntry.php';
require_once 'Store.php';

/**
 * A widget for entering a postal code
 *
 * This widget validates and formats postal codes entered by users. To properly
 * validate a postal code, the widget needs to know the country. Set the
 * {@link StorePostalCodeEntry::$country} property to a known ISO-3611 code to
 * validate postal codes for a particular country.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePostalCodeEntry extends SwatEntry
{
	// {{{ public properties

	/**
	 * The country to validate the postal code in
	 *
	 * This should be a valid ISO-3611 two-digit country code.
	 *
	 * @var string
	 */
	public $country;

	/**
	 * An optional province or state to validate the postal code in
	 *
	 * This is a two letter abbreviation of the province or state. If the
	 * province or state is specified, postal codes will be validated in the
	 * province or state. Otherwise the postal code will just be validated
	 * by country.
	 *
	 * @var string
	 */
	public $provstate;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/store/styles/store-postal-code-entry.css',
			Store::PACKAGE_ID);

		$this->size = 10;
	}

	// }}}
	// {{{ public function process()

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
			$this->validateCA($this->provstate);
			break;
		case 'US':
			$this->validateUS($this->provstate);
			break;
		case 'UK':
			$this->validateUK($this->provstate);
			break;
		default: // No validation
			break;
		}
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
		$classes = parent::getCSSClassNames();
		$classes[] = 'store-postal-code-entry';
		$classes = array_merge($classes, $this->classes);
		return $classes;
	}

	// }}}
	// {{{ private function validateCA()

	/**
	 * Validates a Canadian postal code
	 */
	private function validateCA($province = null)
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
			$message = new SwatMessage(Store::_('The %s field is not a valid '.
				'Canadian postal code.'), SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		} elseif ($province !== null &&
			!$this->validateByProvince($value, $province)) {
			$message = new SwatMessage(Store::_('The %s field is not valid '.
				'for the selected province.'), SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		} elseif (strlen($value) > 3)
			$value = substr($value, 0, 3).' '.substr($value, 3, 3);

		$this->value = $value;
	}

	// }}}
	// {{{ private function validateUS()

	/**
	 * Validates a United States ZIP Code
	 */
	private function validateUS($state = null)
	{
		$value = trim($this->value);

		if (strlen($value) == 0)
			return;

		// matches ZIP or ZIP+4 codes
		if (preg_match('/^\d{5}((-| )\d{4})?$/u', $value) == 0) {
			$message = new SwatMessage(Store::_('The %s field is not a valid '.
				'US ZIP Code.'), SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		} elseif ($state !== null && !$this->validateByState($value, $state)) {
			$message = new SwatMessage(Store::_('The %s field is not valid '.
				'for the selected state.'), SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		} else {
			// correctly formatted. make sure ZIP+4 is separated by a dash.
			$value = str_replace(' ', '-', $value);
		}

		$this->value = $value;
	}

	// }}}
	// {{{ private function validateUK()

	/**
	 * Validates a United Kingdom postcode
	 */
	private function validateUK($county = null)
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
			$message = new SwatMessage(Store::_('The %s field is not a valid '.
				'United Kingdom postcode.'), SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		}

		$this->value = $value;
	}

	// }}}
	// {{{ public function validateByProvince()

	/**
	 * Validates a Canadian postal code by a province code
	 *
	 * @param string $code the postal code to validate.
	 * @param string $province the two letter abbreviation of the Canadian
	 *                          province to validate the postal code for.
	 *
	 * @return boolean true if the postal code is valid for the given province
	 *                  and false if it is not.
	 */
	public function validateByProvince($code, $province)
	{
		switch ($province) {
		case 'NU': // Nunavut
			$districts = array('X');
			break;
		case 'YT': // Yukon Territory
			$districts = array('Y');
			break;
		case 'SK': // Saskatchewan
			$districts = array('S');
			break;
		case 'QC': // Quebec
			$districts = array('G', 'H', 'J');
			break;
		case 'PE': // Prince Edward Island
			$districts = array('C');
			break;
		case 'ON': // Ontario
			$districts = array('M', 'K', 'N', 'L', 'P');
			break;
		case 'NS': // Nova Scotia
			$districts = array('B');
			break;
		case 'NT': // Northwest Territories
			$districts = array('X');
			break;
		case 'NL': // Newfoundland nad Labrador
			$districts = array('A');
			break;
		case 'NB': // New Brunswick
			$districts = array('E');
			break;
		case 'MB': // Manatoba
			$districts = array('R');
			break;
		case 'AB': // Alberta
			$districts = array('T');
			break;
		case 'BC': // British Columbia
			$districts = array('V');
			break;
		default: // Not Found
			$districts = array();
			break;
		}

		return in_array($code[0], $districts);
	}

	// }}}
	// {{{ public function validateByState()

	/**
	 * Validates a United States ZIP Code by a state code
	 *
	 * @param string $code the ZIP Code to validate.
	 * @param string $province the two letter FIPS 5-2 code of the American
	 *                          state to validate the ZIP Code for. {@link
	 *                          http://en.wikipedia.org/wiki/FIPS_state_code
	 *                          FIPS codes may be found on Wikipedia}.
	 *
	 * @return boolean true if the ZIP Code is valid for the given state and
	 *                  false if it is not.
	 */
	public function validateByState($code, $state)
	{
		/* 
		 * Start and end ZIP codes by state taken from Wikipedia:
		 * http://en.wikipedia.org/wiki/Image:ZIP_code_zones.png
		 *  and
		 * http://en.wikipedia.org/wiki/List_of_ZIP_Codes_in_the_United_States
		 *
		 * NOTE: Some codes overlap. Do not use this for reverse lookup of
		 *       states.
		 */
		switch ($state) {
		case 'PW': // Palau
		case 'FM': // Micronesia
		case 'MH': // Marshall Islands
		case 'MP': // North Marina Islands
		case 'GU': // Guam
			$ranges = array('969' => '969');
			break;
		case 'AS': // American Samoa
			$ranges = array('96799' => '96799');
			break;
		case 'AP': // American Forces (Pacific)
			$ranges = array('962' => '966');
			break;
		case 'WA': // Washington
			$ranges = array('980' => '994');
			break;
		case 'OR': // Oregon
			$ranges = array('97' => '97');
			break;
		case 'HI': // Hawii
			$ranges = array('967' => '968');
			break;
		case 'CA': // California
			$ranges = array('900' => '961');
			break;
		case 'AK': // Alaska
			$ranges = array('995' => '999');
			break;
		case 'WY': // Wyoming
			$ranges = array('820' => '831', '83414' => '83414');
			break;
		case 'UT': // Utah
			$ranges = array('84' => '84');
			break;
		case 'NM': // New Mexico
			$ranges = array('870' => '884');
			break;
		case 'NV': // Nevada
			$ranges = array('889' => '899');
			break;
		case 'ID': // Idaho
			$ranges = array('832' => '839');
			break;
		case 'CO': // Colorado
			$ranges = array('80' => '81');
			break;
		case 'AZ': // Arizona
			$ranges = array('85' => '86');
			break;
		case 'TX': // Texas
			$ranges = array('75' => '79', '885' => '885', '73301' => '73301',
				'73344' => '73344');

			break;
		case 'OK': // Oklahoma
			$ranges = array('73' => '74');
			break;
		case 'LA': // Louisiana
			$ranges = array('700' => '715');
			break;
		case 'AR': // Arkansas
			$ranges = array('716' => '729');
			break;
		case 'NE': // Nebraska
			$ranges = array('68' => '69');
			break;
		case 'MO': // Missouri
			$ranges = array('63' => '65');
			break;
		case 'KS': // Kansas
			$ranges = array('66' => '67');
			break;
		case 'IL': // Illinois
			$ranges = array('60' => '62');
			break;
		case 'WI': // Wisconsin
			$ranges = array('53' => '54');
			break;
		case 'SD': // South Dakota
			$ranges = array('57' => '57');
			break;
		case 'ND': // North Dakota
			$ranges = array('58' => '58');
			break;
		case 'MT': // Montana
			$ranges = array('59' => '59');
			break;
		case 'MN': // Minnesota
			$ranges = array('550' => '567');
			break;
		case 'IA': // Iowa
			$ranges = array('50' => '52');
			break;
		case 'OH': // Ohio
			$ranges = array('43' => '45');
			break;
		case 'MI': // Michigan
			$ranges = array('48' => '49');
			break;
		case 'KY': // Kentucky
			$ranges = array('400' => '427');
			break;
		case 'IN': // Indiana
			$ranges = array('46' => '47');
			break;
		case 'AA': // American Forces (Central and South America)
			$ranges = array('340' => '340');
			break;
		case 'TN': // Tennessee
			$ranges = array('370' => '385');
			break;
		case 'MS': // Mississippi
			$ranges = array('386' => '397');
			break;
		case 'GA': // Georgia
			$ranges = array('30' => '31', '398' => '398', '39901' => '39901');
			break;
		case 'FL': // Flordia
			$ranges = array('32' => '34');
			break;
		case 'AL': // Alabama
			$ranges = array('35' => '36');
			break;
		case 'WV': // West Virginia
			$ranges = array('247' => '269');
			break;
		case 'VA': // Virginia (partially overlaps with DC)
			$ranges = array('220' => '246', '200' => '200', '201' => '201');
			break;
		case 'SC': // South Carolina
			$ranges = array('29' => '29');
			break;
		case 'NC': // North Carolina
			$ranges = array('27' => '28');
			break;
		case 'MD': // Maryland
			$ranges = array('206' => '219');
			break;
		case 'DC': // District of Columbia
			$ranges = array('200' => '200', '202' => '205', '569' => '569');
			break;
		case 'PA': // Pennsylvania
			$ranges = array('150' => '196');
			break;
		case 'NY': // New York
			$ranges = array('10' => '14', '06390' => '06390',
				'00501' => '00501', '00544' => '00544');

			break;
		case 'DE': // Delaware
			$ranges = array('197' => '199');
			break;
		case 'VI': // Virgin Islands
			$ranges = array('008' => '008');
			break;
		case 'PR': // Puerto Rico
			$ranges = array('006' => '007', '009' => '009');
			break;
		case 'AE': // American Forces (Europe)
			$ranges = array('09' => '09');
			break;
		case 'VT': // Vermont
			$ranges = array('05' => '05');
			break;
		case 'RI': // Rhode Island
			$ranges = array('028' => '029');
			break;
		case 'NJ': // New Jersey
			$ranges = array('07' => '08');
			break;
		case 'NH': // New Hampshire
			$ranges = array('030' => '038');
			break;
		case 'MA': // Massachusetts
			$ranges = array('010' => '027', '05501' = > '05501',
				'05544' => '05544');

			break;
		case 'ME': // Maine
			$ranges = array('039' => '049');
			break;
		case 'CT': // Connecticut
			$ranges = array('06' => '06');
			break;
		case 'UM': // U.S. Minor Outlying Islands
		default: // Not Found
			$ranges = array('' => '');
			break;
		}

		// is code between some start and end range?
		$valid = false;
		foreach ($ranges as $start => $end) {
			$zip_start = substr($code, 0, strlen($start));
			if ((integer)$zip_start >= (integer)$start &&
				(integer)$zip_start <= (integer)$end) {
				$valid = true;
				break;
			}
		}

		return $valid;
	}

	// }}}
}

?>
