<?php

require_once 'Site/pages/SiteSearchResultsPage.php';
require_once 'Store/StoreArticleSearchEngine.php';
require_once 'Store/StoreProductSearchEngine.php';
require_once 'Store/StoreCategorySearchEngine.php';
require_once 'Store/dataobjects/StoreCategoryImageWrapper.php';
require_once 'Store/dataobjects/StoreProductImageWrapper.php';
require_once 'Store/StoreUI.php';

/**
 * Page for displaying search results
 *
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreSearchResultsPage extends SiteSearchResultsPage
{
	// init phase
	// {{{ public function init

	public function init()
	{
		$this->ui_xml = 'Store/pages/search-results.xml';
		$this->addSearchDataField('type');
		$this->addSearchDataField('category');

		parent::init();
	}

	// }}}

	// build phase
	// {{{ protected function buildResults()

	protected function buildResults()
	{
		$fulltext_result = $this->searchFulltext();

		if ($this->hasSearchDataValue('type')) {
			$type = $this->getSearchDataValue('type');

			if ($type === 'article')
				$this->buildArticles($fulltext_result);
			elseif ($type === 'product')
				$this->buildProducts($fulltext_result);
	
		} elseif (count($this->getSearchDataValues()) === 1 &&
			$this->hasSearchDataValue('keywords')) {

			// keywords only
			$this->buildArticles($fulltext_result);
			$this->buildCategories($fulltext_result);
			$this->buildProducts($fulltext_result);
		} else {
			$this->buildProducts($fulltext_result);
		}

		if ($fulltext_result !== null)
			$this->buildMisspellings($fulltext_result);

		if (count($this->has_results) === 1 &&
			in_array('article', $this->has_results)) {

			// set the article frame to use the whole width
			$frame = $this->ui->getWidget('article_results_frame');
			$frame->classes[] = 'full-width';

			$pager = $this->ui->getWidget('article_pager');
			$pager->display_parts = 
				SwatPagination::NEXT | SwatPagination::PREV |
				SwatPagination::PAGES | SwatPagination::POSITION;
		}
	}

	// }}}
	// {{{ protected function buildMessages()

	protected function buildMessages()
	{
		parent::buildMessages();

		$messages = $this->ui->getWidget('results_message');

		// display no product results message
		if ($messages->getMessageCount() == 0 &&
			!in_array('product', $this->has_results)) {

			$message = $this->getNoResultsMessage();
			$message->primary_content = Store::_('No product results found.');
			$messages = $this->ui->getWidget('results_message');
			$messages->add($message);
		}
	}

	// }}}
	// {{{ protected function getQuerySummary()

	protected function getQuerySummary()
	{
		$summary = parent::getQuerySummary();

		if ($this->hasSearchDataValue('category')) {
			$category = $this->getCategory();
			$summary[] = sprintf('Category: <b>%s</b>',
				SwatString::minimizeEntities($category->title));
		}

		return $summary;
	}

	// }}}
	// {{{ protected function getFulltextTypes()

	protected function getFulltextTypes()
	{
		$types = parent::getFulltextTypes();
		$types[] = 'product';
		$types[] = 'category';

		return $types;
	}

	// }}}

	// build phase - articles
	// {{{ protected function instantiateArticleSearchEngine()

	protected function instantiateArticleSearchEngine()
	{
		$engine = new StoreArticleSearchEngine($this->app);

		return $engine;
	}

	// }}}

	// build phase - categories
	// {{{ protected function buildCategories()

	protected function buildCategories($fulltext_result)
	{
		$engine = $this->instantiateCategorySearchEngine();
		$engine->setFulltextResult($fulltext_result);
		$categories = $engine->search();
		$categories->setRegion($this->app->getRegion());

		$this->result_count['category'] = count($categories);

		if (count($categories) > 0) {
			$this->has_results[] = 'category';

			$frame = $this->ui->getWidget('category_results_frame');
			$results = $this->ui->getWidget('category_results');
			$frame->visible = true;

			ob_start();
			$this->displayCategories($categories);
			$results->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function instantiateCategorySearchEngine()

	protected function instantiateCategorySearchEngine()
	{
		$engine = new StoreCategorySearchEngine($this->app);

		return $engine;
	}

	// }}}
	// {{{ protected function displayCategories()

	/**
	 * Displays search results for a collection of categories
	 *
	 * @param StoreCategoryWrapper $categories the categories to display
	 *                                          search results for.
	 */
	protected function displayCategories(StoreCategoryWrapper $categories)
	{
		echo '<ul class="search-results">';

		foreach ($categories as $category) {
			$navbar = new SwatNavBar();
			$navbar->addEntries($category->getNavBarEntries());
			$path = $navbar->getLastEntry()->link;

			echo '<li class="category-tile">';
			$category->displayAsTile($path);
			$navbar->display();
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function getCategory()

	protected function getCategory()
	{
		$category = null;

		if ($this->hasSearchDataValue('category')) {
			$sql = 'select id, shortname, title from Category
				where parent is null and shortname = %s and id in 
					(select category from VisibleCategoryView
					where region = %s or region is null)';

			$sql = sprintf($sql,
				$this->app->db->quote($this->getSearchDataValue('category')),
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

			$category = SwatDB::query($this->app->db, $sql,
				'StoreCategoryWrapper')->getFirst();
		}

		return $category;
	}

	// }}}

	// build phase - products
	// {{{ protected function buildProducts()

	protected function buildProducts($fulltext_result)
	{
		$pager = $this->ui->getWidget('product_pager');
		$engine = $this->instantiateProductSearchEngine();
		$engine->setFulltextResult($fulltext_result);
		$products = $engine->search($pager->page_size, $pager->current_record);

		$pager->total_records = $engine->getResultCount();
		$pager->link = $this->source;

		$this->result_count['product'] = count($products);

		if (count($products) > 0) {
			$this->has_results[] = 'product';

			$frame = $this->ui->getWidget('product_results_frame');
			$results = $this->ui->getWidget('product_results');
			$frame->visible = true;

			ob_start();
			$this->displayProducts($products);
			$results->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function instantiateProductSearchEngine()

	protected function instantiateProductSearchEngine()
	{
		$engine = new StoreProductSearchEngine($this->app);

		$engine->category = $this->getCategory();

		return $engine;
	}

	// }}}
	// {{{ protected function displayProducts()

	/**
	 * Displays search results for a collection of products
	 *
	 * @param StoreProductWrapper $products the products to display search
	 *                                       results for.
	 */
	protected function displayProducts(StoreProductWrapper $products)
	{
		echo '<ul>';
		$li_tag = new SwatHtmlTag('li');

		foreach ($products as $product) {
			echo '<li class="product-tile">';
			$link_href = 'store/'.$product->path;
			$product->displayAsTile($link_href);
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-search-results-page.css', Store::PACKAGE_ID));
	}

	// }}}
}

?>
