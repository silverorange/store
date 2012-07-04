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
class StoreSalesReportDetails extends AdminIndex
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
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/SalesReport/details.xml';
	}

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

		$this->ui->loadFromXML($this->getUiXml());

		$regions = $this->getRegions();
		$view = $this->ui->getWidget('index_view');

		// add dynamic columns to items view
		$this->appendRegionColumns($view, $regions);
	}

	// }}}
	// {{{ protected function appendRegionColumns()

	protected function appendRegionColumns(SwatTableView $view,
		StoreRegionWrapper $regions)
	{
		foreach ($regions as $region) {
			$view->appendColumn($this->getCreatedColumn($region));
			$view->appendColumn($this->getCancelledColumn($region));
			$view->appendColumn($this->getSubtotalColumn($region));
		}
	}

	// }}}
	// {{{ protected function getCreatedColumn()

	protected function getCreatedColumn($region)
	{
		$column = new SwatTableViewColumn('created_'.$region->id);
		$column->title = sprintf(Store::_('%s Created Orders'), $region->title);

		$renderer = new SwatNumericCellRenderer();

		$column->addRenderer($renderer);
		$column->addMappingToRenderer(
			$renderer,
			'created_'.$region->id,
			'value'
		);

		return $column;
	}

	// }}}
	// {{{ protected function getCancelledColumn()

	protected function getCancelledColumn($region)
	{
		$column = new SwatTableViewColumn('cancelled_'.$region->id);
		$column->title = sprintf(
			Store::_('%s Cancelled Orders'),
			$region->title
		);

		$renderer = new SwatNumericCellRenderer();

		$column->addRenderer($renderer);
		$column->addMappingToRenderer(
			$renderer,
			'cancelled_'.$region->id,
			'value'
		);

		return $column;
	}

	// }}}
	// {{{ protected function getSubtotalColumn()

	protected function getSubtotalColumn(StoreRegion $region)
	{
		$column = new SwatTableViewColumn('subtotal_'.$region->id);
		$column->title = sprintf(Store::_('%s Subtotal'), $region->title);

		$renderer = new SwatMoneyCellRenderer();
		$renderer->locale = $region->getFirstLocale()->id;

		$column->addRenderer($renderer);
		$column->addMappingToRenderer(
			$renderer,
			'subtotal_'.$region->id,
			'value'
		);

		$column->addMappingToRenderer(
			$renderer,
			'locale_id',
			'locale'
		);

		return $column;
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
		$regions   = $this->getRegions();
		$locale_id = $regions->getFirst()->getFirstLocale()->id;

		// create an array of days with default values
		$days = array();
		for ($i = 1; $i <= $this->start_date->getDaysInMonth(); $i++) {
			$day = new SwatDetailsStore();

			foreach ($regions as $region) {
				$day->{'created_'.$region->id}   = 0;
				$day->{'cancelled_'.$region->id} = 0;
				$day->{'subtotal_'.$region->id}  = 0;
			}

			$day->day       = $i;
			$day->locale_id = $locale_id;

			$days[$i] = $day;
		}

		// total row
		$sum = new SwatDetailsStore();

		foreach ($regions as $region) {
			$sum->{'created_'.$region->id}   = 0;
			$sum->{'cancelled_'.$region->id} = 0;
			$sum->{'subtotal_'.$region->id}  = 0;
		}

		$sum->day       = Store::_('Total');
		$sum->locale_id = $locale_id;

		// fill our array with values from the database if the values exist
		$rs = $this->queryOrderStats('createdate');
		foreach ($rs as $row) {
			$key = $row->day;

			$days[$key]->{'subtotal_'.$row->region} += $row->subtotal;
			$days[$key]->{'created_'.$row->region} = $row->num_orders;

			$sum->{'subtotal_'.$row->region} += $row->subtotal;
			$sum->{'created_'.$row->region} += $row->num_orders;
		}

		$rs = $this->queryOrderStats('cancel_date');
		foreach ($rs as $row) {
			$key = $row->day;

			$days[$key]->{'subtotal_'.$row->region} -= $row->subtotal;
			$days[$key]->{'cancelled_'.$row->region} = $row->num_orders;

			$sum->{'subtotal_'.$row->region} -= $row->subtotal;
			$sum->{'cancelled_'.$row->region} += $row->num_orders;
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
		$time_zone_name = $this->start_date->getTimezone()->getName();
		$instance_id = $this->app->getInstanceId();

		$sql = 'select count(Orders.id) as num_orders, Locale.region,
				%1$s as subtotal,
				extract(day from convertTZ(%2$s, %3$s)) as day,
				extract(month from convertTZ(%2$s, %3$s)) as month,
				extract(year from convertTZ(%2$s, %3$s)) as year
			from Orders
				inner join Locale on Orders.locale = Locale.id
			where
				extract(year from convertTZ(%2$s, %3$s)) = %4$s
				and extract(month from convertTZ(%2$s, %3$s)) = %5$s
				and Orders.instance %6$s %7$s
			group by Locale.region, year, month, day';

		$sql = sprintf(
			$sql,
			$this->getSubtotalSelectClause(),
			$date_field,
			$this->app->db->quote($time_zone_name, 'text'),
			$this->app->db->quote($this->start_date->getYear(), 'integer'),
			$this->app->db->quote($this->start_date->getMonth(), 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer')
		);

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getSubtotalSelectClause()

	protected function getSubtotalSelectClause()
	{
		return '(sum(item_total) + sum(surcharge_total))';
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
