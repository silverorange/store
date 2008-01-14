<?php

require_once 'Site/pages/SiteXmlSiteMapPage.php';
require_once 'Store/dataobjects/StoreArticleWrapper.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';

/**
 * A generated XML Site Map for stores
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
			if ($path === null)
				$category_path = $category->shortname;
			else
				$category_path = $path.'/'.$category->shortname;

			$this->displayPath('store/'.$category_path, null, 'weekly');

			$products = $category->getVisibleProducts();
			foreach ($products as $product)
				$this->displayPath('store/'.$product->path, null, 'weekly');

			$sub_categories = $category->getVisibleSubCategories();
			if (count($sub_categories) > 0)
				$this->displayCategories($sub_categories, $category_path);
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

		$sql = sprintf($sql, $this->app->db->quote(true, 'boolean'));

		$categories = SwatDB::query($this->app->db, $sql, $wrapper);
		$categories->setRegion($this->app->getRegion());

		return $categories;
	}

	// }}}
}

?>
