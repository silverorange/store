<?php

/**
 * A widget for entering a postal code.
 *
 * This widget validates and formats postal codes entered by users. To properly
 * validate a postal code, the widget needs to know the country. Set the
 * {@link StorePostalCodeEntry::$country} property to a known ISO-3611 code to
 * validate postal codes for a particular country.
 *
 * @copyright 2006-2016 silverorange
 */
class StorePostalCodeEntry extends SwatEntry
{
    /**
     * The country to validate the postal code in.
     *
     * This should be a valid ISO-3611 two-digit country code.
     *
     * @var string
     */
    public $country;

    /**
     * An optional province or state to validate the postal code in.
     *
     * This is a two letter abbreviation of the province or state. If the
     * province or state is specified, postal codes will be validated in the
     * province or state. Otherwise the postal code will just be validated
     * by country.
     *
     * @var string
     */
    public $provstate;

    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->size = 10;
    }

    /**
     * Processes this postal code entry widget.
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

    /**
     * Gets the array of CSS classes that are applied to this entry widget.
     *
     * @return array the array of CSS classes that are applied to this entry
     *               widget
     */
    protected function getCSSClassNames()
    {
        $classes = parent::getCSSClassNames();
        $classes[] = 'store-postal-code-entry';

        return array_merge($classes, $this->classes);
    }

    /**
     * Validates a Canadian postal code.
     *
     * @param mixed|null $province
     */
    private function validateCA($province = null)
    {
        $value = trim($this->value);

        if ($value == '') {
            return;
        }

        // common mis-written/mis-typped letter translations taken from
        // Canada Post
        $trans = [
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
            'Q' => '2',
        ];

        $value = str_replace('-', '', $value);
        $value = str_replace(' ', '', $value);

        $value = mb_strtoupper($value);
        $value = strtr($value, $trans);

        if (preg_match('/^[ABCEGHJ-NPRSTVXY]\d[A-Z]\d[A-Z]\d$/u', $value) == 0) {
            $message = new SwatMessage(Store::_('The %s field is not a valid ' .
                'Canadian postal code.'), SwatMessage::ERROR);

            $message->content_type = 'text/xml';
            $this->addMessage($message);
        } elseif ($province !== null
            && !$this->validateByProvince($value, $province)) {
            $message = new SwatMessage(Store::_('The %s field is not valid ' .
                'for the selected province.'), SwatMessage::ERROR);

            $message->content_type = 'text/xml';
            $this->addMessage($message);
        } elseif (mb_strlen($value) > 3) {
            $value = mb_substr($value, 0, 3) . ' ' . mb_substr($value, 3, 3);
        }

        $this->value = $value;
    }

    /**
     * Validates a United States ZIP Code.
     *
     * @param mixed|null $state
     */
    private function validateUS($state = null)
    {
        $value = trim($this->value);

        if ($value == '') {
            return;
        }

        // matches ZIP or ZIP+4 codes
        if (preg_match('/^\d{5}((-| )?\d{4})?$/u', $value) == 0) {
            $message = new SwatMessage(Store::_('The %s field is not a valid ' .
                'US ZIP Code.'), SwatMessage::ERROR);

            $message->content_type = 'text/xml';
            $this->addMessage($message);
        } elseif ($state !== null && !$this->validateByState($value, $state)) {
            $message = new SwatMessage(Store::_('The %s field is not valid ' .
                'for the selected state.'), SwatMessage::ERROR);

            $message->content_type = 'text/xml';
            $this->addMessage($message);
        } else {
            // correctly formatted. make sure ZIP+4 is separated by a dash.
            $value = str_replace(' ', '-', $value);
        }

        $this->value = $value;
    }

    /**
     * Validates a United Kingdom postcode.
     *
     * @param mixed|null $county
     */
    private function validateUK($county = null)
    {
        $value = trim($this->value);

        if ($value == '') {
            return;
        }

        // taken from Wikipedia (http://en.wikipedia.org/wiki/UK_postcodes)
        $regex = '/^[A-PR-UWYZ]\d\d?\d[ABD-HJLNP-UW-Z]{2}|' .
            '[A-PR-UWYZ][A-HK-Y]\d\d?\d[ABD-HJLNP-UW-Z]{2}|' .
            '[A-PR-UWYZ]\d[A-HJKSTUW]\d[ABD-HJLNP-UW-Z]{2}|' .
            '[A-PR-UWYZ][A-HK-Y]\d[A-HJKRSTUW]\d[ABD-HJLNP-UW-Z]{2}|' .
            'GIR0AA$/u';

        if (preg_match($regex, $value) == 0) {
            $message = new SwatMessage(Store::_('The %s field is not a valid ' .
                'United Kingdom postcode.'), SwatMessage::ERROR);

            $message->content_type = 'text/xml';
            $this->addMessage($message);
        }

        $this->value = $value;
    }

    /**
     * Validates a Canadian postal code by a province code.
     *
     * @param string $code     the postal code to validate
     * @param string $province the two letter abbreviation of the Canadian
     *                         province to validate the postal code for
     *
     * @return bool true if the postal code is valid for the given province
     *              and false if it is not
     */
    public function validateByProvince($code, $province)
    {
        switch ($province) {
            case 'NU': // Nunavut
                $districts = ['X'];
                break;

            case 'YT': // Yukon Territory
                $districts = ['Y'];
                break;

            case 'SK': // Saskatchewan
                $districts = ['S'];
                break;

            case 'QC': // Quebec
                $districts = ['G', 'H', 'J'];
                break;

            case 'PE': // Prince Edward Island
                $districts = ['C'];
                break;

            case 'ON': // Ontario
                $districts = ['M', 'K', 'N', 'L', 'P'];
                break;

            case 'NS': // Nova Scotia
                $districts = ['B'];
                break;

            case 'NT': // Northwest Territories
                $districts = ['X0', 'X1'];
                break;

            case 'NU': // Nunavut
                $districts = ['X0A', 'X0B', 'X0C'];
                break;

            case 'NL': // Newfoundland and Labrador
                $districts = ['A'];
                break;

            case 'NB': // New Brunswick
                $districts = ['E'];
                break;

            case 'MB': // Manatoba
                $districts = ['R'];
                break;

            case 'AB': // Alberta
                $districts = ['T'];
                break;

            case 'BC': // British Columbia
                $districts = ['V'];
                break;

            default: // Not Found
                $districts = [];
                break;
        }

        return count(
            array_filter(
                $districts,
                function ($district) use ($code) {
                    return strncmp(
                        $district,
                        $code,
                        // strncmp does binary comparison
                        mb_strlen($district, '8bit')
                    ) === 0;
                }
            )
        ) > 0;
    }

    /**
     * Validates a United States ZIP Code by a state code.
     *
     * @param string $code  the ZIP Code to validate
     * @param mixed  $state
     *
     * @return bool true if the ZIP Code is valid for the given state and
     *              false if it is not
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
                $ranges = ['969' => '969'];
                break;

            case 'AS': // American Samoa
                $ranges = ['96799' => '96799'];
                break;

            case 'AP': // American Forces (Pacific)
                $ranges = ['962' => '966'];
                break;

            case 'WA': // Washington
                $ranges = ['980' => '994'];
                break;

            case 'OR': // Oregon
                $ranges = ['97' => '97'];
                break;

            case 'HI': // Hawii
                $ranges = ['967' => '968'];
                break;

            case 'CA': // California
                $ranges = ['900' => '961'];
                break;

            case 'AK': // Alaska
                $ranges = ['995' => '999'];
                break;

            case 'WY': // Wyoming
                $ranges = ['820' => '831', '83414' => '83414'];
                break;

            case 'UT': // Utah
                $ranges = ['84' => '84'];
                break;

            case 'NM': // New Mexico
                $ranges = ['870' => '884'];
                break;

            case 'NV': // Nevada
                $ranges = ['889' => '899'];
                break;

            case 'ID': // Idaho
                $ranges = ['832' => '839'];
                break;

            case 'CO': // Colorado
                $ranges = ['80' => '81'];
                break;

            case 'AZ': // Arizona
                $ranges = ['85' => '86'];
                break;

            case 'TX': // Texas
                $ranges = ['75' => '79', '885' => '885', '73301' => '73301',
                    '73344'     => '73344'];

                break;

            case 'OK': // Oklahoma
                $ranges = ['73' => '74'];
                break;

            case 'LA': // Louisiana
                $ranges = ['700' => '715'];
                break;

            case 'AR': // Arkansas
                $ranges = ['716' => '729'];
                break;

            case 'NE': // Nebraska
                $ranges = ['68' => '69'];
                break;

            case 'MO': // Missouri
                $ranges = ['63' => '65'];
                break;

            case 'KS': // Kansas
                $ranges = ['66' => '67'];
                break;

            case 'IL': // Illinois
                $ranges = ['60' => '62'];
                break;

            case 'WI': // Wisconsin
                $ranges = ['53' => '54'];
                break;

            case 'SD': // South Dakota
                $ranges = ['57' => '57'];
                break;

            case 'ND': // North Dakota
                $ranges = ['58' => '58'];
                break;

            case 'MT': // Montana
                $ranges = ['59' => '59'];
                break;

            case 'MN': // Minnesota
                $ranges = ['550' => '567'];
                break;

            case 'IA': // Iowa
                $ranges = ['50' => '52'];
                break;

            case 'OH': // Ohio
                $ranges = ['43' => '45'];
                break;

            case 'MI': // Michigan
                $ranges = ['48' => '49'];
                break;

            case 'KY': // Kentucky
                $ranges = ['400' => '427'];
                break;

            case 'IN': // Indiana
                $ranges = ['46' => '47'];
                break;

            case 'AA': // American Forces (Central and South America)
                $ranges = ['340' => '340'];
                break;

            case 'TN': // Tennessee
                $ranges = ['370' => '385'];
                break;

            case 'MS': // Mississippi
                $ranges = ['386' => '397'];
                break;

            case 'GA': // Georgia
                $ranges = ['30' => '31', '398' => '398', '39901' => '39901'];
                break;

            case 'FL': // Flordia
                $ranges = ['32' => '34'];
                break;

            case 'AL': // Alabama
                $ranges = ['35' => '36'];
                break;

            case 'WV': // West Virginia
                $ranges = ['247' => '269'];
                break;

            case 'VA': // Virginia (partially overlaps with DC)
                $ranges = ['220' => '246', '200' => '201'];
                break;

            case 'SC': // South Carolina
                $ranges = ['29' => '29'];
                break;

            case 'NC': // North Carolina
                $ranges = ['27' => '28'];
                break;

            case 'MD': // Maryland
                $ranges = ['206' => '219'];
                break;

            case 'DC': // District of Columbia
                $ranges = ['200' => '200', '202' => '205', '569' => '569'];
                break;

            case 'PA': // Pennsylvania
                $ranges = ['150' => '196'];
                break;

            case 'NY': // New York
                $ranges = ['10' => '14', '06390' => '06390',
                    '00501'     => '00501', '00544' => '00544'];

                break;

            case 'DE': // Delaware
                $ranges = ['197' => '199'];
                break;

            case 'VI': // Virgin Islands
                $ranges = ['008' => '008'];
                break;

            case 'PR': // Puerto Rico
                $ranges = ['006' => '007', '009' => '009'];
                break;

            case 'AE': // American Forces (Europe)
                $ranges = ['09' => '09'];
                break;

            case 'VT': // Vermont
                $ranges = ['05' => '05'];
                break;

            case 'RI': // Rhode Island
                $ranges = ['028' => '029'];
                break;

            case 'NJ': // New Jersey
                $ranges = ['07' => '08'];
                break;

            case 'NH': // New Hampshire
                $ranges = ['030' => '038'];
                break;

            case 'MA': // Massachusetts
                $ranges = ['010' => '027', '05501' => '05501',
                    '05544'      => '05544'];

                break;

            case 'ME': // Maine
                $ranges = ['039' => '049'];
                break;

            case 'CT': // Connecticut
                $ranges = ['06' => '06'];
                break;

            case 'UM': // U.S. Minor Outlying Islands
            default: // Not Found
                $ranges = ['' => ''];
                break;
        }

        // truncate code if longer than 5 characters
        if (mb_strlen($code) > 5) {
            $code = mb_substr($code, 0, 5);
        }

        // prepend code with zeros if shorter than 5 characters
        if (mb_strlen($code) < 5) {
            $code = str_repeat('0', 5 - mb_strlen($code)) . $code;
        }

        // is code between some start and end range?
        $valid = false;
        foreach ($ranges as $start => $end) {
            $zip_start = mb_substr($code, 0, mb_strlen($start));
            if ((int) $zip_start >= (int) $start
                && (int) $zip_start <= (int) $end) {
                $valid = true;
                break;
            }
        }

        return $valid;
    }
}
