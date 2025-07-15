<?php

/**
 * A category for an e-commerce web application.
 *
 * Categories are a navigational network that lies beneath the products and
 * items of a store. The sole purpose of categories is to organize products
 * and items into meaningful and navigatable sets.
 *
 * <pre>
 * Category
 * |
 * -- Product
 *    |
 *    -- Item
 * </pre>
 *
 * One category may belong to another category and may contain multiple
 * categories. There is no restriction on placing a single category into
 * multiple categories so categories do not represent a tree structure.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategory extends SwatDBDataObject
{
    /**
     * The maximum depth of categories in the category tree.
     *
     * Objects that interact with categories may choose not to respect
     * categories with a depth greater than this value.
     *
     * The root category is the zero-th level category.
     */
    public const MAX_DEPTH = 8;

    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Short, textual identifier of this category.
     *
     * This identifier is designed to be used in URL's.
     *
     * @var string
     */
    public $shortname;

    /**
     * User visible title.
     *
     * @var string
     */
    public $title;

    /**
     * Optional HTML title.
     *
     * If set, the category page HTML title uses this value. Otherwise, the
     * category page uses the category title from
     * {@link StoreCategory::$title}.
     *
     * @var string
     */
    public $html_title;

    /**
     * Description of this category.
     *
     * This text is intended to be displayed on the parent page of this
     * category.
     *
     * @var string
     */
    public $description;

    /**
     * Body text of this category.
     *
     * This text is intended to be displayed on a page dedicated to this
     * category.
     *
     * @var string
     */
    public $bodytext;

    /**
     * Always visible.
     *
     * Always display this category, even when it contains no enabled products.
     *
     * @var bool
     */
    public $always_visible;

    /**
     * The date this category was created.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * Order of display of this category.
     *
     * @var int
     */
    public $displayorder;

    /**
     * Headline content used for creating pay-per-click ads.
     *
     * This is usually the category title or a shortened version of the
     * category title.
     *
     * @var string
     */
    public $ppc_ad_headline;

    /**
     * Content used for creating pay-per-click ads.
     *
     * @var string
     */
    public $ppc_ad_description1;

    /**
     * Content used for creating pay-per-click ads.
     *
     * @var string
     */
    public $ppc_ad_description2;

    /**
     * @var StoreRegion
     */
    protected $region;

    /**
     * @var bool
     */
    protected $limit_by_region = true;

    /**
     * Cache of product counts for this category indexed by region id.
     *
     * This is an array of integers.
     *
     * @var array
     */
    protected $product_count = [];

    /**
     * Cache of available product counts for this category indexed by region id.
     *
     * This is an array of integers.
     *
     * @var array
     */
    protected $available_product_count = [];

    /**
     * Cache of item counts for this category indexed by region id.
     *
     * This is an array of integers.
     *
     * @var array
     */
    protected $item_count = [];

    /**
     * @var array
     *
     * @see StoreCategory::getNavBarEntries()
     */
    protected $navbar_entries;

    /**
     * @var array
     *
     * @see StoreCategory::getAdminNavBarEntries()
     */
    protected $admin_navbar_entries;

    public function setRegion(StoreRegion $region, $limiting = true)
    {
        $this->region = $region;
        $this->limit_by_region = $limiting;

        // TODO: there is no loadProducts() method
        if ($this->hasSubDataObject('products')) {
            foreach ($this->products as $product) {
                $product->setRegion($region, $limiting);
            }
        }
    }

    /**
     * Loads the count of visible products in this category in a region.
     *
     * If you are calling this method frequently during a single request, it is
     * more efficient to include the 'product_count' and 'region_id' fields in
     * the initial category query.
     *
     * @param StoreRegion $region optional. Region for which to get product
     *                            count. If no region is specified, the region
     *                            set using {@link StoreItem::setRegion()}
     *                            is used.
     *
     * @return int the count of visible products in this category in the
     *             given region
     */
    public function getProductCount(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        // We can set this to zero because if there is a null result in the
        // CategoryVisibleProductCountByRegionCache this is the same as having
        // no products.
        $product_count = 0;

        if ($this->region->id == $region->id
            && isset($this->product_count[$region->id])) {
            $product_count = $this->product_count[$region->id];
        } else {
            $sql = 'select product_count
				from CategoryVisibleProductCountByRegionCache
				where region = %s and category = %s';

            $sql = sprintf(
                $sql,
                $this->db->quote($region->id, 'integer'),
                $this->db->quote($this->id, 'integer')
            );

            $product_count = SwatDB::queryOne($this->db, $sql);
            if ($product_count === null) {
                $product_count = 0;
            }

            $this->product_count[$region->id] = $product_count;
        }

        return $product_count;
    }

    /**
     * Loads the count of available products in this category in a region.
     *
     * If you are calling this method frequently during a single request, it is
     * more efficient to include the 'available_product_count' and 'region_id' fields in
     * the initial category query.
     *
     * @param StoreRegion $region optional. Region for which to get product
     *                            count. If no region is specified, the region
     *                            set using {@link StoreItem::setRegion()}
     *                            is used.
     *
     * @return int the count of available products in this category in the
     *             given region
     */
    public function getAvailableProductCount(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        // We can set this to zero because if there is a null result in the
        // CategoryAvailableProductCountByRegionCache this is the same as having
        // no products.
        $product_count = 0;

        if ($this->region->id == $region->id
            && isset($this->available_product_count[$region->id])) {
            $product_count = $this->available_product_count[$region->id];
        } else {
            $sql = 'select product_count
				from CategoryAvailableProductCountByRegionCache
				where region = %s and category = %s';

            $sql = sprintf(
                $sql,
                $this->db->quote($region->id, 'integer'),
                $this->db->quote($this->id, 'integer')
            );

            $product_count = SwatDB::queryOne($this->db, $sql);
            if ($product_count === null) {
                $product_count = 0;
            }

            $this->available_product_count[$region->id] = $product_count;
        }

        return $product_count;
    }

    /**
     * Loads the count of visible items in this category in a region.
     *
     * If you are calling this method frequently during a single request, it is
     * more efficient to include the 'item_count' and 'region_id' fields in
     * the initial category query.
     *
     * @param StoreRegion $region optional. Region for which to get item
     *                            count. If no region is specified, the region
     *                            set using {@link StoreItem::setRegion()}
     *                            is used.
     *
     * @return int the count of visible items in this category in the
     *             given region
     */
    public function getItemCount(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        // We can set this to zero because if there is a null result in the
        // CategoryVisibleItemCountByRegionCache this is the same as having
        // no products.
        $item_count = 0;

        if ($this->region->id == $region->id
            && isset($this->item_count[$region->id])) {
            $item_count = $this->item_count[$region->id];
        } else {
            $sql = 'select item_count
				from CategoryVisibleItemCountByRegionCache
				where region = %s and category = %s';

            $sql = sprintf(
                $sql,
                $this->db->quote($region->id, 'integer'),
                $this->db->quote($this->id, 'integer')
            );

            $product_count = SwatDB::queryOne($this->db, $sql);
            if ($item_count === null) {
                $item_count = 0;
            }

            $this->item_count[$region->id] = $item_count;
        }

        return $item_count;
    }

    public function getVisibleSubCategories(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                'Region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        $sql = 'select Category.*,
				c.product_count, c.region as region_id
			from Category
			left outer join CategoryVisibleProductCountByRegionCache as c
				on c.category = Category.id and c.region = %1$s
			where parent = %2$s
			and id in
				(select Category from VisibleCategoryView
				where region = %1$s or region is null)
			order by displayorder, title';

        $sql = sprintf(
            $sql,
            $this->db->quote($region->id, 'integer'),
            $this->db->quote($this->id, 'integer')
        );

        $wrapper_class = SwatDBClassMap::get(StoreCategoryWrapper::class);
        $sub_categories = SwatDB::query($this->db, $sql, $wrapper_class);
        $sub_categories->setRegion($region);

        return $sub_categories;
    }

    public function getVisibleProducts(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                'Region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        $sql = 'select Product.*,
				ProductPrimaryCategoryView.primary_category,
				ProductPrimaryImageView.image as primary_image,
				getCategoryPath(ProductPrimaryCategoryView.primary_category) as
					path
			from Product
			inner join CategoryProductBinding
				on CategoryProductBinding.product = Product.id
			inner join VisibleProductCache on
				VisibleProductCache.product = Product.id and
				VisibleProductCache.region = %1$s
			left outer join ProductPrimaryCategoryView on
				ProductPrimaryCategoryView.product = Product.id
			left outer join ProductPrimaryImageView
				on ProductPrimaryImageView.product = Product.id
			inner join AvailableProductView on
				AvailableProductView.product = Product.id and
				AvailableProductView.region = %1$s
			where CategoryProductBinding.category = %2$s
			order by CategoryProductBinding.displayorder, Product.title';

        $sql = sprintf(
            $sql,
            $this->db->quote($region->id, 'integer'),
            $this->db->quote($this->id, 'integer')
        );

        $wrapper_class = SwatDBClassMap::get(StoreProductWrapper::class);
        $products = SwatDB::query($this->db, $sql, $wrapper_class);
        $products->setRegion($region);

        return $products;
    }

    /**
     * Gets the set of {@link SwatNavBarEntry} objects for this category.
     *
     * @return array the set of SwatNavBarEntry objects for this category
     */
    public function getNavBarEntries()
    {
        if ($this->navbar_entries === null) {
            $this->navbar_entries = [];

            $path = 'store';
            foreach ($this->queryNavBar() as $row) {
                $path .= '/' . $row->shortname;
                $this->navbar_entries[] =
                    new SwatNavBarEntry($row->title, $path);
            }
        }

        return $this->navbar_entries;
    }

    /**
     * Gets the set of {@link SwatNavBarEntry} objects for this category
     * with links for the admin site.
     *
     * @return array the set of SwatNavBarEntry objects for this category
     */
    public function getAdminNavBarEntries()
    {
        if ($this->admin_navbar_entries === null) {
            $this->admin_navbar_entries = [];

            foreach ($this->queryNavBar() as $row) {
                $link = sprintf('Category/Index?id=%s', $row->id);
                $this->admin_navbar_entries[] =
                    new SwatNavBarEntry($row->title, $link);
            }
        }

        return $this->admin_navbar_entries;
    }

    /**
     * Loads a category from the database with a path.
     *
     * @param string $path   the path of the article in the category graph.
     *                       Category nodes are separated by a '/' character.
     * @param array  $fields the category fields to load from the database. By
     *                       default, only the id and title are loaded. The
     *                       path pseudo-field is always populated from the
     *                       <code>$path</code> parameter.
     *
     * @return bool true if a category was successfully loaded and false if
     *              no category was found at the specified path
     */
    public function loadByPath(
        $path,
        StoreRegion $region,
        $fields = ['id', 'title']
    ) {
        $this->checkDB();

        $found = false;

        $id_field = new SwatDBField($this->id_field, 'integer');
        foreach ($fields as &$field) {
            $field = $this->table . '.' . $field;
        }

        $sql = 'select %1$s from
				findCategory(%2$s)
			inner join %3$s on findCategory = %3$s.%4$s
			inner join VisibleCategoryView on
				findCategory = VisibleCategoryView.category and
				(VisibleCategoryView.region = %5$s or
					VisibleCategoryView.region is null)';

        $sql = sprintf(
            $sql,
            implode(', ', $fields),
            $this->db->quote($path, 'text'),
            $this->table,
            $id_field->name,
            $this->db->quote($region->id, 'integer')
        );

        $row = SwatDB::queryRow($this->db, $sql);
        if ($row !== null) {
            $this->initFromRow($row);
            $this->setRegion($region);
            $this->setInternalValue('path', $path);
            $this->generatePropertyHashes();
            $found = true;
        }

        return $found;
    }

    protected function init()
    {
        $this->registerDeprecatedProperty('ppc_ad_text');
        $this->registerDateProperty('createdate');

        $this->registerInternalProperty('path');
        $this->registerInternalProperty(
            'image',
            SwatDBClassMap::get(StoreCategoryImage::class)
        );

        $this->registerInternalProperty(
            'parent',
            SwatDBClassMap::get(StoreCategory::class)
        );

        $this->table = 'Category';
        $this->id_field = 'integer:id';
    }

    /**
     * Initializes this category from a row object.
     *
     * If the row object has a 'region_id' field and the fields
     * 'product_count' the product_count value is cached for subsequent calls
     * to the getProductCount() method.
     *
     * @param mixed $row
     */
    protected function initFromRow($row)
    {
        parent::initFromRow($row);

        if (is_object($row)) {
            $row = get_object_vars($row);
        }

        if (isset($row['region_id'])) {
            if (isset($row['product_count'])) {
                $this->product_count[$row['region_id']] = $row['product_count'];
            }

            if (isset($row['item_count'])) {
                $this->item_count[$row['region_id']] = $row['item_count'];
            }

            if (isset($row['available_product_count'])) {
                $this->available_product_count[$row['region_id']] =
                    $row['available_product_count'];
            }
        }
    }

    protected function getSerializableSubDataObjects()
    {
        return array_merge(
            parent::getSerializableSubDataObjects(),
            ['image', 'related_articles', 'path']
        );
    }

    protected function getSerializablePrivateProperties()
    {
        return array_merge(
            parent::getSerializablePrivateProperties(),
            ['region', 'limit_by_region', 'product_count',
                'available_product_count', 'item_count', 'navbar_entries']
        );
    }

    protected function deleteInternal()
    {
        parent::deleteInternal();

        if ($this->getInternalValue('image') !== null) {
            $this->image->delete();
        }
    }

    // loader methods

    /**
     * Loads the URL fragment of this category.
     *
     * If the path was part of the initial query to load this category, that
     * value is returned. Otherwise, a separate query gets the path of this
     * category. If you are calling this method frequently during a single
     * request, it is more efficient to include the path in the initial
     * category query.
     */
    protected function loadPath()
    {
        $path = '';

        if ($this->hasInternalValue('path')
            && $this->getInternalValue('path') !== null) {
            $path = $this->getInternalValue('path');
        } else {
            $sql = sprintf(
                'select getCategoryPath(%s)',
                $this->db->quote($this->id, 'integer')
            );

            $path = SwatDB::queryOne($this->db, $sql);
        }

        return $path;
    }

    /**
     * Loads related articles.
     *
     * Related articles are ordered by the article table's display order.
     *
     * @see StoreArticle::loadRelatedCategories()
     */
    protected function loadRelatedArticles(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        $sql = 'select Article.*, getArticlePath(Article.id) as path
			from Article
				inner join ArticleCategoryBinding
					on Article.id = ArticleCategoryBinding.article
						and ArticleCategoryBinding.category = %s
				inner join EnabledArticleView
					on Article.id = EnabledArticleView.id
						and EnabledArticleView.region = %s
			order by Article.displayorder asc';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer'),
            $this->db->quote($region->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(SiteArticleWrapper::class)
        );
    }

    /**
     * Loads the sub-categories of this category.
     *
     * @return StoreCategoryWrapper a recordset of sub-categories of the
     *                              specified category
     */
    protected function loadSubCategories()
    {
        $sql = 'select id, shortname, title, getCategoryPath(id) as path
			from Category
			where parent = %s and id in (
				select id from VisibleCategoryView
				where region = %s or region is null
			) order by displayorder, title';

        if ($this->region === null) {
            throw new StoreException('Region not set on the category ' .
                'dataobject; call the setRegion() method.');
        }

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer'),
            $this->db->quote($this->region->id, 'integer')
        );

        $wrapper = SwatDBClassMap::get(StoreCategoryWrapper::class);
        $categories = SwatDB::query($this->db, $sql, $wrapper);

        foreach ($categories as $category) {
            $category->setRegion($this->region);
        }

        return $categories;
    }

    /**
     * Helper method for loading navbar entries of this category.
     */
    protected function queryNavBar()
    {
        $sql = sprintf(
            'select * from getCategoryNavbar(%s)',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query($this->db, $sql);
    }

    // display methods

    /**
     * Displays the category as:
     *
     * [ IMAGE ] _Title_
     *
     * @param string $link the link to use when displaying. This link should go
     *                     to this category's page.
     */
    public function displayAsTile($link)
    {
        $anchor_tag = new SwatHtmlTag('a');
        $anchor_tag->class = 'store-category-tile-link';
        $anchor_tag->href = $link;

        $title_span = new SwatHtmlTag('span');
        $title_span->class = 'store-category-tile-title';
        $title_span->setContent($this->title);

        $anchor_tag->open();

        $img_tag = $this->getThumbnailImgTag();
        $img_tag->display();

        $unavailable_span = $this->getUnavailableSpan();
        if ($unavailable_span !== null) {
            $unavailable_span->display();
        }

        $title_span->display();
        $anchor_tag->close();
        echo ' ';

        $details_span = $this->getDetailsSpan();
        if ($details_span !== null) {
            $details_span->display();
        }

        if ($this->description != '') {
            $description_p = new SwatHtmlTag('p');
            $description_p->class = 'store-category-description';
            $description_p->setContent($this->description);
            $description_p->display();
        }
    }

    public function getThumbnailImgTag()
    {
        return $this->getImgTag('thumb');
    }

    public function getImgTag($dimension_shortname = 'thumb')
    {
        if ($this->image !== null) {
            $img_tag = $this->image->getImgTag($dimension_shortname);

            if ($img_tag->alt == '') {
                $img_tag->alt = sprintf(Store::_('Image of %s'), $this->title);
            }
        } else {
            $class = SwatDBClassMap::get(SiteImageDimension::class);
            $dimension = new $class();
            $dimension->setDatabase($this->db);
            $dimension->loadByShortname('categories', $dimension_shortname);
            $img_tag = new SwatHtmlTag('img');
            $img_tag->width = $dimension->max_width;
            $img_tag->height = $dimension->max_height;
            $img_tag->src = $this->getPlaceholderImageFilename();
            $img_tag->alt = '';
        }

        return $img_tag;
    }

    protected function getPlaceholderImageFilename()
    {
        return 'packages/store/images/category-placeholder.png';
    }

    protected function getUnavailableSpan()
    {
        $span = null;

        if (!$this->always_visible
            && $this->getAvailableProductCount() == 0) {
            $span = new SwatHtmlTag('span');
            $span->setContent('');
            $span->title = 'Out of Stock';
            $span->class = 'category-unavailable';
        }

        return $span;
    }

    protected function getDetailsSpan()
    {
        $span = null;

        if ($this->getProductCount() > 1) {
            $span = new SwatHtmlTag('span');
            $span->class = 'store-category-tile-details';
            $span->setContent(sprintf(
                ngettext('%s item', '%s items', $this->getProductCount()),
                $this->getProductCount()
            ));
        }

        return $span;
    }
}
