<?php

/**
 * A product for an e-commerce web application
 *
 * Products are in the middle of the product structure. Each product can have
 * multiple items and can belong to multiple categories. Procucts are usually
 * displayed on product pages. A product is different from an item in that
 * the product contains a very general idea of what is available and an item
 * describes an exact item that a customer can purchase.
 *
 * <pre>
 * Category
 * |
 * -- Product
 *    |
 *    -- Item
 * </pre>
 *
 * Ideally, products are displayed one to a page but it is possible to display
 * many products on one page.
 *
 * If there are many StoreProduct objects that must be loaded for a page
 * request, the MDB2 wrapper class called StoreProductWrapper should be used to
 * load the objects.
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreProductWrapper
 */
class StoreProduct extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * A short textual identifier for this product
	 *
	 * This identifier is designed to be used in URL's and must be unique
	 * within a catalog.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Optional HTML title
	 *
	 * If set, the product page HTML title uses this value. Otherwise, the
	 * product page uses the product title from {@link StoreProduct::$title}.
	 *
	 * @var string
	 */
	public $html_title;

	/**
	 * Keywords used by the search indexer
	 *
	 * @var string
	 */
	public $keywords;

	/**
	 * User visible content
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Optional content displayed in the meta description tag for this
	 * product's product page
	 *
	 * If not set, a shortened version of the product bodytext is used.
	 *
	 * @var string
	 *
	 * @see StoreProductPage
	 */
	public $meta_description;

	/**
	 * Create date
	 *
	 * @var SwatDate
	 */
	public $createdate;

	/**
	 * Headline content used for creating pay-per-click ads
	 *
	 * This is usually the product title or a shortened version of the product
	 * title.
	 *
	 * @var string
	 */
	public $ppc_ad_headline;

	/**
	 * Content used for creating pay-per-click ads
	 *
	 * @var string
	 */
	public $ppc_ad_description1;

	/**
	 * Content used for creating pay-per-click ads
	 *
	 * @var string
	 */
	public $ppc_ad_description2;

	// }}}
	// {{{ protected properties

	/**
	 * The region to use when loading region-specific fields in item sub-data-
	 * objects
	 *
	 * @var StoreRegion
	 * @see StoreProduct::setRegion()
	 */
	protected $region = null;

	/**
	 * Whether or not to exclude items unavailable in the current join region
	 * when loading item sub-data-objects
	 *
	 * @var boolean
	 * @see StoreProduct::setRegion()
	 */
	protected $limit_by_region = true;

	/**
	 * Cache of availability of this product indexed by region id
	 *
	 * This is an array of boolean values.
	 *
	 * @var array
	 * @see StoreProduct::isAvailableInRegion()
	 */
	protected $is_available = array();

	/**
	 * Cache of item count
	 *
	 * @var integer
	 */
	protected $item_count;

	// }}}
	// {{{ public function setRegion()

	/**
	 * Sets the region to use when loading region-specific fields for item
	 * sub-data-objects
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to exclude items unavailable in
	 *                           the current join region when loading item
	 *                           sub-data-objects.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		$this->region = $region;
		$this->limit_by_region = $limiting;

		if ($this->hasSubDataObject('items'))
			foreach ($this->items as $item)
				$item->setRegion($region, $limiting);
	}

	// }}}
	// {{{ public function getVisibleRelatedProducts()

	/**
	 * Retrieve related products in the current region
	 *
	 * Related products are retrieved with primary categories and ordered by
	 * their popularity. Only products visible in the current region are
	 * returned.
	 *
	 * @param integer $limit the limit of this range.
	 * @param integer $offset optional. The offset of this range. If not
	 *                         specified, defaults to 0.
	 *
	 * @return StoreProductWrapper related products to the current product.
	 */
	public function getVisibleRelatedProducts($limit = null, $offset = null)
	{
		$sql = 'select Product.*, ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as
				path,
			case when AvailableProductView.product is null then false
				else true
				end as is_available,
			VisibleProductCache.region as region_id
			from Product
				inner join VisibleProductCache
					on VisibleProductCache.product = Product.id
						and VisibleProductCache.region = %s
				inner join ProductRelatedProductBinding
					on Product.id = ProductRelatedProductBinding.related_product
						and ProductRelatedProductBinding.source_product = %s
				left outer join AvailableProductView on
					AvailableProductView.product = Product.id and
					AvailableProductView.region = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by ProductRelatedProductBinding.displayorder asc';

		$sql = sprintf($sql,
			$this->db->quote($this->region->id, 'integer'),
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($this->region->id, 'integer'));

		if ($limit !== null)
			$this->db->setLimit($limit, $offset);

		$related_products = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));

		$related_products->setRegion($this->region);
		return $related_products;
	}

	// }}}
	// {{{ public function isAvailableInRegion()

	/**
	 * Gets whether or not this product is available in a particular region
	 *
	 * A product is available in a region if it has one or more avaialable
	 * items in the region.
	 *
	 * If you are calling this method frequently during a single request, it is
	 * more efficient to include 'is_available' and 'region_id' in the initial
	 * product query by left outer joining the AvailableProductView. Otherwise,
	 * an additional query needs to be performed to get region availablilty.
	 *
	 * @param StoreRegion $region optional. The region in which to check if this
	 *                             product is available. If not specified, the
	 *                             last region specified by the
	 *                             {@link StoreProduct::setRegion()} method is
	 *                             used.
	 *
	 * @return boolean true if and only if this product is available in the
	 *                  specified region.
	 */
	public function isAvailableInRegion(StoreRegion $region = null)
	{
		if ($region === null)
			$region = $this->region;

		if ($region === null)
			throw new SwatException('Region must be specified or region must '.
				'be set on this product before availability is known.');

		if ($region->id === null)
			throw new StoreException('Region have an id set before '.
				'availability can be determined for this product.');

		$available = '';

		if (isset($this->is_available[$region->id])) {
			$available = $this->is_available[$region->id];
		} else {
			$this->checkDB();

			if ($this->id === null)
				throw new StoreException('Product must have an id set before '.
					'availability can be determined for this region.');

			$sql = sprintf('select count(product) from AvailableProductView
				where AvailableProductView.product = %s
					and AvailableProductView.region = %s',
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($region->id, 'integer'));

			$available = (SwatDB::queryOne($this->db, $sql) > 0);
			$this->is_available[$region->id] = $available;
		}

		return $available;
	}

	// }}}
	// {{{ public function getItemCount()

	/**
	 * Get the number of items that belong to this product
	 *
	 * @return integer
	 */
	public function getItemCount()
	{
		if ($this->item_count === null)
			$this->item_count = count($this->items);

		return $this->item_count;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDeprecatedProperty('ppc_ad_text');
		$this->registerInternalProperty('primary_category',
			SwatDBClassMap::get('StoreCategory'));

		$this->registerInternalProperty('path');
		$this->registerInternalProperty('cheapest_item');
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('catalog',
			SwatDBClassMap::get('StoreCatalog'));

		$this->registerInternalProperty('primary_image',
			SwatDBClassMap::get('StoreProductImage'));

		$this->table = 'Product';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function initFromRow()

	/**
	 * Initializes this product from a row object
	 *
	 * If the row object has a 'region_id' field and an 'is_available' field,
	 * the 'is_available' field is cached for subsequent calls to the
	 * {@link StoreProduct::isAvailableInRegion()} method.
	 *
	 * @param mixed $row an MDB2 result row.
	 */
	protected function initFromRow($row)
	{
		parent::initFromRow($row);

		if (is_object($row))
			$row = get_object_vars($row);

		if (isset($row['item_count']))
			$this->item_count = $row['item_count'];

		if (isset($row['region_id'])) {
			if (isset($row['price']))
				$this->price[$row['region_id']] = $row['price'];

			if (isset($row['enabled']))
				$this->is_enabled[$row['region_id']] = $row['enabled'];

			if (isset($row['is_available']))
				$this->is_available[$row['region_id']] = $row['is_available'];
		}
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array_merge(
			parent::getSerializableSubDataObjects(),
			array(
				'primary_category', 'primary_image', 'cheapest_item',
				'items', 'item_groups', 'categories', 'attributes',
				'featured_categories', 'related_products',
				'related_articles', 'images', 'catalog', 'path',
				'collection_products'
			)
		);
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(),
			array('region', 'limit_by_region', 'is_available'));
	}

	// }}}

	// loader methods
	// {{{ protected function loadItems()

	/**
	 * Loads item sub-data-objects for this product
	 *
	 * If you want to load region-specific fields on the items, call the
	 * {@link StoreProduct::setRegion()} method first.
	 *
	 * @see StoreProduct::setRegion()
	 */
	protected function loadItems()
	{
		$items = null;
		$wrapper = SwatDBClassMap::get('StoreItemWrapper');

		if ($this->region === null) {
			$sql = 'select id from Item where product = %s';
			$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
			$items = call_user_func(array($wrapper, 'loadSetFromDB'),
				$this->db, $sql);
		} else {
			$sql = 'select id from Item where product = %s';
			$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
			$items = call_user_func(array($wrapper, 'loadSetFromDBWithRegion'),
				$this->db, $sql, $this->region, $this->limit_by_region);

			$item_ids = array();
			foreach ($items as $item)
				$item_ids[] = $item->id;

			// load qty discounts here
			$class = SwatDBClassMap::get('StoreQuantityDiscountWrapper');
			$wrapper = new $class();
			$quantity_discounts = $wrapper->loadSetFromDB($this->db,
				$item_ids, $this->region, $this->limit_by_region);

			foreach ($items as $item) {
				$discounts = new $class();
				foreach ($quantity_discounts as $discount) {
					if ($discount->getInternalValue('item') == $item->id) {
						$discount->item = $item;
						$discounts->add($discount);
					}
				}

				$item->quantity_discounts = $discounts;
			}
		}

		foreach ($items as $item)
			$item->product = $this;

		return $items;
	}

	// }}}
	// {{{ protected function loadPath()

	/**
	 * Loads the URL fragment of this product
	 *
	 * If the path was part of the initial query to load this product, that
	 * value is returned. Otherwise, a separate query gets the path of this
	 * product. If you are calling this method frequently during a single
	 * request, it is more efficient to include the path in the initial
	 * product query.
	 */
	protected function loadPath()
	{
		$path = $this->shortname;

		if ($this->hasInternalValue('path') &&
			$this->getInternalValue('path') !== null) {
				$path = $this->getInternalValue('path').'/'.$this->shortname;

		} elseif ($this->hasSubDataObject('primary_category')) {
			$path = $this->primary_category->path.'/'.$this->shortname;

		} elseif ($this->hasInternalValue('primary_category') &&
			$this->getInternalValue('primary_category') !== null) {

			$sql = sprintf('select getCategoryPath(%s)',
				$this->db->quote($this->getInternalValue(
					'primary_category'), 'integer'));

			$category_path = SwatDB::queryOne($this->db, $sql);
			if ($category_path !== null)
				$path = $category_path.'/'.$this->shortname;

		} else {
			$sql = sprintf('select getCategoryPath(primary_category)
					from ProductPrimaryCategoryView
					where product = %s',
					$this->db->quote($this->id, 'integer'));

			$category_path = SwatDB::queryOne($this->db, $sql);
			if ($category_path !== null)
				$path = $category_path.'/'.$this->shortname;
		}

		return $path;
	}

	// }}}
	// {{{ protected function loadItemGroups()

	protected function loadItemGroups()
	{
		$sql = 'select * from ItemGroup
			where product = %s order by displayorder';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreItemGroupWrapper'));
	}

	// }}}
	// {{{ protected function loadCategories()

	protected function loadCategories()
	{
		$sql = 'select id, title, shortname, parent
			from Category where id in
				(select category from CategoryProductBinding
			where product = %s)';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreCategoryWrapper'));
	}

	// }}}
	// {{{ protected function loadAttributes()

	protected function loadAttributes()
	{
		$sql = sprintf('select * from Attribute
			where id in (
			select attribute from ProductAttributeBinding where product = %s)
			order by displayorder asc',
			$this->db->quote($this->id, 'integer'));

		$attributes = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreAttributeWrapper'));

		return $attributes;
	}

	// }}}
	// {{{ protected function loadFeaturedCategories()

	protected function loadFeaturedCategories()
	{
		$sql = 'select id, title, shortname from Category where id in
			(select category from CategoryFeaturedProductBinding
			where product = %s)';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreCategoryWrapper'));
	}

	// }}}
	// {{{ protected function loadRelatedProducts()

	/**
	 * Loads related products
	 *
	 * Related products are loaded with primary categories and ordered by the
	 * binding table's display order.
	 *
	 * For a region-aware way to load related products, see {@link
	 * StoreProduct::getRelatedProducts()}
	 */
	protected function loadRelatedProducts()
	{
		$sql = 'select Product.*, ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path
			from Product
				inner join ProductRelatedProductBinding
					on Product.id = ProductRelatedProductBinding.related_product
						and ProductRelatedProductBinding.source_product = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by ProductRelatedProductBinding.displayorder asc';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));
	}

	// }}}
	// {{{ protected function loadRelatedArticles()

	/**
	 * Loads related articles
	 *
	 * Related articles are ordered by the article table's display order.
	 *
	 * @see StoreArticle::loadRelatedProducts()
	 */
	protected function loadRelatedArticles(StoreRegion $region = null)
	{
		if ($region === null)
			$region = $this->region;

		if ($region === null)
			throw new StoreException(
				'$region must be specified unless setRegion() is called '.
				'beforehand.');

		$sql = 'select Article.*, getArticlePath(article.id) as path
			from Article
				inner join ArticleProductBinding
					on Article.id = ArticleProductBinding.article
						and ArticleProductBinding.product = %s
				inner join EnabledArticleView
					on Article.id = EnabledArticleView.id
						and EnabledArticleView.region = %s
			order by Article.displayorder asc';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($region->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteArticleWrapper'));
	}

	// }}}
	// {{{ protected function loadCollectionProducts()

	/**
	 * Loads the collections that this product belongs to
	 *
	 * Collections products are loaded with primary categories.
	 * To load only available collection products, see {@link
	 * StoreProduct::getVisibleCollectionProducts}.
	 */
	protected function loadCollectionProducts()
	{
		$sql = 'select Product.*,
			ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path
			from Product
				inner join ProductCollectionBinding
					on Product.id = ProductCollectionBinding.source_product
						and ProductCollectionBinding.member_product = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by Product.title asc';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));
	}

	// }}}
	// {{{ protected function loadCollectionMemberProducts()

	/**
	 * Loads the member products of this collection product
	 *
	 * Member products are loaded with primary categories.
	 */
	protected function loadCollectionMemberProducts()
	{
		$sql = 'select Product.*,
			ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path
			from Product
				inner join ProductCollectionBinding
					on Product.id = ProductCollectionBinding.member_product
						and ProductCollectionBinding.source_product = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by Product.title asc';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));
	}

	// }}}
	// {{{ protected function loadCheapestItem()

	/**
	 * Loads the cheapest item of this product
	 *
	 * If the cheapest item was part of the initial query to load this product,
	 * that value is returned. Otherwise, a separate query gets the cheapest
	 * item of this product. If you are calling this method frequently during
	 * a single request, it is more efficient to include the cheapest item in
	 * the initial product query.
	 */
	protected function loadCheapestItem()
	{
		$cheapest_item = null;

		if ($this->hasInternalValue('cheapest_item') &&
			$this->getInternalValue('cheapest_item') !== null) {
			$cheapest_item_id = $this->getInternalValue('cheapest_item');

			$sql = 'select * from Item where id = %s';

			$sql = sprintf($sql,
				$this->db->quote($cheapest_item_id, 'integer'));
		} else {
			$sql = 'select * from Item where id in
				(select getProductCheapestItem(%s, %s))';

			$sql = sprintf($sql,
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($this->region->id, 'integer'));
		}

		$wrapper = SwatDBClassMap::get('StoreItemWrapper');
		$cheapest_item = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

		if ($cheapest_item != null)
			$cheapest_item->setRegion($this->region);

		return $cheapest_item;
	}

	// }}}
	// {{{ protected function loadPrimaryImage()

	/**
	 * Loads the primary image of this product
	 *
	 * If the primary image was part of the initial query to load this product,
	 * that value is returned. Otherwise, a separate query gets the primary
	 * image of this product. If you are calling this method frequently during
	 * a single request, it is more efficient to include the primary image in
	 * the initial product query.
	 */
	protected function loadPrimaryImage()
	{
		$primary_image = null;

		if ($this->hasInternalValue('primary_image') &&
			$this->getInternalValue('primary_image') !== null) {
			$primary_image_id = $this->getInternalValue('primary_image');

			$sql = 'select * from Image where id = %s';

			$sql = sprintf($sql,
				$this->db->quote($primary_image_id, 'integer'));
		} else {
			$sql = 'select * from Image where id in
				(select image from ProductPrimaryImageView
				where product = %s)';

			$sql = sprintf($sql,
				$this->db->quote($this->id, 'integer'));
		}

		$wrapper = SwatDBClassMap::get('StoreProductImageWrapper');
		$rs = SwatDB::query($this->db, $sql, $wrapper);
		$primary_image = $rs->getFirst();

		return $primary_image;
	}

	// }}}
	// {{{ protected function loadImages()

	/**
	 * Loads images for this product
	 *
	 * @return StoreProductImageWrapper
	 */
	protected function loadImages()
	{
		$sql = 'select Image.* from Image
			inner join ProductImageBinding
				on ProductImageBinding.image = Image.id
			where ProductImageBinding.product = %s
			order by ProductImageBinding.displayorder';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductImageWrapper'));
	}

	// }}}

	// saver methods
	// {{{ protected function saveItems()

	/**
	 * Automatically saves StoreItem sub-data-objects when this
	 * StoreProduct object is saved
	 */
	protected function saveItems()
	{
		foreach ($this->items as $item)
			$item->product = $this;

		$this->items->setDatabase($this->db);
		$this->items->save();
	}

	// }}}

	// display methods
	// {{{ public function displayAsIcon()

	/**
	 * Displays this product as:
	 *
	 * [IMAGE]
	 * _Title_
	 */
	public function displayAsIcon($link, $image_size = 'thumb')
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->class = sprintf('product-icon-%s', $image_size);
		$anchor_tag->href = $link;
		$anchor_tag->open();

		$img_tag = $this->getThumbnailImgTag($image_size);
		$img_tag->display();

		$unavailable_span = $this->getUnavailableSpan();
		if ($unavailable_span !== null)
			$unavailable_span->display();

		$span = new SwatHtmlTag('span');
		$span->setContent(SwatString::ellipsizeRight($this->title, 40));
		$span->display();

		$anchor_tag->close();
	}

	// }}}
	// {{{ public function displayAsTile()

	/**
	 * Displays the product as:
	 *
	 * [ IMAGE ] _Title_
	 *           Summarized description ... _more >>_
	 */
	public function displayAsTile($link, $image_size = 'thumb')
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $link;
		$anchor_tag->setContent('more&nbsp;»');
		$anchor_tag->open();

		$img_span = new SwatHtmlTag('span');
		$img_span->class = 'tile-image-wrapper';

		$img_tag = $this->getThumbnailImgTag($image_size);

		$img_span->open();
		$img_tag->display();
		$img_span->close();

		$unavailable_span = $this->getUnavailableSpan();
		if ($unavailable_span !== null)
			$unavailable_span->display();

		$title_span = new SwatHtmlTag('span');
		$title_span->class = 'store-product-tile-title';
		$title_span->setContent($this->title);
		$title_span->display();

		$anchor_tag->close();

		$paragraph_tag = new SwatHtmlTag('p');
		$summary = SwatString::condense($this->bodytext, 200);
		$paragraph_tag->setContent($summary);
		$paragraph_tag->open();
		$paragraph_tag->displayContent();
		echo ' ';
		$anchor_tag->display();
		$paragraph_tag->close();
	}

	// }}}
	// {{{ public function displayAsText()

	/**
	 * Displays this product as:
	 *
	 * _Title_
	 */
	public function displayAsText($link)
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $link;
		$span = new SwatHtmlTag('span');
		$span->setContent($this->title);

		$anchor_tag->open();
		$span->display();
		$anchor_tag->close();
	}

	// }}}
	// {{{ public function getThumbnailImgTag()

	public function getThumbnailImgTag($size = 'thumb')
	{
		if ($this->primary_image !== null) {
			$img_tag = $this->primary_image->getImgTag($size);

			if ($img_tag->alt == '')
				$img_tag->alt = sprintf(Store::_('Image of %s'), $this->title);
		} else {
			$class = SwatDBClassMap::get('SiteImageDimension');
			$dimension = new $class();
			$dimension->setDatabase($this->db);
			$dimension->loadByShortname('products', $size);
			$img_tag = new SwatHtmlTag('img');
			$img_tag->width = $dimension->max_width;
			$img_tag->height = $dimension->max_height;
			$img_tag->src = $this->getPlaceholderImageFilename();
			$img_tag->alt = '';
		}

		return $img_tag;
	}

	// }}}
	// {{{ protected function getUnavailableSpan()

	protected function getUnavailableSpan()
	{
		$span = null;

		if (!$this->isAvailableInRegion()) {
			$span = new SwatHtmlTag('span');
			$span->setContent('');
			$span->title = 'Out of Stock';
			$span->class = 'product-unavailable';
		}

		return $span;
	}

	// }}}
	// {{{ protected function getPlaceholderImageFilename()

	protected function getPlaceholderImageFilename()
	{
		return 'packages/store/images/product-placeholder.png';
	}

	// }}}

	// serialization
	// {{{ public function unserialize()

	public function unserialize(string $data): void
	{
		parent::unserialize($data);

		if ($this->hasSubDataObject('items'))
			foreach ($this->items as $item)
				$item->product = $this;
	}

	// }}}
}

?>
