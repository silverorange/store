<?php

require_once 'Swat/SwatDate.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Exchange rate table lookup class
 *
 * This class uses the tables provided by Federal Reserve Bank of
 * St. Louis FRED® (Federal Reserve Economic Data) database to return daily
 * exchange rate data.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreExchangeRateTable
{
	// {{{ protected properties

	/**
	 * Currency to convert from
	 *
	 * ISO 4217 currency code
	 *
	 * @var string
	 */
	protected $from_currency;

	/**
	 * Currency to convert to
	 *
	 * ISO 4217 currency code
	 *
	 * @var string
	 */
	protected $to_currency;

	/**
	 * Set the cut-off date for the list of exchange rates.
	 *
	 * The array returned from getRate() will begin on the cut-off
	 * date and contain values up until the present.
	 *
	 * @var SwatDate
	 */
	protected $cut_off_date = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new exchange rate table
	 *
	 * @param $from_currency Currency to convert from 
	 * @param $to_currency Currency to convert to 
	 * @param SwatDate $date The date of the cut-off 
	 */
	public function __construct($from_currency, $to_currency,
		SwatDate $cut_off_date = null)
	{
		$this->from_currency = $from_currency;
		$this->to_currency = $to_currency;
		$this->cut_off_date = $cut_off_date;
	}

	// }}}

	// build phase
	// {{{ public function getRate()

	/**
	 * Get an exchange rate
	 *
	 * Returns the exchange rate for a given day. If no date is set,
	 * returns the most recent exchange rate. 
	 *
	 * @param SwatDate $date Date to return the exchange rate for (optional) 
	 *
	 * @return float The exchange rate for the given date.
	 */
	public function getRate($date = null)
	{
		static $exchange_rates = array();

		if (empty($exchange_rates)) {
			$exchange_data = file($this->getUrl());

			// start looking from most recent exchange rates
			$exchange_data = array_reverse($exchange_data);
			$expression = '/(\d{4}-\d\d-\d\d)  (\d\.\d{4})/u';

			foreach ($exchange_data as $line) {
				if (preg_match($expression, $line, $regs) === 1) {
					$exchange_rates[$regs[1]] = floatval($regs[2]);

					// only get exchange rate data for dates after site launch
					$exchange_date = new SwatDate($regs[1]);
					// rates are noon buying rates in New York City
					$exchange_date->setHour(12);
					$exchange_date->setTZbyID('America/New_York');

					if ($this->cut_off_date !== null &&
						SwatDate::compare($exchange_date,
						$this->cut_off_date) < 0)
						break;
				}
			}
		}

		if (empty($exchange_rates))
			return null;

		if ($date !== null) {
			$day = clone $date;

			for ($i = 0; $i < 7; $i++) {
				$key = $day->format('%Y-%m-%d');

				if (array_key_exists($key, $exchange_rates))
					return $exchange_rates[$key];

				$day->addSpan(new Date_Span(array(1, 0, 0, 0)));
			}
		}

		return end($exchange_rates);
	}

	// }}}
	// {{{ private function getUrl()

	private function getUrl()
	{
		$conversion = $this->from_currency.'_'.$this->to_currency;

		$filename = null;

		switch ($conversion) {
		case 'USD_CAD':
			$filename = 'DEXCAUS';
			break;
		case 'GBP_USD':
			$filename = 'DEXUSUK';
			break;
		case 'EUR_USD':
			$filename = 'DEXUSEU';
			break;
		}

		if ($filename === null)
			throw new StoreException('The currency conversion requested is '.
				'not supported.');

		return sprintf('http://research.stlouisfed.org/fred2/data/%s.txt',
			$filename);
	}

	// }}}
}

?>
