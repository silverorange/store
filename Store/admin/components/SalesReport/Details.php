<?php

/**
 * Displays sales for a single month
 *
 * @package   Store
 * @copyright 2011-2016 silverorange
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

	protected $display_shipping = false;

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
		StoreRegionWrapper $regions) {
		$show_region = count($regions) > 1;

		foreach ($regions as $region) {
			$view->appendColumn(
				$this->getCreatedColumn($region, $show_region));

			$view->appendColumn(
				$this->getCancelledColumn($region, $show_region));

			$view->appendColumn(
				$this->getSubtotalColumn($region, $show_region));

			if ($this->display_shipping) {
				$view->appendColumn(
					$this->getShippingColumn($region, $show_region));
			}
		}
	}

	// }}}
	// {{{ protected function getCreatedColumn()

	protected function getCreatedColumn($region, $show_title = true)
	{
		$column = new SwatTableViewColumn('created_'.$region->id);
		$column->title = sprintf(Store::_('%sCreated Orders'),
			($show_title) ? $region->title.' ' : '');

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

	protected function getCancelledColumn($region, $show_title = true)
	{
		$column = new SwatTableViewColumn('cancelled_'.$region->id);
		$column->title = sprintf(
			Store::_('%sCancelled Orders'),
			($show_title) ? $region->title.' ' : '');

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

	protected function getSubtotalColumn(StoreRegion $region,
		$show_title = true) {
		$column = new SwatTableViewColumn('subtotal_'.$region->id);
		$column->title = sprintf(Store::_('%sSubtotal'),
			($show_title) ? $region->title.' ' : '');

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
	// {{{ protected function getShippingColumn()

	protected function getShippingColumn(StoreRegion $region,
		$show_title = true) {
		$column = new SwatTableViewColumn('shipping_'.$region->id);
		$column->title = sprintf(Store::_('%sShipping'),
			($show_title) ? $region->title.' ' : '');

		$renderer = new SwatMoneyCellRenderer();
		$renderer->locale = $region->getFirstLocale()->id;

		$column->addRenderer($renderer);
		$column->addMappingToRenderer(
			$renderer,
			'shipping_'.$region->id,
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
				$day->{'shipping_'.$region->id}  = 0;
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
			$sum->{'shipping_'.$region->id}  = 0;
		}

		$sum->day       = Store::_('Total');
		$sum->locale_id = $locale_id;

		// fill our array with values from the database if the values exist
		$rs = $this->queryOrderStats('createdate');
		foreach ($rs as $row) {
			$key = $row->day;

			$days[$key]->{'subtotal_'.$row->region} += $row->subtotal;
			$days[$key]->{'shipping_'.$row->region} += $row->shipping;
			$days[$key]->{'created_'.$row->region} = $row->num_orders;

			$sum->{'subtotal_'.$row->region} += $row->subtotal;
			$sum->{'shipping_'.$row->region} += $row->shipping;
			$sum->{'created_'.$row->region} += $row->num_orders;
		}

		$rs = $this->queryOrderStats('cancel_date');
		foreach ($rs as $row) {
			$key = $row->day;

			$days[$key]->{'subtotal_'.$row->region} -= $row->subtotal;
			$days[$key]->{'shipping_'.$row->region} -= $row->shipping;
			$days[$key]->{'cancelled_'.$row->region} = $row->num_orders;

			$sum->{'subtotal_'.$row->region} -= $row->subtotal;
			$sum->{'cancelled_'.$row->region} += $row->num_orders;

			// don't subtract shipping as it's usually not refunded
			//$sum->{'shipping_'.$row->region} -= $row->shipping;
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

		$sql = 'select count(Orders.id) as num_orders, Locale.region,
				sum(OrderCommissionTotalView.commission_total) as subtotal,
				%1$s as shipping,
				extract(day from convertTZ(%2$s, %3$s)) as day,
				extract(month from convertTZ(%2$s, %3$s)) as month,
				extract(year from convertTZ(%2$s, %3$s)) as year
			from Orders
				inner join Locale on Orders.locale = Locale.id
				inner join OrderCommissionTotalView on
					OrderCommissionTotalView.ordernum = Orders.id
			where
				extract(year from convertTZ(%2$s, %3$s)) = %4$s
				and extract(month from convertTZ(%2$s, %3$s)) = %5$s
				%6$s
			group by Locale.region, year, month, day';

		$sql = sprintf(
			$sql,
			$this->getShippingSelectClause(),
			$date_field,
			$this->app->db->quote($time_zone_name, 'text'),
			$this->app->db->quote($this->start_date->getYear(), 'integer'),
			$this->app->db->quote($this->start_date->getMonth(), 'integer'),
			$this->getInstanceWhereClause()
		);

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getShippingSelectClause()

	protected function getShippingSelectClause()
	{
		return 'sum(shipping_total)';
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
			$sql = 'select id, title from Region order by id';
			$this->regions = SwatDB::query($this->app->db, $sql,
				SwatDBClassMap::get('StoreRegionWrapper'));
		}

		return $this->regions;
	}

	// }}}
}

?>
