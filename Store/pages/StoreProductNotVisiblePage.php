<?php

/**
 * A page for displaying a message if a product is not visible.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductNotVisiblePage extends StoreNotVisiblePage
{
    public $product_id;

    // build phase

    protected function buildInternal()
    {
        $sql = 'select * from Product where id = %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->product_id, 'integer')
        );

        $categories = SwatDB::query(
            $this->app->db,
            $sql,
            'StoreProductWrapper'
        );

        $product = $categories->getFirst();

        $this->layout->data->title =
            SwatString::minimizeEntities((string) $product->title);

        $this->ui->getWidget('content')->content = sprintf(
            Store::_(
                '%s is not available from our %s store.'
            ),
            SwatString::minimizeEntities($product->title),
            SwatString::minimizeEntities($this->app->getRegion()->title)
        );
    }

    protected function getAvailableRegions()
    {
        $sql = 'select id, title from Region
			inner join VisibleProductCache
				on VisibleProductCache.region = Region.id
			where product = %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->product_id, 'integer')
        );

        return SwatDB::query(
            $this->app->db,
            $sql,
            'StoreRegionWrapper'
        );
    }

    protected function buildNavBar($link_prefix = '')
    {
        if (!isset($this->layout->navbar)) {
            return;
        }

        $this->layout->navbar->createEntry('Store', 'store');

        parent::buildNavBar('store');
    }
}
