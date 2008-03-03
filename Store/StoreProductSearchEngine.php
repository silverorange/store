<?php

require_once 'Site/SiteSearchEngine.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/dataobjects/StorePriceRangeWrapper.php';
require_once 'Store/dataobjects/StoreAttributeWrapper.php';

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

	/**
	 * Optional price range to search with
	 *
	 * @var StorePriceRange
	 */
	public $price_range;

	/**
	 * Optional set of attributes to search with
	 *
	 * @var StoreAttributeWrapper
	 */
	public $attributes;

	/**
	 * Whether or not to search category descendants when a category
	 * is selected
	 *
	 * Defaults to true.
	 *
	 * @var boolean
	 */
	public $include_category_descendants = true;

	/**
	 * An optional category that products are featured within
	 *
	 * @var StoreCategory
	 */
	public $featured_category;

	/**
	 * Whether or not to search for only available products
	 *
	 * Defaults to false and searchs all visible products.
	 *
	 * @var boolean
	 */
	public $available_only = false;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new product search engine
	 *
	 * Adds default order by fields.
	 *
	 * @param SiteApplication $app the application object.
	 */
	public function __construct(SiteApplication $app)
	{
		parent::__construct($app);
		$this->addOrderByField('Product.title');
	}

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
			$summary[] = sprintf(Store::_('Category: <b>%s</b>'),
				SwatString::minimizeEntities($this->category->title));

		if ($this->price_range !== null)
			$summary[] = sprintf(Store::_('Price: <b>%s</b>'),
				SwatString::minimizeEntities($this->price_range->getTitle()));

		if ($this->attributes !== null) {
			if (count($this->attributes)) {
				ob_start();
				echo '<ul>';

				foreach ($this->attributes as $attribute) {
					echo '<li>';
					$attribute->display();
					echo '</li>';
				}

				echo '</ul>';
				$summary[] = sprintf('Attributes: <b>%s</b>', ob_get_clean());
			}
		}

		return $summary;
	}

	// }}}
	// {{{ protected function search()

	public function search($limit = null, $offset = null)
	{
		$products = parent::search($limit, $offset);

		if (count($products) > 0)
			$this->loadSubDataObjects($products);

		$products->setRegion($this->app->getRegion());

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
			Product.bodytext, Product.catalog,
			ProductPrimaryCategoryView.primary_category,
			ProductPrimaryImageView.image as primary_image,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as
				path,
			case when AvailableProductView.product is null then false
				else true
				end as is_available,
			VisibleProductCache.region as region_id';

		return $clause;
	}

	// }}}
	// {{{ protected function getFromClause()

	protected function getFromClause()
	{
		$clause = sprintf('from Product
			inner join Catalog on Product.catalog = Catalog.id
			inner join VisibleProductCache on
				VisibleProductCache.product = Product.id and
				VisibleProductCache.region = %s
			inner join getProductPriceRange(%s, false) as getProductPriceRange
				on getProductPriceRange.product = Product.id
			left outer join ProductPrimaryCategoryView on
				ProductPrimaryCategoryView.product = Product.id
			left outer join ProductPrimaryImageView
				on ProductPrimaryImageView.product = Product.id
			%s join AvailableProductView on
				AvailableProductView.product = Product.id and
				AvailableProductView.region = %s',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->available_only ? 'inner' : 'left outer',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		if ($this->fulltext_result !== null)
			$clause.= ' '.
				$this->fulltext_result->getJoinClause('Product.id', 'product');

		if ($this->category !== null) {
			$clause.= ' inner join CategoryProductBinding
				on CategoryProductBinding.product = Product.id';
		}

		if ($this->featured_category !== null) {
			if ($this->featured_category instanceof StoreCategory)
				$category_id = $this->featured_category->id;
			else
				$category_id = intval($this->featured_category);

			$clause.= sprintf(' inner join CategoryFeaturedProductBinding on
				CategoryFeaturedProductBinding.product = Product.id and
				CategoryFeaturedProductBinding.category = %s',
				$this->app->db->quote($category_id, 'integer'));
		}

		if ($this->price_range instanceof VanBourgondienPriceRange)
			$clause.= sprintf(' inner join getProductPriceRange(%s, %s) on
				getProductPriceRange.product = Product.id',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'),
				$this->app->db->quote(
					$this->price_range->original_price, 'boolean'));

		return $clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$clause = parent::getWhereClause();

		if ($this->category !== null) {
			if ($this->category instanceof StoreCategory)
				$category_id = $this->category->id;
			else
				$category_id = intval($this->category);

			if ($this->include_category_descendants)
				$clause.= sprintf(' and CategoryProductBinding.category in (
					select descendant from getCategoryDescendants(%s))',
					$this->app->db->quote($category_id, 'integer'));
			else
				$clause.= sprintf(' and CategoryProductBinding.category = %s',
					$this->app->db->quote($category_id, 'integer'));
		}

		if ($this->price_range instanceof StorePriceRange) {
			if ($this->price_range->end_price === null) {
				$clause.= sprintf(' and (getProductPriceRange.min_price >= %1$s)',
					$this->app->db->quote($this->price_range->start_price, 'integer'));
			} elseif ($this->price_range->start_price === null) {
				$clause.= sprintf(' and (getProductPriceRange.max_price <= %1$s)',
					$this->app->db->quote($this->price_range->end_price, 'integer'));
			} else {
				$clause.= sprintf(' and (
					(getProductPriceRange.min_price >= %1$s and getProductPriceRange.min_price <= %2$s) or
					(getProductPriceRange.max_price >= %1$s and getProductPriceRange.max_price <= %2$s))',
					$this->app->db->quote($this->price_range->start_price, 'integer'),
					$this->app->db->quote($this->price_range->end_price, 'integer'));
			}
		}

		if ($this->attributes instanceof StoreAttributeWrapper) {
			$attribute_ids = array();
			foreach ($this->attributes as $attribute)
				$clause.= sprintf(' and Product.id in (
					select product from ProductAttributeBinding
					where attribute = %s)',
					$this->app->db->quote($attribute->id, 'integer'));
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
			$default_order_by = implode(', ', $this->order_by_fields);
			$clause = $this->fulltext_result->getOrderByClause(
				$default_order_by);
		}

		return $clause;
	}

	// }}}
}

?>
