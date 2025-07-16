<?php

/**
 * Index page for Catalogs.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogIndex extends AdminIndex
{
    /**
     * Cache of regions used by queryRegions().
     *
     * @var RegionsWrapper
     */
    private $regions;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());

        // set a default order on the table view
        $index_view = $this->ui->getWidget('index_view');
        $index_view->getColumn('title')->setDirection(
            SwatTableViewOrderableColumn::ORDER_BY_DIR_ASCENDING
        );

        foreach ($this->queryRegions() as $region) {
            $renderer = new SwatBooleanCellRenderer();
            $renderer->id = 'enabled_' . $region->id;

            $column = new SwatTableViewOrderableColumn(
                'enabled_' . $region->id
            );

            $column->title = sprintf(Store::_('Enabled in %s'), $region->title);
            $column->addRenderer($renderer);
            $column->addMappingToRenderer(
                $renderer,
                'enabled_' . $region->id,
                'value'
            );

            $index_view->appendColumn($column);
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    // build phase

    protected function getTableModel(SwatView $view): ?SwatTableModel
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
            $regions_join .= sprintf(
                $regions_join_base,
                $region->id,
                $this->app->db->quote($region->id, 'integer')
            ) . ' ';

            $regions_select .= sprintf(
                $regions_select_base,
                $region->id,
                $this->app->db->quote($region->id, 'integer')
            ) . ', ';
        }

        $sql = sprintf(
            'select %s id, title, clone_of, in_season
			from Catalog %s
			order by %s',
            $regions_select,
            $regions_join,
            $this->getOrderByClause($view, 'title')
        );

        return SwatDB::query($this->app->db, $sql);
    }

    final protected function queryRegions()
    {
        if ($this->regions === null) {
            $sql = 'select id, title from Region order by id';

            $this->regions = SwatDB::query(
                $this->app->db,
                $sql,
                SwatDBClassMap::get(StoreRegionWrapper::class)
            );
        }

        return $this->regions;
    }
}
