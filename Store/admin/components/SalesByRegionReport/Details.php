<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNumericCellRenderer.php';
require_once 'Swat/SwatMoneyCellRenderer.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Store/dataobjects/StoreCountryWrapper.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';
require_once 'Store/admin/components/SalesByRegionReport/include/StoreSalesByRegionGroup.php';

/**
 * Displays sales split by region for a year
 *
 * @package   Store
 * @copyright 2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSalesByRegionReportDetails extends AdminIndex
{
	// {{{ protected properties

	/**
	 * The starting date for this report
	 *
	 * @var SwatDate
	 */
	protected $start_date;

	/**
	 * @var boolean
	 */
	protected $show_shipping = false;

	/**
	 * @var StoreCountryWrapper
	 */
	protected $detail_countries = null;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/SalesByRegionReport/details.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$id = SiteApplication::initVar('id');

		if (preg_match('/^[12][0-9]{3}$/', $id) !== 1) {
			throw new AdminNotFoundException(
				sprintf(
					'Unable to load report with id of “%s”',
					$id
				)
			);
		}

		$this->start_date = new SwatDate('now', $this->app->default_time_zone);
		$this->start_date->toUTC();
		if ($this->start_date->setDate($id, 1, 1) === false) {
			throw new AdminNotFoundException(
				sprintf(
					'Unable to load report with id of “%s”',
					$id
				)
			);
		}

		$this->ui->loadFromXML($this->getUiXml());

		$view = $this->ui->getWidget('index_view');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$report_title = $this->start_date->formatLikeIntl(Store::_('YYYY'));

		// set frame title
		$index_frame = $this->ui->getWidget('index_frame');
		$index_frame->subtitle = $report_title;

		// and navbar entry
		$this->layout->navbar->createEntry($report_title);

		$view = $this->ui->getWidget('index_view');
		$view->getColumn('shipping')->visible = $this->show_shipping;
		$view->getGroup('country')->getRenderer('shipping_total')->visible =
			$this->show_shipping;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$end_date = clone $this->start_date;
		$end_date->addYears(1);
		$end_date->toUTC();

		$sql = sprintf(
			'select sum(Orders.total) as gross_total,
				sum(Orders.shipping_total) as shipping_total,
				Country.title as country_title,
				Country.id as country_id
			from Orders
				inner join OrderAddress
					on Orders.billing_address = OrderAddress.id
				inner join Country on OrderAddress.Country = Country.id
			where convertTZ(Orders.createdate, %1$s) >= %2$s
				and convertTZ(Orders.createdate, %1$s) < %3$s
				and Orders.cancel_date is null
				%4$s
			group by Country.id, Country.title
			order by Country.title',
			$this->app->db->quote(
				$this->app->default_time_zone->getName(),
				'text'
			),
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($end_date->getDate(), 'date'),
			$this->getInstanceWhereClause()
		);

		$store = new SwatTableStore();

		$rs = SwatDB::query(
			$this->app->db,
			$sql
		);

		$detail_countries = $this->getDetailCountries();

		foreach ($rs as $row) {
			if (isset($detail_countries[$row->country_id])) {
				$provstate_model = $this->getProvStateModel($row->country_id);
				foreach ($provstate_model as $provstate_row) {
					$ds = new SwatDetailsStore($provstate_row);

					$ds->country_group = $row->country_title;
					$ds->country_gross_total = $row->gross_total;
					$ds->country_shipping_total = $row->shipping_total;
					$ds->region_title = $provstate_row->provstate_title;
					$ds->locale_id = 'en_US';

					$store->add($ds);
				}
			}
		}

		$other_gross_total = 0;
		$other_shipping_total = 0;
		foreach ($rs as $row) {
			if (!isset($detail_countries[$row->country_id])) {
				$other_gross_total += $row->gross_total;
				$other_shipping_total += $row->shipping_total;
			}
		}

		foreach ($rs as $row) {
			if (!isset($detail_countries[$row->country_id])) {
				$ds = new SwatDetailsStore($row);

				$ds->country_group = Store::_('Other');
				$ds->country_gross_total = $other_gross_total;
				$ds->country_shipping_total = $other_shipping_total;
				$ds->region_title = $row->country_title;
				$ds->locale_id = 'en_US';

				$store->add($ds);
			}
		}


		return $store;
	}

	// }}}
	// {{{ protected function getProvStateModel()

	protected function getProvStateModel($country_id)
	{
		$end_date = clone $this->start_date;
		$end_date->addYears(1);
		$end_date->toUTC();

		$sql = sprintf(
			'select sum(Orders.total) as gross_total,
				sum(Orders.shipping_total) as shipping_total,
				Country.title as country_title,
				Country.id as country_id,
				ProvState.title as provstate_title
			from Orders
				inner join OrderAddress
					on Orders.billing_address = OrderAddress.id
				inner join Country on OrderAddress.Country = Country.id
				left outer join ProvState
					on OrderAddress.ProvState = ProvState.id
			where Country.id = %5$s
				and convertTZ(Orders.createdate, %1$s) >= %2$s
				and convertTZ(Orders.createdate, %1$s) < %3$s
				and Orders.cancel_date is null
				%4$s
			group by Country.id, Country.title, ProvState.title
			order by Country.title, ProvState.title',
			$this->app->db->quote(
				$this->app->default_time_zone->getName(),
				'text'
			),
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($end_date->getDate(), 'date'),
			$this->getInstanceWhereClause(),
			$this->app->db->quote($country_id, 'text')
		);

		return SwatDB::query(
			$this->app->db,
			$sql
		);
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
	// {{{ protected function getDetailCountries()

	protected function getDetailCountries()
	{
		if (!$this->detail_countries instanceof StoreCountryWrapper) {
			$this->detail_countries = SwatDB::query(
				$this->app->db,
				sprintf(
					'select * from Country
					where %s
					order by Country.title',
					$this->getDetailCountriesWhereClause()
				),
				SwatDBClassMap::get('StoreCountryWrapper')
			);
		}

		return $this->detail_countries;
	}

	// }}}
	// {{{ protected function getDetailCountriesWhereClause()

	protected function getDetailCountriesWhereClause()
	{
		return 'id in (select country from ProvState)';
	}

	// }}}
}

?>
