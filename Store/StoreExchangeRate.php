<?php

require_once 'Swat/SwatDate.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Exchange rate lookup class
 *
 * This class uses the tables provided by Federal Reserve Bank of
 * St. Louis FRED® (Federal Reserve Economic Data) database to return daily
 * exchange rate data.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreExchangeRate
{
	// {{{ protected properties

	/**
	 * The date the website launched
	 *
	 * @var SwatDate
	 */
	protected $cutoff_date = null;

	/**
	 * Exchange rate series id
	 *
	 * @var string
	 */
	protected $series_id;

	// }}}

	// build phase
	// {{{ public function setCutoffDate()

	/**
	 * Set the cut-off date for the list of exchange rates.
	 *
	 * The array returned from getExchangeRate() will begin on the cut-off
	 * date and contain values up until the present.
	 *
	 * @param SwatDate $date The date of the cut-off 
	 */
	public function setCutoffDate(SwatDate $date)
	{
		$this->cutoff_date = $date;
	}

	// }}}
	// {{{ public function setSeriesId()

	/**
	 * Set the series id for the exchange rate you wish to calculate.
	 *
	 * The series id specifies what exchange rate is calculated. See {@link
	 * http://research.stlouisfed.org/fred2/categories/15} for a full list
	 * of possible ids.
	 *
	 * @param SwatDate $date The date of the cut-off 
	 */
	public function setSeriesId($series_id)
	{
		$this->series_id = $series_id;
	}

	// }}}
	// {{{ public function getExchangeRate()

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
	public function getExchangeRate($date = null)
	{
		static $exchange_rates = array();

		if ($this->series_id === null)
			throw new StoreException('You must specify an exchange rate series id.');

		if (empty($exchange_rates)) {
			$exchange_data =
				file(sprintf('http://research.stlouisfed.org/fred2/data/%s.txt',
					$this->series_id));

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

					if ($this->cutoff_date !== null &&
						SwatDate::compare($exchange_date,
						$this->cutoff_date) < 0)
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
}

?>
