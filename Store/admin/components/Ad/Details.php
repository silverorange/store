<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMoneyCellRenderer.php';
require_once 'Swat/SwatNumericCellRenderer.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';


/**
 * Report page for Ads
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdDetails extends AdminIndex
{
	// {{{ protected properties

	protected $id;
	protected $ui_xml = 'Store/admin/components/Ad/details.xml';
	protected $periods;

	// }}}}
	// {{{ private properties

	/**
	 * Cache of regions used by queryRegions()
	 *
	 * @var RegionsWrapper
	 */
	private $regions = null;
	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);

		$this->id = SiteApplication::initVar('id');

		$title =  SwatDB::queryOne($this->app->db,
			sprintf('select title from Ad where id = %s',
			$this->app->db->quote($this->id, 'integer')));

		$this->ui->getWidget('index_frame')->subtitle = $title;

		$this->periods = array(
			'day' => Store::_('Day'),
			'week' => Store::_('Week'),
			'two_week' => Store::_('2 Weeks'),
			'month' => Store::_('Month'),
			'total' => Store::_('Total')
		);
	}

	// }}}

	// build phase
	// {{{ protected function getTableStore()

	protected function getTableStore($view) 
	{
		switch ($view->id) {
		case 'orders_view' :
			return $this->getOrdersTableStore();
		case 'referrer_period_view' :
			return $this->getRefererPeriodTableStore();
		}
	}

	// }}}
	// {{{ protected function getOrdersTableStore()

	protected function getOrdersTableStore()
	{
		$regions = $this->queryRegions();
		$this->appendRegionColumns($regions);

		$sql = sprintf('select * from RegionSalesByAdView where ad = %s',
			$this->app->db->quote($this->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		$store = new SwatTableStore();

		foreach ($rs as $row) {
			foreach ($this->periods as $key => $val) {
				$myvar[$key]->period = $val;

				$col1 = 'subtotal_'.$row->region;
				$col2 = $key.'_sales';
				$myvar[$key]->$col1 = $row->$col2;

				$col1 = 'orders_'.$row->region;
				$col2 = $key.'_orders';
				$myvar[$key]->$col1 = $row->$col2;

				$myvar[$key]->total_orders += $row->$col2;
			}
		}

		foreach ($myvar as $row)
			$store->addRow($row);

		return $store;
	}

	// }}}
	// {{{ protected function getRefererPeriodTableStore()

	protected function getRefererPeriodTableStore()
	{
		$sql = sprintf('select *from AdReferrerByPeriodView where ad = %s',
			$this->app->db->quote($this->id, 'integer'));

		$row = SwatDB::queryRow($this->app->db, $sql);

		$store = new SwatTableStore();

		foreach ($this->periods as $key => $val) {
			$myvar->period = $val;
			$myvar->referrers = intval($row->$key);

			$store->addRow(clone $myvar);
		}

		return $store;
	}

	// }}}
	// {{{ private function queryRegions()

	private function queryRegions()
	{
		if ($this->regions === null) {
			$sql = 'select id, title from Region order by Region.id';

			$this->regions =
				SwatDB::query($this->app->db, $sql, 'StoreRegionWrapper');
		}

		return $this->regions;
	}

	// }}}
	// {{{ protected function appendRegionColumns()

	protected function appendRegionColumns($regions)
	{
		$view = $this->ui->getWidget('orders_view');

		foreach ($regions as $region) {
			$subtotal_column = new SwatTableViewColumn('subtotal_'.$region->id);
			$subtotal_column->title = sprintf(Store::_('%s Subtotal'),
				$region->title);

			$subtotal_renderer = new SwatMoneyCellRenderer();
			$subtotal_renderer->locale = $region->getFirstLocale()->id;

			$subtotal_column->addRenderer($subtotal_renderer);
			$subtotal_column->addMappingToRenderer($subtotal_renderer,
				'subtotal_'.$region->id, 'value');


			$orders_column = new SwatTableViewColumn('orders_'.$region->id);
			$orders_column->title = sprintf(Store::_('%s Orders'),
				$region->title);

			$orders_renderer = new SwatNumericCellRenderer();

			$orders_column->addRenderer($orders_renderer);
			$orders_column->addMappingToRenderer($orders_renderer,
				'orders_'.$region->id, 'value');

			$view->appendColumn($orders_column);
			$view->appendColumn($subtotal_column);
		}

		$orders_column = new SwatTableViewColumn('total_orders');
		$orders_column->title = Store::_('Total Orders');

		$orders_renderer = new SwatNumericCellRenderer();

		$orders_column->addRenderer($orders_renderer);
		$orders_column->addMappingToRenderer($orders_renderer,
			'total_orders', 'value');

		$view->appendColumn($orders_column);
		
	}
	// }}}
}	

?>
