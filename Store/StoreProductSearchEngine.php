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
	 * An optional category or categories to search within
	 *
	 * @var StoreCategory|StoreCategoryWrapper
	 */
	public $category;

	/**
	 * Whether or not to search category descendants when a category or
	 * categories is selected
	 *
	 * Defaults to true.
	 *
	 * @var boolean
	 */
	public $include_category_descendants = true;

	// }}}
	// {{{ public function getSearchSummary()

	/**
	 * Get a summary of the criteria that was used to perform the search
	 *
	 * @return array an array of summary strings.
	 */
	public function getSearchSummary()
	{
		$summary = parent::getSearchSummary();

		if ($this->category !== null)
			$summary[] = sprintf('Category: <b>%s</b>',
				SwatString::minimizeEntities($this->category->title));

		return $summary;
	}

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
	 * Load sub-dataobjects for the StoreProductWrapper results
	 *
	 * @param StoreProductWrapper $products a recordset of StoreProduct
	 *                                       dataobjects.
	 */
	protected function loadSubDataObjects(StoreProductWrapper $products)
	{
		$sql = 'select * from Image where id in (%s)';
		$wrapper_class = SwatDBClassMap::get('StoreProductImageWrapper');
		$products->loadAllSubDataObjects(
			'primary_image', $this->app->db, $sql, $wrapper_class);
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
		$clause = 'select Product.id, Product.title, Product.shortname,
			Product.bodytext, ProductPrimaryCategoryView.primary_category,
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

		if (!$this->include_category_descendants &&
			($this->category instanceof StoreCategory ||
			$this->category instanceof StoreCategoryWrapper)) {

			if ($this->category instanceof StoreCategory) {
				$quoted_category_ids = $this->app->db->quote(
					$this->category->id, 'integer');
			} else {
				$quoted_category_ids = array();
				foreach ($this->category as $category)
					$quoted_category_ids[] =
						$this->app->db->quote($category->id, 'integer');

				$quoted_category_ids = implode(', ', $quoted_category_ids);
			}

			$clause.= sprintf(' inner join CategoryProductBinding on
				CategoryProductBinding.product = Product.id and
				CategoryProductBinding.category in (%s)',
				$quoted_category_ids);
		}

		return $clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$clause = parent::getWhereClause();

		if ($this->include_category_descendants &&
			($this->category instanceof StoreCategory ||
			$this->category instanceof StoreCategoryWrapper)) {

			if ($this->category instanceof StoreCategory) {
				$clause.= sprintf(' and Product.id in (
					select product from	CategoryProductBinding
					inner join getCategoryDescendents(%s) as
						category_descendents on
							category_descendents.descendent =
							CategoryProductBinding.category)',
					$this->app->db->quote($this->category->id, 'integer'));
			} else {
				if (count($this->category) > 0) {
					$clause.= ' and ';
					$first = true;
					foreach ($this->category as $category) {
						if ($first)
							$first = false;
						else
							$clause.= ' or ';

						$clause.= sprintf('(Product.id in (
							select product from CategoryProductBinding
							inner join getCategoryDescendents(%1$s) as
								category_descendents_%1$s on
									category_descendents_%1$s.descendent =
									CategoryProductBinding.category))',
							$this->app->db->quote($category->id, 'integer'));
					}
				}
			}
		}

		return $clause;
	}

	// }}}
	// {{{ protected function getOrderByClause()

	protected function getOrderByClause()
	{
		if ($this->fulltext_result === null) {
			$clause = parent::getOrderByClause();
		} else {
			$default_order_by = implode(', ', $this->order_by_clauses);
			$clause = $this->fulltext_result->getOrderByClause(
				$default_order_by);
		}

		return $clause;
	}

	// }}}
}

?>
