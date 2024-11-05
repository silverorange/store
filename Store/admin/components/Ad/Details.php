<?php

/**
 * Report page for Ads
 *
 * Store also reports order conversion rates.
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdDetails extends SiteAdDetails
{
	// {{{ private properties

	/**
	 * Cache of regions used by queryRegions()
	 *
	 * @var RegionsWrapper
	 */
	private $regions = null;

	// }}}

	// init phase
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/details.xml';
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view): ?SwatTableModel
	{
		switch ($view->id) {
		case 'orders_view':
			return $this->getOrdersTableModel();
		default:
			return parent::getTableModel($view);
		}
	}

	// }}}
	// {{{ protected function getOrdersTableModel()

	protected function getOrdersTableModel(): SwatTableStore
	{
		$regions = $this->queryRegions();
		$this->appendRegionColumns($regions);

		$sql = sprintf('select * from RegionSalesByAdView where ad = %s',
			$this->app->db->quote($this->ad->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		$store = new SwatTableStore();

		foreach ($rs as $row) {
			foreach ($this->periods as $key => $value) {
				$periods[$key]->period = $value;

				$col1 = 'subtotal_'.$row->region;
				$col2 = $key.'_sales';
				$periods[$key]->{$col1} = $row->$col2;

				$col1 = 'orders_'.$row->region;
				$col2 = $key.'_orders';
				$periods[$key]->{$col1} = $row->$col2;

				$periods[$key]->total_orders += $row->$col2;
			}
		}

		foreach ($periods as $period)
			$store->add($period);

		return $store;
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
	// {{{ private function queryRegions()

	private function queryRegions()
	{
		if ($this->regions === null) {
			$sql = 'select id, title from Region order by Region.id';

			$this->regions =
				SwatDB::query($this->app->db, $sql,
					SwatDBClassMap::get('StoreRegionWrapper'));
		}

		return $this->regions;
	}

	// }}}
}

?>
