<?php

/**
 * A generated XML Site Map for stores.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       http://www.sitemaps.org/
 */
class StoreXmlSiteMapPage extends SiteXmlSiteMapPage
{
    // {{{ protected function displaySiteMap()

    protected function displaySiteMap()
    {
        parent::displaySiteMap();

        $categories = $this->queryCategories();
        $this->displayCategories($categories);
    }

    // }}}
    // {{{ protected function queryArticles()

    protected function queryArticles()
    {
        $articles = parent::queryArticles();
        $articles->setRegion($this->app->getRegion());

        return $articles;
    }

    // }}}
    // {{{ protected function displayCategories()

    protected function displayCategories($categories, $path = null)
    {
        foreach ($categories as $category) {
            if ($path === null) {
                $category_path = $category->shortname;
            } else {
                $category_path = $path . '/' . $category->shortname;
            }

            $this->displayPath($this->app->config->store->path .
                $category_path, null, 'weekly');

            $products = $category->getVisibleProducts();
            foreach ($products as $product) {
                $this->displayPath($this->app->config->store->path .
                    $product->path, null, 'weekly');
            }

            $sub_categories = $category->getVisibleSubCategories();
            if (count($sub_categories) > 0) {
                $this->displayCategories($sub_categories, $category_path);
            }
        }
    }

    // }}}
    // {{{ protected function queryCategories()

    protected function queryCategories()
    {
        $wrapper = SwatDBClassMap::get('StoreCategoryWrapper');

        $sql = 'select id, shortname
			from Category
			where parent is null
				and id in (select category from VisibleCategoryView)
			order by displayorder, title';

        $categories = SwatDB::query($this->app->db, $sql, $wrapper);
        $categories->setRegion($this->app->getRegion());

        return $categories;
    }

    // }}}
}
