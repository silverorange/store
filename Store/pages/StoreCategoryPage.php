<?php

require_once 'Swat/SwatString.php';
require_once 'Store/pages/StorePage.php';
require_once 'Store/dataobjects/StoreLocaleWrapper.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/dataobjects/StoreProductImageWrapper.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
class StoreCategoryPage extends StorePage
{
	// init phase
	// {{{ public function isVisibleInRegion()

	public function isVisibleInRegion(StoreRegion $region)
	{
		$category = null;

		if ($this->path !== null) {
			$path_entry = $this->path->getLast();
			if ($path_entry !== null) {
				$category_id = $path_entry->id;

				$sql = sprintf('select category from VisibleCategoryView
					where category = %s and (region = %s or region is null)',
					$this->app->db->quote($category_id, 'integer'),
					$this->app->db->quote($region->id, 'integer'));

				$category = SwatDB::queryOne($this->app->db, $sql);
			}
		}

		return ($category !== null);
	}

	// }}}
	// {{{ protected function getSelectedCategoryId()

	protected function getSelectedCategoryId()
	{
		$category_id = null;

		if ($this->path !== null) {
			$path_entry = $this->path->getLast();
			if ($path_entry !== null)
				$category_id = $path_entry->id;
		}

		return $category_id;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildNavBar();
		$category_id = $this->path->getLast()->id;
		$category = $this->queryCategory($category_id);

		$this->layout->data->title =
			SwatString::minimizeEntities($category->title);

		$this->layout->data->description =
			SwatString::minimizeEntities($category->description);

		if (strlen($category->bodytext) > 0)
			$this->layout->data->content=
				'<div class="store-category-bodytext">'.
				SwatString::toXHTML($category->bodytext).'</div>';
		else
			$this->layout->data->content= '';

		if ($category->description === null) {
			$this->layout->data->meta_description =
				SwatString::minimizeEntities(SwatString::condense(
				SwatString::stripXHTMLTags($category->bodytext, 400)));
		} else {
			$this->layout->data->meta_description =
				SwatString::minimizeEntities($category->description);
		}

		$this->layout->startCapture('content');
		$this->displayRelatedArticles($category);
		$this->layout->endCapture();

		$this->buildPage($category);
	}

	// }}}
	// {{{ protected function buildPage()

	protected function buildPage($category)
	{
		$this->layout->startCapture('content');
		$this->displayFeaturedProducts($category->id);
		$this->displayCategory($category->id);
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function querySubCategories()

	protected function querySubCategories($category_id = null)
	{
		$sql = 'select Category.id, Category.title, Category.shortname,
				Category.description, Category.image,
				c.product_count, c.region as region_id
			from Category
			left outer join CategoryVisibleProductCountByRegionCache as c
				on c.category = Category.id and c.region = %s
			where parent %s %s
			and id in 
				(select Category from VisibleCategoryView
				where region = %s or region is null)
			order by displayorder, title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			SwatDB::equalityOperator($category_id),
			$this->app->db->quote($category_id, 'integer'),
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$wrapper_class = SwatDBClassMap::get('StoreCategoryWrapper');
		$sub_categories = SwatDB::query($this->app->db, $sql, $wrapper_class);
		$sub_categories->setRegion($this->app->getRegion());

		if (count($sub_categories) == 0)
			return $sub_categories;

		$sql = 'select * from Image where id in (%s)';
		$wrapper_class = SwatDBClassMap::get('StoreCategoryImageWrapper');
		$sub_categories->loadAllSubDataObjects(
			'image', $this->app->db, $sql, $wrapper_class);

		return $sub_categories;
	}

	// }}}
	// {{{ protected function displaySubCategories()

	protected function displaySubCategories($category_id = null)
	{
		$sub_categories = $this->querySubCategories($category_id);

		if (count($sub_categories) == 0)
			return;

		echo '<ul class="store-category-list">';

		foreach ($sub_categories as $category) {
			echo '<li class="store-category-tile">';
			$link = $this->source.'/'.$category->shortname;
			$category->displayAsTile($link);
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displayProducts()

	protected function displayProducts($products, $path = null)
	{
		if ($path === null)
			$path = $this->source;

		echo '<ul class="store-product-list">';

		foreach ($products as $product) {
			echo '<li class="store-product-icon">';
			$link = $path.'/'.$product->shortname;
			$product->displayAsIcon($link);
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function queryProducts()

	protected function queryProducts($sub_query)
	{
		$sql = 'select Product.id, Product.shortname, Product.title,
				ProductPrimaryImageView.image as primary_image
			from Product
			inner join CategoryProductBinding
				on CategoryProductBinding.product = Product.id
			inner join VisibleProductCache
				on VisibleProductCache.product = Product.id
			left outer join ProductPrimaryImageView
				on ProductPrimaryImageView.product = Product.id
			where CategoryProductBinding.category in (%s)
				and VisibleProductCache.region = %s
			order by displayorder, title';

		$sql = sprintf($sql,
			$sub_query,
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$wrapper_class = SwatDBClassMap::get('StoreProductWrapper');
		$products = SwatDB::query($this->app->db, $sql, $wrapper_class);

		if (count($products) == 0)
			return $products;

		$sql = 'select * from Image where id in (%s)';
		$wrapper_class = SwatDBClassMap::get('StoreProductImageWrapper');
		$products->loadAllSubDataObjects(
			'primary_image', $this->app->db, $sql, $wrapper_class);

		return $products;
	}

	// }}}
	// {{{ protected function displayCategory()

	protected function displayCategory($category_id)
	{
		$this->displaySubCategories($category_id);

		$products = $this->queryProducts(
			$this->app->db->quote($category_id, 'integer'));

		if (count($products) == 0)
			return;

		if (count($products) == 1) {
			$link = $this->source.'/'.$products->getFirst()->shortname;
			$this->app->relocate($link);
		}

		$this->displayProducts($products);
	}

	// }}}
	// {{{ protected function queryFeaturedProducts()

	protected function queryFeaturedProducts($category_id)
	{
		$sql = 'select Product.id, shortname, title, primary_category,
				ProductPrimaryImageView.image as primary_image,
				getCategoryPath(primary_category) as path
			from Product
				inner join CategoryFeaturedProductBinding
					on Product.id = CategoryFeaturedProductBinding.product
						and category = %s
				inner join VisibleProductCache
					on Product.id = VisibleProductCache.product
						and VisibleProductCache.region = %s
				left outer join ProductPrimaryCategoryView
					on ProductPrimaryCategoryView.product = Product.id
				left outer join ProductPrimaryImageView
					on ProductPrimaryImageView.product = Product.id
			order by CategoryFeaturedProductBinding.displayorder,
				Product.title';

		$sql = sprintf($sql,
			$this->app->db->quote($category_id, 'integer'),
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$wrapper_class = SwatDBClassMap::get('StoreProductWrapper');
		$products = SwatDB::query($this->app->db, $sql, $wrapper_class);

		return $products;
	}

	// }}}
	// {{{ protected function displayFeaturedProducts()

	protected function displayFeaturedProducts($category_id)
	{
		$products = $this->queryFeaturedProducts($category_id);

		if (count($products) == 0)
			return;

		$div = new SwatHtmlTag('div');
		$div->id = 'featured_products';
		$div->open();

		$header_tag = new SwatHtmlTag('h4');
		$header_tag->setContent(Store::_('Featuring:'));
		$header_tag->display();

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'store-product-list';
		$ul_tag->open();

		$li_tag = new SwatHtmlTag('li');
		$li_tag->class = 'store-product-text';

		foreach ($products as $product) {
			$li_tag->open();
			$path = 'store/'.$product->path;
			$product->displayAsText($path);
			$li_tag->close();
			echo ' ';
		}

		$ul_tag->close();
		echo '<div class="clear"></div>';
		$div->close();
	}

	// }}}
	// {{{ protected function displayRelatedArticles()

	protected function displayRelatedArticles(StoreCategory $category)
	{
		if (count($category->related_articles) > 0) {
			$div = new SwatHtmlTag('div');
			$div->id = 'related_articles';
			$div->open();
			$this->displayRelatedArticlesTitle();

			$first = true;
			$anchor_tag = new SwatHtmlTag('a');
			foreach ($category->related_articles as $article) {
				if ($first)
					$first = false;
				else
					echo ', ';

				$anchor_tag->href = $article->path;
				$anchor_tag->setContent($article->title);
				$anchor_tag->display();
			}

			$div->close();
		}
	}

	// }}}
	// {{{ protected function displayRelatedArticlesTitle()

	protected function displayRelatedArticlesTitle()
	{
		echo Store::_('Related Articles: ');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->path !== null) {
			$link = 'store';
			foreach ($this->path as $path_entry) {
				$link.= '/'.$path_entry->shortname;
				$this->layout->navbar->createEntry($path_entry->title, $link);
			}
		}
	}

	// }}}
}

?>
