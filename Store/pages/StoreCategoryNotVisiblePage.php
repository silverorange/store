<?php

/**
 * A page for displaying a message if a category is not visible.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryNotVisiblePage extends StoreNotVisiblePage
{
    public $category_id;

    // build phase

    protected function buildInternal()
    {
        $sql = 'select * from Category where id = %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->category_id, 'integer')
        );

        $categories = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreCategoryWrapper::class)
        );

        $category = $categories->getFirst();

        $this->layout->data->title =
            SwatString::minimizeEntities((string) $category->title);

        $this->ui->getWidget('content')->content = sprintf(
            Store::_(
                'Products in the %s category are not available from our %s
			store.'
            ),
            SwatString::minimizeEntities($category->title),
            SwatString::minimizeEntities($this->app->getRegion()->title)
        );
    }

    protected function getAvailableRegions()
    {
        $sql = 'select id, title from Region
			inner join VisibleCategoryView
				on VisibleCategoryView.region = Region.id
			where category = %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->category_id, 'integer')
        );

        return SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreRegionWrapper::class)
        );
    }

    protected function buildNavBar($link_prefix = '')
    {
        if (isset($this->layout->navbar)) {
            $this->layout->navbar->createEntry('Store', 'store');
        }

        parent::buildNavBar('store');
    }
}
