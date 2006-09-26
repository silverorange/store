<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/dataobjects/StoreCategory.php';
require_once 'Store/StoreCategoryPath.php';

/**
 * Resolves pages below the 'store' tag in the URL.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreStorePageFactory
{
	// {{{ protected properties

	protected $app;

	// }}}
	// {{{ public __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function resolvePage()

	public function resolvePage($path)
	{
		// if path is empty, load front page of store
		if (count($path) == 0)
			return $this->resolveFrontPage($path);

		// if path ends with 'image', try to load product image page
		if (end($path) == 'image') {
			array_pop($path);

			list($product_id, $category_id) = 
				$this->findProduct($path);

			if ($product_id === null)
				throw new SiteNotFoundException();

			return $this->resolveProductImagePage($path, $category_id,
				$product_id);
		}

		$category_id = 
			$this->findCategory($path);

		// if path is a valid category, load category page
		if ($category_id !== null)
			return $this->resolveCategoryPage($path, $category_id);

		list($product_id, $category_id) = 
			$this->findProduct($path);

		// if path is a valid product, load product page
		if ($product_id !== null)
			return $this->resolveProductPage($path, $category_id, $product_id);

		// we wern't able to resolve a product or a category
		throw new SiteNotFoundException();
	}

	// }}}
	// {{{ protected function resolveLayout()

	protected function resolveLayout($path)
	{
		return null;
	}

	// }}}
	// {{{ protected function resolveFrontPage()

	protected function resolveFrontPage($path)
	{
		throw new SiteNotFoundException();
	}

	// }}}
	// {{{ protected function resolveCategoryPage()

	protected function resolveCategoryPage($path, $category_id)
	{
		$layout = $this->resolveLayout($path);
		require_once 'Store/pages/StoreCategoryPage.php';
		$page = new StoreCategoryPage($this->app, $layout);
		$page->path = new StoreCategoryPath($this->app, $category_id);

		return $page;
	}

	// }}}
	// {{{ protected function resolveProductPage()

	protected function resolveProductPage($path, $category_id, $product_id)
	{
		$layout = $this->resolveLayout($path);
		require_once 'Store/pages/StoreProductPage.php';
		$page = new StoreProductPage($this->app, $layout);
		$page->path = new StoreCategoryPath($this->app, $category_id);
		$page->product_id = $product_id;

		return $page;
	}

	// }}}
	// {{{ protected function resolveProductImagePage()

	protected function resolveProductImagePage($path, $category_id, $product_id)
	{
		$layout = $this->resolveLayout($path);
		require_once 'Store/pages/StoreProductImagePage.php';
		$page = new StoreProductImagePage($this->app, $layout);
		$page->path = new StoreCategoryPath($this->app, $category_id);
		$page->product_id = $product_id;

		return $page;
	}

	// }}}
	// {{{ protected function findCategory()

	protected function findCategory($path)
	{
		$category_id = null;
		$db = $this->app->db;
		$region_id = $this->app->getRegion()->id;

		// don't try to resolve categories that are deeper than the max depth
		if (count($path) <= StoreCategory::MAX_DEPTH) {

			// trim at 254 to prevent database errors
			$path_str = substr(implode('/', $path), 0, 254);

			/*
			 * Region is null in VisibleCategoryView for categories with
			 * always_visible = true and no products.  Categories with the
			 * always_visible flag set and no products therefore must always
			 * be visible in both regions.  This is the reason for the
			 * "region is null" clause below.
			 */
			$sql = 'select findCategory from findCategory(%s)
				inner join VisibleCategoryView on
					findCategory = VisibleCategoryView.category
				where region = %s or region is null';

			$sql = sprintf($sql,
				$db->quote($path_str, 'text'),
				$db->quote($region_id, 'integer'));

			$category_id = SwatDB::queryOne($db, $sql);
		}

		return $category_id;
	}

	// }}}
	// {{{ protected function findProduct()

	protected function findProduct($path)
	{
		$product_id = null;
		$db = $this->app->db;
		$region_id = $this->app->getRegion()->id;

		$product_shortname = array_pop($path);
		$category_id = $this->findCategory($path);

		if ($category_id !== null) {
			$sql = 'select id from Product where shortname = %s
				and id in 
				(select product from CategoryProductBinding where category = %s)
				and id in 
				(select product from VisibleProductCache where region = %s)';

			$sql = sprintf($sql,
				$db->quote($product_shortname, 'text'),
				$db->quote($category_id, 'integer'),
				$db->quote($region_id, 'integer'));

			$product_id = SwatDB::queryOne($db, $sql);
		}

		return array($product_id, $category_id);
	}

	// }}}
}

?>
