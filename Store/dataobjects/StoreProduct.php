<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/dataobjects/StoreItemGroupWrapper.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'Store/dataobjects/StoreProductImage.php';
require_once 'Store/dataobjects/StoreProductImageWrapper.php';
require_once 'Store/dataobjects/StoreRegion.php';

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
 * @copyright 2005-2007 silverorange
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
	 * User visible content
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Create date
	 *
	 * @var Date
	 */
	public $createdate;

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
	 * @return StoreProductWrapper Related products to the current product
	 */
	public function getVisibleRelatedProducts($limit = null, $offset = null)
	{
		$sql = 'select Product.*, ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path
			from Product
				inner join VisibleProductCache
					on VisibleProductCache.product = Product.id
						and VisibleProductCache.region = %s
				inner join ProductRelatedProductBinding
					on Product.id = ProductRelatedProductBinding.related_product
						and ProductRelatedProductBinding.source_product = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by ProductRelatedProductBinding.displayorder asc';

		$sql = sprintf($sql,
			$this->db->quote($this->region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		if ($limit !== null)
			$this->db->setLimit($limit, $offset);

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));
	}

	// }}}
	// {{{ public function getVisiblePopularProducts()

	/**
	 * Retrieve popular products visible in the current region
	 *
	 * Popular products are the most frequently ordered products in orders
	 * that include the current product.
	 * Popular products are retrieved with primary categories and ordered by
	 * their popularity. Only products visible in the current region are
	 * returned.
	 *
	 * @param integer $limit the limit of this range.
	 * @param integer $offset optional. The offset of this range. If not
	 *                         specified, defaults to 0.
	 *
	 * @return StoreProductWrapper Popular products of the current product
	 */
	public function getVisiblePopularProducts($limit = null, $offset = null)
	{
		$sql = 'select Product.*, ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path
			from Product
				inner join VisibleProductCache
					on VisibleProductCache.product = Product.id
						and VisibleProductCache.region = %s
				inner join ProductPopularProductBinding
					on Product.id = ProductPopularProductBinding.related_product
						and ProductPopularProductBinding.source_product = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by ProductPopularProductBinding.order_count desc';

		$sql = sprintf($sql,
			$this->db->quote($this->region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		if ($limit !== null)
			$this->db->setLimit($limit, $offset);

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('primary_category',
			SwatDBClassMap::get('StoreCategory'));

		$this->registerInternalProperty('path');
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('catalog',
			SwatDBClassMap::get('StoreCatalog'));

		$this->registerInternalProperty('primary_image',
			SwatDBClassMap::get('StoreProductImage'));

		$this->table = 'Product';
		$this->id_field = 'integer:id';
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
		}

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
		$path = '';

		if ($this->hasInternalValue('path') &&
			$this->getInternalValue('path') !== null) {
			$path = $this->getInternalValue('path').'/'.$this->shortname;
		} elseif ($this->hasInternalValue('primary_category') &&
			$this->getInternalValue('primary_category') !== null) {

			$primary_category = $this->getInternalValue('primary_category');

			$sql = sprintf('select getCategoryPath(%s)',
				$this->db->quote($primary_category, 'integer'));

			$path = SwatDB::queryOne($this->db, $sql);
			$path.= '/'.$this->shortname;
		} else {
			$path = $this->shortname;
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
		$sql = 'select id, title, shortname from Category where id in
			(select category from CategoryProductBinding
			where product = %s)';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreCategoryWrapper'));
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
	// {{{ protected function loadPopularProducts()

	/**
	 * Loads popular products
	 *
	 * Popular products are loaded with primary categories and ordered by
	 * their popularity.
	 *
	 * For a region-aware way to load popular products, see {@link
	 * StoreProduct::getPopularProducts()}
	 */
	protected function loadPopularProducts()
	{
		$sql = 'select Product.*, ProductPrimaryCategoryView.primary_category,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path
			from Product
				inner join ProductPopularProductBinding
					on Product.id = ProductPopularProductBinding.related_product
						and ProductPopularProductBinding.source_product = %s
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			order by ProductPopularProductBinding.order_count desc';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));
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
	 * @return StoreImageWrapper
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
	public function displayAsIcon($link)
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $link;
		$span_tag = new SwatHtmlTag('span');
		$span_tag->setContent(SwatString::ellipsizeRight($this->title, 30));
		$img_tag = $this->getThumbnailImgTag();

		$anchor_tag->open();
		$img_tag->display();
		$span_tag->display();
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
	public function displayAsTile($link)
	{
		require_once 'Swat/SwatString.php';

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $link;
		$anchor_tag->setContent('more&nbsp;»');

		$title_span = new SwatHtmlTag('span');
		$title_span->class = 'store-product-tile-title';
		$title_span->setContent($this->title);

		$img_tag = $this->getThumbnailImgTag();

		$paragraph_tag = new SwatHtmltag('p');
		$summary = SwatString::condense($this->bodytext, 200);
		$paragraph_tag->setContent($summary);

		$anchor_tag->open();
		$img_tag->display();
		$title_span->display();
		$anchor_tag->close();

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
		$span_tag = new SwatHtmlTag('span');
		$span_tag->setContent($this->title);

		$anchor_tag->open();
		$span_tag->display();
		$anchor_tag->close();
	}

	// }}}
	// {{{ protected function getThumbnailImgTag()

	protected function getThumbnailImgTag()
	{
		$img_tag = new SwatHtmlTag('img');

		if ($this->primary_image !== null) {
			$img_tag->src = $this->primary_image->getURI('thumb');
			$img_tag->width = $this->primary_image->thumb_width;
			$img_tag->height = $this->primary_image->thumb_height;
			$img_tag->alt = sprintf(Store::_('Photo of %s'), $this->title);
		} else {
			$class = SwatDBClassMap::get('StoreProductImage');
			$sizes = call_user_func(array($class, 'getSizes'));
			$dimensions = $sizes['thumb'];
			$img_tag->width = $dimensions[0];
			$img_tag->height = $dimensions[1];
			$img_tag->src = $this->getPlaceholderImageFilename();
			$img_tag->alt = '';
		}

		return $img_tag;
	}

	// }}}
	// {{{ protected function getPlaceholderImageFilename()

	protected function getPlaceholderImageFilename()
	{
		return 'packages/store/images/product-placeholder.png';
	}

	// }}}

}

?>
