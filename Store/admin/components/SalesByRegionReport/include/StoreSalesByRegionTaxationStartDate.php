<?php

/**
 * Object to get the start date on the sales by region report.
 *
 * These reports are for US tax savings on intenrational sales. This
 * law didn't start applying to the following date.
 *
 * @package   Store
 * @copyright 2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSalesByRegionTaxationStartDate
{
	// {{{ protected properties

	/**
	 * @var SwatDate
	 */
	protected $start_date = false;

	/**
	 * @var AdminApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(AdminApplication $app)
	{
		$this->app = $app;
		$this->initStartDate();
	}

	// }}}
	// {{{ public function getDate()

	public function getDate()
	{
		return $this->start_date;
	}

	// }}}
	// {{{ public function getWarningMessage()

	public function getWarningMessage()
	{
		$start_date = clone $this->start_date;
		$start_date->setTimezone($this->app->default_time_zone);

		$message = new SwatMessage(
			Store::_('This report is for US taxation purposes only.')
		);

		$message->secondary_content = sprintf(
			Store::_(
				'It includes all sales from %s onwards. Any sales prior to '.
				'the date fall outside the tax laws this report is used for '.
				'and are explicitly excluded.'
			),
			$start_date->formatLikeIntl(SwatDate::DF_DATE)
		);

		return $message;
	}

	// }}}
	// {{{ public function getTitlePatternFromDate()

	public function getTitlePatternFromDate(SwatDate $date)
	{
		$now = new SwatDate();
		$now->setTimezone($this->app->default_time_zone);
		$start_date = clone $this->start_date;
		$start_date->setTimezone($this->app->default_time_zone);

		$title_pattern = '%s';
		if ($date->getYear() === $start_date->getYear()) {
			$title_pattern.= sprintf(
				' from %s',
				$this->start_date->formatLikeIntl('MMM d')
			);
		}

		if ($date->getYear() === $now->getYear()) {
			$title_pattern.= ' (YTD)';
		}

		return $title_pattern;
	}

	// }}}
	// {{{ protected function initStartDate()

	protected function initStartDate()
	{
		// These reports are for US tax savings on intenrational sales. This
		// law didn't start applying to the following date.
		$this->start_date = new SwatDate();
		$this->start_date->setTimezone($this->app->default_time_zone);
		$this->start_date->setDate(2015, 4, 14);
		$this->start_date->setTime(0, 0, 0);
		$this->start_date->toUTC();
	}

	// }}}
}

?>
