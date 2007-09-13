<?php

require_once 'Site/SiteSearchEngine.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';

/**
 * A product search engine
 *
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreProductSearchEngine extends SiteSearchEngine
{
	// {{{ public properties

	/**
	 * An optional category to search within
	 *
	 * @var StoreCategory
	 */
	public $category;

	// }}}

	// {{{ protected function search()

	public function search($limit = null, $offset = null)
	{
		$products = parent::search($limit, $offset);

		if (count($products) > 0)
			$this->loadSubDataObjects($products);

		return $products;
	}

	// }}}
	// {{{ protected function loadSubDataObjects()

	/**
	 * Load sub dataobjects for the StoreProductWrapper results
	 *
	 * @param StoreProductWrapper $products A collection of StoreProduct
	 *                                       dataobjects.
	 */
	protected function loadSubDataObjects(StoreProductWrapper $products)
	{
		$sql = 'select * from Image where id in (%s)';
		$image_wrapper_class = SwatDBClassMap::get('StoreProductImageWrapper');

		$products->loadAllSubDataObjects(
			'primary_image', $this->app->db, $sql, $image_wrapper_class);
	}

	// }}}
	// {{{ protected function getResultWrapperClass()

	protected function getResultWrapperClass()
	{
		$wrapper_class = SwatDBClassMap::get('StoreProductWrapper');

		return $wrapper_class;
	}

	// }}}
	// {{{ protected function getSelectClause()

	protected function getSelectClause()
	{
		$clause = 'select Product.title, Product.shortname, Product.bodytext,
			ProductPrimaryCategoryView.primary_category,
			ProductPrimaryImageView.image as primary_image,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path';

		return $clause;
	}

	// }}}
	// {{{ protected function getFromClause()

	protected function getFromClause()
	{
		$clause = sprintf('from Product
				left outer join ProductPrimaryCategoryView on
					ProductPrimaryCategoryView.product = Product.id
				left outer join ProductPrimaryImageView
					on ProductPrimaryImageView.product = Product.id
				inner join VisibleProductCache on
					VisibleProductCache.product = Product.id and
					VisibleProductCache.region = %s',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		if ($this->fulltext_result !== null)
			$clause.= ' '.
				$this->fulltext_result->getJoinClause('Product.id', 'product');

		return $clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$clause = parent::getWhereClause();

		if ($this->category instanceof StoreCategory)
			$clause.= sprintf(' and Product.id in (
				select product from	CategoryProductBinding
				inner join getCategoryDescendents(%s) as category_descendents
					on category_descendents.descendent = 
					CategoryProductBinding.category)',
				$this->app->db->quote($this->category->id, 'integer'));

		return $clause;
	}

	// }}}
	// {{{ protected function getOrderByClause()

	protected function getOrderByClause()
	{
		if ($this->fulltext_result === null)
			$clause = sprintf('order by Product.title');
		else
			$clause = 
				$this->fulltext_result->getOrderByClause('Product.title');

		return $clause;
	}

	// }}}
}

?>
