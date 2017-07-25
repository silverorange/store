<?php

/**
 * Displays sales summaries for a year by month
 *
 * @package   Store
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSalesReportIndex extends AdminIndex
{
	// {{{ protected properties

	/**
	 * Cache of regions used by getRegions()
	 *
	 * @var StoreRegionWrapper
	 */
	protected $regions = null;

	/**
	 * Current year of commission report
	 *
	 * @var integer
	 */
	protected $year;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/SalesReport/index.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());

		$regions = $this->getRegions();
		$view = $this->ui->getWidget('index_view');

		// add dynamic columns to items view
		$this->appendRegionColumns($view, $regions);

		$oldest_date_string = SwatDB::queryOne(
			$this->app->db,
			'select min(createdate) from Orders'
		);

		$today = new SwatDate();
		$today->setTimezone($this->app->default_time_zone);

		$order_date = new SwatDate($oldest_date_string);
		$order_date->setTimezone($this->app->default_time_zone);

		$pager = $this->ui->getWidget('pager');
		$pager->page_size = 1;
		$pager->total_records = $today->getYear() - $order_date->getYear() + 1;
		$pager->process();

		$this->year = $today->getYear() - $pager->current_record;
	}

	// }}}
	// {{{ protected function appendRegionColumns()

	protected function appendRegionColumns(SwatTableView $view,
		StoreRegionWrapper $regions) {
		$include_region_in_title = (count($regions) > 1);

		foreach ($regions as $region) {
			$created_column = new SwatTableViewColumn('created_'.$region->id);
			$created_column->title = sprintf(
				($include_region_in_title)
					? Store::_('%s Created Orders')
					: Store::_('Created Orders'),
				$region->title
			);

			$created_renderer = new SwatNumericCellRenderer();

			$created_column->addRenderer($created_renderer);
			$created_column->addMappingToRenderer(
				$created_renderer,
				'created_'.$region->id,
				'value'
			);

			$cancelled_column = new SwatTableViewColumn(
				'cancelled_'.$region->id
			);

			$cancelled_column->title = sprintf(
				($include_region_in_title)
					? Store::_('%s Cancelled Orders')
					: Store::_('Cancelled Orders'),
				$region->title
			);

			$cancelled_renderer = new SwatNumericCellRenderer();

			$cancelled_column->addRenderer($cancelled_renderer);
			$cancelled_column->addMappingToRenderer(
				$cancelled_renderer,
				'cancelled_'.$region->id,
				'value'
			);

			$subtotal_column = new SwatTableViewColumn('subtotal_'.$region->id);
			$subtotal_column->title = sprintf(
				($include_region_in_title)
					? Store::_('%s Subtotal')
					: Store::_('Subtotal'),
				$region->title
			);

			$subtotal_renderer = new SwatMoneyCellRenderer();
			$subtotal_renderer->locale = $region->getFirstLocale()->id;

			$subtotal_column->addRenderer($subtotal_renderer);
			$subtotal_column->addMappingToRenderer(
				$subtotal_renderer,
				'subtotal_'.$region->id,
				'value'
			);

			$subtotal_column->addMappingToRenderer(
				$subtotal_renderer,
				'locale_id',
				'locale'
			);

			$view->appendColumn($created_column);
			$view->appendColumn($cancelled_column);
			$view->appendColumn($subtotal_column);
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$start_date = new SwatDate();
		$start_date->setDate($this->year, 1, 1);

		$regions = $this->getRegions();
		$locale_id = $regions->getFirst()->getFirstLocale()->id;

		// create an array of months with default values
		$months = array();
		for ($i = 1; $i <= 12; $i++) {
			$key = $this->year.'-'.$i;

			$month = new SwatDetailsStore();

			foreach ($regions as $region) {
				$month->{'created_'.$region->id}   = 0;
				$month->{'cancelled_'.$region->id} = 0;
				$month->{'subtotal_'.$region->id}  = 0;
			}

			$month->date        = clone $start_date;
			$month->date_string = $key;
			$month->locale_id   = $locale_id;

			$months[$key] = $month;

			$start_date->setMonth($i + 1);
		}

		// fill our array with values from the database if the values exist
		$rs = $this->queryOrderStats('createdate');
		foreach ($rs as $row) {
			$key = $row->year.'-'.$row->month;

			$months[$key]->{'created_'.$row->region} = $row->num_orders;
			$months[$key]->{'subtotal_'.$row->region} += $row->subtotal;
		}

		$rs = $this->queryOrderStats('cancel_date');
		foreach ($rs as $row) {
			$key = $row->year.'-'.$row->month;

			$months[$key]->{'cancelled_'.$row->region} = $row->num_orders;
			$months[$key]->{'subtotal_'.$row->region} -= $row->subtotal;
		}

		// turn the array into a table model
		$store = new SwatTableStore();
		foreach ($months as $month) {
			$store->add($month);
		}

		return $store;
	}

	// }}}
	// {{{ protected function queryOrderStats()

	protected function queryOrderStats($date_field)
	{
		$sql = 'select count(Orders.id) as num_orders, Locale.region,
				sum(OrderCommissionTotalView.commission_total) as subtotal,
				extract(month from convertTZ(%1$s, %2$s)) as month,
				extract(year from convertTZ(%1$s, %2$s)) as year
			from Orders
				inner join Locale on Orders.locale = Locale.id
				inner join OrderCommissionTotalView on
					OrderCommissionTotalView.ordernum = Orders.id
			where
				extract(year from convertTZ(%1$s, %2$s)) = %3$s
				%4$s
			group by Locale.region, year, month';

		$sql = sprintf(
			$sql,
			$date_field,
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($this->year, 'integer'),
			$this->getInstanceWhereClause()
		);

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getInstanceWhereClause()

	protected function getInstanceWhereClause()
	{
		if ($this->app->isMultipleInstanceAdmin()) {
			return '';
		}

		$instance_id = $this->app->getInstanceId();

		return sprintf(
			'and Orders.instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer')
		);
	}

	// }}}
	// {{{ protected function getRegions()

	protected function getRegions()
	{
		if ($this->regions === null) {
			$sql = 'select Region.id, Region.title from Region
				order by Region.id';

			$this->regions = SwatDB::query(
				$this->app->db,
				$sql,
				SwatDBClassMap::get('StoreRegionWrapper')
			);
		}

		return $this->regions;
	}

	// }}}
}

?>
