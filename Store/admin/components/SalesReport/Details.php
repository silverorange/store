<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';

/**
 * Displays sales for a single month
 *
 * @package   Store
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SalesReportDetails extends AdminIndex
{
	// {{{ protected properties

	/**
	 * The starting date for this report
	 *
	 * @var SwatDate
	 */
	protected $start_date;

	/**
	 * Cache of regions used by getRegions()
	 *
	 * @var StoreRegionWrapper
	 */
	protected $regions = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$id = SiteApplication::initVar('id');
		$parts = explode('-', $id);
		if (count($parts) != 2) {
			throw new AdminNotFoundException(sprintf(
				'Unable to load commission report with id of “%s”', $id));
		}

		$this->start_date = new SwatDate('now', $this->app->default_time_zone);
		if ($this->start_date->setDate($parts[0], $parts[1], 1) === false) {
			throw new AdminNotFoundException(sprintf(
				'Unable to load commission report with id of “%s”', $id));
		}

		$this->ui->loadFromXML(dirname(__FILE__).'/details.xml');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$report_title = $this->start_date->formatLikeIntl(SwatDate::DF_MY);

		// set frame title
		$index_frame = $this->ui->getWidget('index_frame');
		$index_frame->subtitle = $report_title;

		// and navbar entry
		$this->layout->navbar->createEntry($report_title);
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$locale_id = $this->getRegions()->getFirst()->getFirstLocale()->id;

		// create an array of days with default values
		$days = array();
		for ($i = 1; $i <= $this->start_date->getDaysInMonth(); $i++) {
			$day = new SwatDetailsStore();
			$day->day       = $i;
			$day->created   = 0;
			$day->cancelled = 0;
			$day->subtotal  = 0;
			$day->locale_id = $locale_id;

			$days[$i] = $day;
		}

		$sum = new SwatDetailsStore();
		$sum->day       = 'Total'; // TODO: should this be translated
		$sum->created   = 0;
		$sum->cancelled = 0;
		$sum->subtotal  = 0;
		$sum->locale_id = $locale_id;

		// fill our array with values from the database if the values exist
		$rs = $this->queryOrderStats('createdate');
		foreach ($rs as $row) {
			$key = $row->day;

			$days[$key]->subtotal = $row->subtotal;
			$sum->subtotal += $row->subtotal;

			$days[$key]->created = $row->num_orders;
			$sum->created += $row->num_orders;
		}

		$rs = $this->queryOrderStats('cancel_date');
		foreach ($rs as $row) {
			$key = $row->day;

			$days[$key]->subtotal-= $row->subtotal;
			$sum->subtotal-= $row->subtotal;

			$days[$key]->cancelled = $row->num_orders;
			$sum->cancelled += $row->num_orders;
		}

		// turn the array into a table model
		$store = new SwatTableStore();
		foreach ($days as $day) {
			$store->add($day);
		}

		$store->add($sum);

		return $store;
	}

	// }}}
	// {{{ protected function queryOrderStats()

	protected function queryOrderStats($date_field)
	{
		$sql = 'select count(Orders.id) as num_orders, Locale.region,
				(sum(item_total) + sum(surcharge_total)
					- sum(promotion_total)) as subtotal,
				extract(day from convertTZ(%1$s, %2$s)) as day,
				extract(month from convertTZ(%1$s, %2$s)) as month,
				extract(year from convertTZ(%1$s, %2$s)) as year
			from Orders
				inner join Locale on Orders.locale = Locale.id
			where
				extract(year from convertTZ(%1$s, %2$s)) = %3$s
				and extract(month from convertTZ(%1$s, %2$s)) = %4$s
			group by Locale.region, year, month, day';

		$time_zone_name = $this->start_date->getTimezone()->getName();
		$sql = sprintf($sql,
			$date_field,
			$this->app->db->quote($time_zone_name, 'text'),
			$this->app->db->quote($this->start_date->getYear(), 'integer'),
			$this->app->db->quote($this->start_date->getMonth(), 'integer'));

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getRegions()

	protected function getRegions()
	{
		if ($this->regions === null) {
			$sql = 'select id, title from Region order by id';
			$this->regions = SwatDB::query($this->app->db, $sql,
				SwatDBClassMap::get('StoreRegionWrapper'));
		}

		return $this->regions;
	}

	// }}}
}

?>
