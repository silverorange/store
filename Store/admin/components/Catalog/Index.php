<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';

/**
 * Index page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogIndex extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Catalog/index.xml';

	// }}}
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

		// set a default order on the table view
		$index_view = $this->ui->getWidget('index_view');
		$index_view->getColumn('title')->setDirection(
			SwatTableViewOrderableColumn::ORDER_BY_DIR_ASCENDING);

		foreach ($this->queryRegions() as $region) {
			$renderer = new SwatBooleanCellRenderer();
			$renderer->id = 'enabled_'.$region->id;

			$column = new SwatTableViewOrderableColumn(
				'enabled_'.$region->id);

			$column->title = sprintf(Store::_('Enabled in %s'), $region->title);
			$column->addRenderer($renderer);
			$column->addMappingToRenderer($renderer, 'enabled_'.$region->id,
				'value');

			$index_view->appendColumn($column);
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		/*
		 * This dynamic SQL is needed to make the table orderable by the
		 * enabled columns.
		 */
		$regions_join_base =
			'left outer join CatalogRegionBinding as CatalogRegionBinding_%1$s
				on CatalogRegionBinding_%1$s.Catalog = Catalog.id
					and CatalogRegionBinding_%1$s.region = %2$s';

		$regions_select_base =
			'CatalogRegionBinding_%s.catalog is not null as enabled_%s';

		$regions_join = '';
		$regions_select = '';
		foreach ($this->queryRegions() as $region) {
			$regions_join.= sprintf($regions_join_base,
				$region->id,
				$this->app->db->quote($region->id, 'integer')).' ';

			$regions_select.= sprintf($regions_select_base,
				$region->id,
				$this->app->db->quote($region->id, 'integer')).', ';
		}

		$sql = sprintf('select %s id, title, clone_of, in_season
			from Catalog %s
			order by %s',
			$regions_select,
			$regions_join,
			$this->getOrderByClause($view, 'title'));

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}
	// {{{ protected final function queryRegions()

	protected final function queryRegions()
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
