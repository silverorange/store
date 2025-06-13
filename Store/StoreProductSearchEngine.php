<?php

/**
 * A product search engine.
 *
 * @copyright 2007-2016 silverorange
 */
class StoreProductSearchEngine extends SiteSearchEngine
{
    // {{{ public properties

    /**
     * An optional sku to limit search results with.
     *
     * @var string
     */
    public $sku;

    /**
     * An optional category to search within.
     *
     * @var StoreCategory
     */
    public $category;

    /**
     * Optional price range to search with.
     *
     * @var StorePriceRange
     */
    public $price_range;

    /**
     * Optional set of attributes to search with.
     *
     * @var StoreAttributeWrapper
     */
    public $attributes;

    /**
     * Optional region to search within.
     *
     * If region is not set, the engine uses the Application's region.
     *
     * @var StoreRegion
     */
    public $region;

    /**
     * Optional instance to search within.
     *
     * @var SiteInstance
     */
    public $instance;

    /**
     * Whether or not to search category descendants when a category
     * is selected.
     *
     * Defaults to true.
     *
     * @var bool
     */
    public $include_category_descendants = true;

    /**
     * An optional category that products are featured within.
     *
     * @var StoreCategory
     */
    public $featured_category;

    /**
     * Whether or not to search for only available products.
     *
     * Defaults to false and searchs all available products.
     *
     * @var bool
     */
    public $available_only = false;

    /**
     * Whether or not to search for only visible products.
     *
     * Defaults to true and searchs all visible products.
     *
     * @var bool
     */
    public $visible_only = true;

    /**
     * Optional flag to search for collections only.
     *
     * Search will find collection products only.
     *
     * @var bool
     */
    public $collection_products_only = false;

    /**
     * Optional member product for collections.
     *
     * Search will find collections containing this member product.
     *
     * @var StoreProduct
     */
    public $collection_member_product;

    /**
     * Optional source product for related products.
     *
     * Search will find products related to the source product.
     *
     * @var StoreProduct
     */
    public $related_source_product;

    /**
     * Optional source product for collections.
     *
     * Search will find products in this collection product.
     *
     * @var StoreProduct
     */
    public $collection_source_product;

    /**
     * Whether or not to supress duplicate products.
     *
     * When searching within a category, the primary category view is not used
     * and it is possible get duplicate product results if a product belongs
     * to multiple categories within the search category.  This option will
     * suppress these dupes by choosing a primary category within the subtree.
     *
     * Defaults to false.
     *
     * @var bool
     */
    public $supress_duplicate_products = false;

    /**
     * An optional array of product id to limit search results with.
     *
     * @var array
     */
    public $product_ids;

    /**
     * An optional array of product shortnames to limit search results with.
     *
     * @var array
     */
    public $product_shortnames;

    /**
     * Item minimum quantity group.
     *
     * @var StoreItemMinimumQuantityGroup
     */
    public $item_minimum_quantity_group;

    // }}}
    // {{{ public function __construct()

    /**
     * Creates a new product search engine.
     *
     * Adds default order by fields.
     *
     * @param SiteApplication $app the application object
     */
    public function __construct(SiteApplication $app)
    {
        parent::__construct($app);
        $this->addOrderByField('Product.title');
    }

    // }}}
    // {{{ public function setRegion()

    public function setRegion(StoreRegion $region)
    {
        $this->region = $region;
    }

    // }}}
    // {{{ public function getRegion()

    /**
     * Creates a new product search engine.
     *
     * Adds default order by fields.
     */
    public function getRegion()
    {
        if ($this->region === null) {
            $this->region = $this->app->getRegion();
        }

        return $this->region;
    }

    // }}}
    // {{{ public function getSearchSummary()

    /**
     * Get a summary of the criteria that was used to perform the search.
     *
     * @return array an array of summary strings
     */
    public function getSearchSummary()
    {
        $summary = parent::getSearchSummary();

        if ($this->category !== null) {
            $summary[] = sprintf(
                Store::_('Category: <b>%s</b>'),
                SwatString::minimizeEntities($this->category->title)
            );
        }

        if ($this->price_range !== null) {
            $summary[] = sprintf(
                Store::_('Price: <b>%s</b>'),
                SwatString::minimizeEntities($this->price_range->getTitle())
            );
        }

        if ($this->item_minimum_quantity_group !== null) {
            $summary[] = sprintf(
                Store::_('Group: <b>%s</b>'),
                SwatString::minimizeEntities(
                    $this->item_minimum_quantity_group->title
                )
            );
        }

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
    // {{{ protected function getMemcacheNs()

    protected function getMemcacheNs()
    {
        return 'product';
    }

    // }}}
    // {{{ protected function search()

    public function search($limit = null, $offset = null)
    {
        $products = parent::search($limit, $offset);
        $products->setRegion($this->getRegion());

        return $products;
    }

    // }}}
    // {{{ protected function loadSubObjects()

    /**
     * Load sub-dataobjects for the StoreProductWrapper results.
     *
     * @param StoreProductWrapper $products a recordset of StoreProduct
     *                                      dataobjects
     */
    protected function loadSubObjects(SwatDBRecordsetWrapper $products)
    {
        parent::loadSubObjects($products);

        if ($products instanceof StoreProductWrapper) {
            $sql = 'select * from Image where id in (%s)';
            $wrapper_class = SwatDBClassMap::get('StoreProductImageWrapper');
            $products->loadAllSubDataObjects(
                'primary_image',
                $this->app->db,
                $sql,
                $wrapper_class
            );
        }
    }

    // }}}
    // {{{ protected function getResultWrapperClass()

    protected function getResultWrapperClass()
    {
        return SwatDBClassMap::get('StoreProductWrapper');
    }

    // }}}
    // {{{ protected function getSelectClause()

    protected function getSelectClause()
    {
        $clause = 'select';
        $first = true;

        foreach ($this->getSelectClauseTerms() as $name => $term) {
            if ($first) {
                $first = false;
            } else {
                $clause .= ',';
            }

            $clause .= sprintf(' %s as %s', $term, $name);
        }

        return $clause;
    }

    // }}}
    // {{{ protected function getSelectClauseTerms()

    protected function getSelectClauseTerms()
    {
        $terms = [
            'id'            => 'Product.id',
            'title'         => 'Product.title',
            'shortname'     => 'Product.shortname',
            'bodytext'      => 'Product.bodytext',
            'catalog'       => 'Product.catalog',
            'primary_image' => 'ProductPrimaryImageView.image',
            'region_id'     => 'VisibleProductCache.region',
            'is_available'  => 'case when AvailableProductView.product is null then
					false else true end',
        ];

        if ($this->category === null) {
            $terms['primary_category'] =
                'ProductPrimaryCategoryView.primary_category';

            $terms['path'] =
                'getCategoryPath(ProductPrimaryCategoryView.primary_category)';
        } else {
            $terms['primary_category'] = 'CategoryProductBinding.category';

            $terms['path'] =
                'getCategoryPath(CategoryProductBinding.category)';
        }

        return $terms;
    }

    // }}}
    // {{{ protected function getFromClause()

    protected function getFromClause()
    {
        $clause = sprintf(
            'from Product
			inner join Catalog on Product.catalog = Catalog.id
			%s join VisibleProductCache on
				VisibleProductCache.product = Product.id and
				VisibleProductCache.region = %s
			left outer join ProductPrimaryImageView
				on ProductPrimaryImageView.product = Product.id
			%s join AvailableProductView on
				AvailableProductView.product = Product.id and
				AvailableProductView.region = %s',
            $this->visible_only ? 'inner' : 'left outer',
            $this->app->db->quote($this->getRegion()->id, 'integer'),
            $this->available_only ? 'inner' : 'left outer',
            $this->app->db->quote($this->getRegion()->id, 'integer')
        );

        if ($this->fulltext_result !== null) {
            $clause .= ' ' .
                $this->fulltext_result->getJoinClause('Product.id', 'product');
        }

        if ($this->sku !== null) {
            $clause .= 'inner join ItemView on Product.id = ItemView.product';
        }

        if ($this->category === null) {
            $clause .= ' left outer join ProductPrimaryCategoryView
				on ProductPrimaryCategoryView.product = Product.id';
        } else {
            if ($this->supress_duplicate_products) {
                $clause .= sprintf(
                    '
					inner join CategoryProductBinding
						on CategoryProductBinding.product = Product.id
					inner join getProductPrimaryCategoryInSubTree(%s)
						as ProductPrimaryCategoryView
					on ProductPrimaryCategoryView.product = Product.id and
						CategoryProductBinding.category =
							ProductPrimaryCategoryView.primary_category',
                    $this->app->db->quote($this->category->id, 'integer')
                );
            } else {
                $clause .= '
					inner join CategoryProductBinding
						on CategoryProductBinding.product = Product.id
					left outer join ProductPrimaryCategoryView
						on ProductPrimaryCategoryView.product = Product.id and
							CategoryProductBinding.category =
								ProductPrimaryCategoryView.primary_category';
            }
        }

        if ($this->featured_category !== null) {
            if ($this->featured_category instanceof StoreCategory) {
                $category_id = $this->featured_category->id;
            } else {
                $category_id = intval($this->featured_category);
            }

            $clause .= sprintf(
                ' inner join CategoryFeaturedProductBinding on
				CategoryFeaturedProductBinding.product = Product.id and
				CategoryFeaturedProductBinding.category = %s',
                $this->app->db->quote($category_id, 'integer')
            );
        }

        if ($this->price_range instanceof StorePriceRange) {
            $clause .= sprintf(
                ' inner join getProductPriceRange(%s, %s) on
				getProductPriceRange.product = Product.id',
                $this->app->db->quote($this->getRegion()->id, 'integer'),
                $this->app->db->quote(
                    $this->price_range->original_price,
                    'boolean'
                )
            );
        }

        if ($this->related_source_product instanceof StoreProduct) {
            $clause .= ' inner join ProductRelatedProductBinding on
					Product.id = ProductRelatedProductBinding.related_product';
        }

        return $clause;
    }

    // }}}
    // {{{ protected function getWhereClause()

    protected function getWhereClause()
    {
        $clause = parent::getWhereClause();

        if ($this->sku !== null) {
            $clause .= sprintf(
                ' and ItemView.sku = %s',
                $this->app->db->quote($this->sku, 'text')
            );
        }

        if ($this->category !== null) {
            if ($this->category instanceof StoreCategory) {
                $category_id = $this->category->id;
            } else {
                $category_id = intval($this->category);
            }

            if ($this->include_category_descendants) {
                $clause .= sprintf(
                    ' and CategoryProductBinding.category in (
					select descendant from getCategoryDescendants(%s))',
                    $this->app->db->quote($category_id, 'integer')
                );
            } else {
                $clause .= sprintf(
                    ' and CategoryProductBinding.category = %s',
                    $this->app->db->quote($category_id, 'integer')
                );
            }
        }

        if ($this->price_range instanceof StorePriceRange) {
            if ($this->price_range->end_price === null) {
                $clause .= sprintf(
                    ' and (getProductPriceRange.min_price >= %1$s)',
                    $this->app->db->quote($this->price_range->start_price, 'integer')
                );
            } elseif ($this->price_range->start_price === null) {
                $clause .= sprintf(
                    ' and (getProductPriceRange.max_price <= %1$s)',
                    $this->app->db->quote($this->price_range->end_price, 'integer')
                );
            } else {
                $clause .= sprintf(
                    ' and ((
						getProductPriceRange.min_price >= %1$s
						and getProductPriceRange.min_price <= %2$s
					) or (
						getProductPriceRange.max_price >= %1$s
						and getProductPriceRange.max_price <= %2$s
					))',
                    $this->app->db->quote($this->price_range->start_price, 'integer'),
                    $this->app->db->quote($this->price_range->end_price, 'integer')
                );
            }
        }

        if ($this->attributes instanceof StoreAttributeWrapper) {
            $attribute_ids = [];
            foreach ($this->attributes as $attribute) {
                $clause .= sprintf(
                    ' and Product.id in (
					select product from ProductAttributeBinding
					where attribute = %s)',
                    $this->app->db->quote($attribute->id, 'integer')
                );
            }
        }

        if ($this->related_source_product instanceof StoreProduct) {
            $clause .= sprintf(
                ' and ProductRelatedProductBinding.source_product = %s',
                $this->app->db->quote(
                    $this->related_source_product->id,
                    'integer'
                )
            );
        }

        if ($this->collection_source_product instanceof StoreProduct) {
            $clause .= sprintf(
                ' and Product.id in
				(select member_product from ProductCollectionBinding
				where source_product = %s)',
                $this->app->db->quote(
                    $this->collection_source_product->id,
                    'integer'
                )
            );
        }

        if ($this->collection_member_product instanceof StoreProduct) {
            $clause .= sprintf(
                ' and Product.id in
				(select source_product from ProductCollectionBinding
				where member_product = %s)',
                $this->app->db->quote(
                    $this->collection_member_product->id,
                    'integer'
                )
            );
        }

        if ($this->collection_products_only) {
            $clause .= ' and Product.id in
				(select source_product from ProductCollectionBinding)';
        }

        if (is_array($this->product_ids)) {
            $clause .= sprintf(
                ' and Product.id in (%s)',
                $this->app->db->datatype->implodeArray(
                    $this->product_ids,
                    'integer'
                )
            );
        }

        if (is_array($this->product_shortnames)) {
            $clause .= sprintf(
                ' and Product.shortname in (%s)',
                $this->app->db->datatype->implodeArray(
                    $this->product_shortnames,
                    'text'
                )
            );
        }

        if ($this->item_minimum_quantity_group !== null) {
            $clause .= sprintf(
                ' and Product.id in (select Item.product
				from Item where minimum_quantity_group = %s)',
                $this->app->db->quote(
                    $this->item_minimum_quantity_group->id,
                    'integer'
                )
            );
        }

        if ($this->instance instanceof SiteInstance) {
            $clause .= sprintf(
                ' and Catalog.id in
				(select catalog from CatalogInstanceBinding where instance = %s)',
                $this->app->db->quote($this->instance->id, 'integer')
            );
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
                $default_order_by
            );
        }

        return $clause;
    }

    // }}}
}
