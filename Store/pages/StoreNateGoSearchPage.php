<?php

require_once 'Store/pages/StoreSearchPage.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';
require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreNateGoSearchPage extends StoreSearchPage
{
	// {{{ protected properties

	/**
	 * @var NateGoSearchResult
	 */
	protected $nate_go_search_result;

	/**
	 * Whether or not to allow results with no keywords
	 *
	 * Should be set to false if another criteria is used to search
	 *
	 * @var boolean
	 */
	protected $require_keywords = true;

	// }}}

	// process phase
	// {{{ protected function search()

	/**
	 * Performs a keyword search on content
	 *
	 * @param string $keywords the keywords with which to to search.
	 *
	 * @see StoreSearchPage::search()
	 */
	protected function search($keywords)
	{
		$this->nate_go_search_result = $this->searchNateGo($keywords);
		$this->recordSearch($this->nate_go_search_result->getQueryString());
	}

	// }}}
	// {{{ protected function searchNateGo()

	/**
	 * Performs a NateGo search using keywords
	 *
	 * For a generic store, NateGo search can search articles, products and
	 * categories.
	 *
	 * @param string $keywords the keywords to search with.
	 *
	 * @return NateGoSearchResult the result resource returned by the search
	 *                             query.
	 *
	 * @see StoreSearchPage::$search_type
	 */
	protected function searchNateGo($keywords)
	{
		$spell_checker = new NateGoSearchSpellChecker();
		$spell_checker->loadMisspellingsFromFile(
			$spell_checker->getDefaultMisspellingsFilename());

		$query = new NateGoSearchQuery($this->app->db);
		$query->addBlockedWords(NateGoSearchQuery::getDefaultBlockedWords());
		$query->setSpellChecker($spell_checker);

		if ($this->search_type === null ||
			$this->search_type == StoreSearchPage::TYPE_ARTICLES)
			$query->addDocumentType(
				$this->getDocumentType(StoreSearchPage::TYPE_ARTICLES));

		if ($this->search_type === null ||
			$this->search_type == StoreSearchPage::TYPE_PRODUCTS)
			$query->addDocumentType(
				$this->getDocumentType(StoreSearchPage::TYPE_PRODUCTS));

		if ($this->search_type === null ||
			$this->search_type == StoreSearchPage::TYPE_CATEGORIES)
			$query->addDocumentType(
				$this->getDocumentType(StoreSearchPage::TYPE_CATEGORIES));

		return $query->query($keywords);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if ($this->searchWasPerformed()) {
			if ($this->search_type === null ||
				$this->search_type == StoreSearchPage::TYPE_CATEGORIES)
				$this->searchCategories();

			if ($this->search_type === null ||
				$this->search_type == StoreSearchPage::TYPE_PRODUCTS)
				$this->searchProducts();

			if ($this->search_type === null ||
				$this->search_type == StoreSearchPage::TYPE_ARTICLES)
				$this->searchArticles();

			$has_categories = in_array(StoreSearchPage::TYPE_CATEGORIES,
				$this->search_has_results);

			$has_products = in_array(StoreSearchPage::TYPE_PRODUCTS,
				$this->search_has_results);

			// set the article frame to use the whole width if it is the only
			// results frame displayed
			if ($this->search_type === StoreSearchPage::TYPE_ARTICLES ||
				(!$has_categories && !$has_products)) {
				$this->ui->getWidget('article_results_frame')->classes[] =
					'full-width';
			} else {
				$this->ui->getWidget('article_pagination')->display_parts = 
					SwatPagination::NEXT | SwatPagination::PREV;
			}

			// display no results message
			if (count($this->search_has_results) == 0) {
				$no_results = $this->getNoResultsMessage(
					$this->ui->getWidget('search_keywords')->value);

				$messages = $this->ui->getWidget('search_message');
				$messages->add($no_results);

			// display no product results message
			} elseif ($this->search_type === null && !$has_products) {
				$no_product_results = $this->getNoResultsMessage(
					$this->ui->getWidget('search_keywords')->value,
					StoreSearchPage::TYPE_PRODUCTS);

				$messages = $this->ui->getWidget('search_message');
				$messages->add($no_product_results);
			}
		}

		if ($this->nate_go_search_result !== null) {
			$this->buildMisspellings();
		}

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function searchWasPerformed()

	/**
	 * Whether or not a search was performed
	 *
	 * @return boolean true if a search was performed and false if a search was
	 *                  not performed.
	 */
	protected function searchWasPerformed()
	{
		return ($this->nate_go_search_result !== null);
	}

	// }}}
	// {{{ protected function buildMisspellings()

	// display suggested spellings
	protected function buildMisspellings()
	{
		$misspellings = $this->nate_go_search_result->getMisspellings();
		if (count($misspellings) > 0 ) {
			$corrected_phrase = $corrected_string =
				' '.$this->ui->getWidget('search_keywords')->value.' ';

			$corrected_string = SwatString::minimizeEntities($corrected_string);

			foreach ($misspellings as $misspelling => $correction) {
				// for URL
				$corrected_phrase = str_replace(' '.$misspelling.' ',
					' '.$correction.' ', $corrected_phrase);

				// for display
				$corrected_string = str_replace(
					' '.SwatString::minimizeEntities($misspelling).' ',
					' <strong>'.SwatString::minimizeEntities($correction).
					'</strong> ',
					$corrected_string);
			}

			$corrected_phrase = trim($corrected_phrase);
			$corrected_string = trim($corrected_string);

			$misspellings_link = new SwatHtmlTag('a');
			$misspellings_link->href = sprintf('search?keywords=%s',
				urlencode($corrected_phrase));

			$misspellings_link->setContent($corrected_string, 'text/xml');

			$misspellings_message = new SwatMessage(sprintf(
				Store::_('Did you mean “%s”?'),
				$misspellings_link->toString()));

			$misspellings_message->content_type = 'text/xml';

			$messages = $this->ui->getWidget('search_message');
			$messages->add($misspellings_message);
		}
	}

	// }}}
	// {{{ protected function searchArticles()

	protected function searchArticles()
	{
		$result = $this->nate_go_search_result;

		/*
		 * This query selects only visible and searchable articles and filters
		 * by search results, ordering by search relevance.
		 */
		$join_clause = sprintf('inner join %1$s on
				%1$s.document_id = Article.id and
				%1$s.unique_id = %2$s and %1$s.document_type = %3$s',
			$result->getResultTable(),
			$this->app->db->quote($result->getUniqueId(), 'text'),
			$this->app->db->quote(
				$this->getDocumentType(StoreSearchPage::TYPE_ARTICLES),
				'integer'));

		$where_clause = sprintf('where Article.searchable = %s and
			Article.id in
				(select id from VisibleArticleView where region = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$total_articles = SwatDB::queryOne($this->app->db,
			'select count(Article.id) from Article '.$join_clause.' '.
			$where_clause);

		$pagination = $this->ui->getWidget('article_pagination');
		$pagination->total_records = $total_articles;
		$pagination->link = sprintf('search?keywords=%s&type=articles&page=%%s',
			$this->getKeywordsField());
			
		$pagination->setCurrentPage(SiteApplication::initVar('page', 0,
			SiteApplication::VAR_GET));

		$sql = sprintf('select Article.*
			from Article
				%2$s
				%3$s
			order by %1$s.displayorder1, %1$s.displayorder2, Article.title
			limit %4$s offset %5$s',
			$result->getResultTable(),
			$join_clause,
			$where_clause,
			$this->app->db->quote($pagination->page_size, 'integer'),
			$this->app->db->quote($pagination->current_record, 'integer'));

		$class_map = StoreClassMap::instance();
		$wrapper_class = $class_map->resolveClass('StoreArticleWrapper');
		$articles = SwatDB::query($this->app->db, $sql, $wrapper_class);

		if (count($articles) > 0) {
			$this->search_has_results[] = StoreSearchPage::TYPE_ARTICLES;
			$this->ui->getWidget('article_results_frame')->visible = true;
			$article_results = $this->ui->getWidget('article_results');
			$article_results->content_type = 'text/xml';

			ob_start();
			$this->displayArticles($articles);
			$article_results->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function searchCategories()

	protected function searchCategories()
	{
		$result = $this->nate_go_search_result;

		$sql = 'select Category.id, Category.title, Category.shortname,
				Category.image, c.product_count, c.region as region_id
			from Category
				inner join CategoryVisibleProductCountByRegionCache as c
					on c.category = Category.id and c.region = %s
			where id in
				(select document_id from %s
				where %s.unique_id = %s and %s.document_type = %s)';

		$sql = sprintf($sql,
			$this->app->getRegion()->id,
			$result->getResultTable(),
			$result->getResultTable(),
			$this->app->db->quote($result->getUniqueId(), 'text'),
			$result->getResultTable(),
			$this->app->db->quote(
				$this->getDocumentType(StoreSearchPage::TYPE_CATEGORIES),
				'integer'));

		$class_map = StoreClassMap::instance();
		$wrapper_class = $class_map->resolveClass('StoreCategoryWrapper');
		$categories = SwatDB::query($this->app->db, $sql, $wrapper_class);
		$categories->setRegion($this->app->getRegion());

		if (count($categories) > 0) {
			$sql = 'select * from Image where id in (%s)';
			$image_wrapper_class = $class_map->resolveClass(
				'StoreCategoryImageWrapper');

			$categories->loadAllSubDataObjects(
				'image', $this->app->db, $sql, $image_wrapper_class);

			$this->search_has_results[] = StoreSearchPage::TYPE_CATEGORIES;
			$this->ui->getWidget('category_results_frame')->visible = true;
			$category_results = $this->ui->getWidget('category_results');
			$category_results->content_type = 'text/xml';

			ob_start();
			$this->displayCategories($categories);
			$category_results->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function searchProducts()

	/**
	 * Searches for product results and displays results
	 */
	protected function searchProducts()
	{
		$this->buildProductPagination();

		if ($this->nate_go_search_result !== null || !$this->require_keywords) {
			$products = $this->queryProducts();

			if (count($products) > 0) {
				$this->loadProductSubDataObjects($products);

				$this->search_has_results[] = StoreSearchPage::TYPE_PRODUCTS;
				$this->ui->getWidget('product_results_frame')->visible = true;
				$product_results = $this->ui->getWidget('product_results');
				$product_results->content_type = 'text/xml';

				ob_start();
				$this->displayProducts($products);
				$product_results->content = ob_get_clean();
			}
		}
	}

	// }}}
	// {{{ protected function buildProductPagination()

	protected function buildProductPagination()
	{
		$total_sql = sprintf('select count(Product.id) from Product %s %s',
			$this->getProductJoinClause(),
			$this->getProductWhereClause());

		$total_products = SwatDB::queryOne($this->app->db, $total_sql);

		$pagination = $this->ui->getWidget('product_pagination');
		$pagination->total_records = $total_products;
		$pagination->link = sprintf('search?keywords=%s&type=products&page=%%s',
			$this->getKeywordsField());

		$pagination->setCurrentPage(SiteApplication::initVar('page', 0,
			SiteApplication::VAR_GET));
	}

	// }}}
	// {{{ protected function queryProducts()

	/**
	 * Allows subclasses to add additional fields to the product query
	 *
	 * @return StoreProductWrapper An ordered collection of StoreProduct
	 * 	dataobjects to display as search results.
	 */
	protected function queryProducts()
	{
		/*
		 * We cannot use normal loader methods here because we need ordering
		 * by search relevance.
		 *
		 * This query selects only visible products and selects the primary
		 * category field and filters by search results, ordering by search
		 * relevance.
		 *
		 * The 'Product.id as tag' is a hack to efficiently load tags.
		 * See detailed explanation below.
		 */

		$pagination = $this->ui->getWidget('product_pagination');

		$sql = sprintf('select %6$s
			from Product
			%1$s -- join
			%2$s -- where
			%3$s -- order by
			limit %4$s offset %5$s',
			$this->getProductJoinClause(),
			$this->getProductWhereClause(),
			$this->getProductOrderByClause(),
			$this->app->db->quote($pagination->page_size, 'integer'),
			$this->app->db->quote($pagination->current_record, 'integer'),
			$this->getProductSelectClause());

		$class_map = StoreClassMap::instance();
		$wrapper_class = $class_map->resolveClass('StoreProductWrapper');
		return SwatDB::query($this->app->db, $sql, $wrapper_class);
	}

	// }}}
	// {{{ protected function loadProductSubDataObjects()

	/**
	 * Load sub dataobjects for the StoreProductWrapper results
	 *
	 * @param StoreProductWrapper $products A collection of StoreProduct
	 * 	dataobjects.
	 */
	protected function loadProductSubDataObjects(StoreProductWrapper $products)
	{
		/*
		 * Effciently load tags. The 'product as id' is a slight hack since
		 * there is a product-tag binding table, but we only support one
		 * tag per product. This is needed so that loadAllSubDataObjects()
		 * can properly attach the tag objects to the products.
		 */
		/*$sql = 'select product as id, title, description from Tag 
			inner join ProductTagBinding on ProductTagBinding.tag = Tag.id 
				and ProductTagBinding.product in (%s)';

		$products->loadAllSubDataObjects(
			'tag', $this->app->db, $sql, 'TagWrapper');
		*/
		$class_map = StoreClassMap::instance();

		$sql = 'select * from Image where id in (%s)';
		$image_wrapper_class = $class_map->resolveClass(
			'StoreProductImageWrapper');

		$products->loadAllSubDataObjects(
			'primary_image', $this->app->db, $sql, $image_wrapper_class);
	}

	// }}}
	// {{{ protected function getProductSelectClause()

	/**
	 * Allows subclasses to add additional columns to the select list.
	 *
	 * @return string a select clause for the product query.
	 */
	protected function getProductSelectClause()
	{
		return 'Product.title, Product.shortname, Product.bodytext,
			Product.id as tag, ProductPrimaryCategoryView.primary_category,
			ProductPrimaryImageView.image as primary_image,
			getCategoryPath(ProductPrimaryCategoryView.primary_category) as path';
	}

	// }}}
	// {{{ protected function getProductWhereClause()

	/**
	 * Allows subclasses to do additional filtering on Products above and
	 * beyond the fulltext and visibility filtering
	 *
	 * Subclasses should include the 'where' in the returned where clause.
	 *
	 * @return string a where clause that affects the product query.
	 */
	protected function getProductWhereClause()
	{
		return '';
	}

	// }}}
	// {{{ protected function getProductOrderByClause()

	/**
	 * Allows subclasses to do optional ordering based on search
	 * parameters.
	 *
	 * Subclasses should include the 'order by' in the returned order clause.
	 *
	 * @return string an order clause that affects the product query.
	 */
	protected function getProductOrderByClause()
	{
		$result = $this->nate_go_search_result;

		if ($result === null)
			return 'order by Product.title';
		else
			return sprintf('order by %1$s.displayorder1 asc,
				%1$s.displayorder2 asc, Product.title',
				$result->getResultTable());
	}

	// }}}
	// {{{ protected function getProductJoinClause()

	/**
	 * Allows subclasses to do additional joins on Products above and
	 * beyond the joining of visiblity and product information tables
	 *
	 * @return string a join clause that affects the product query.
	 */
	protected function getProductJoinClause()
	{
		$result = $this->nate_go_search_result;

		$join_clause = sprintf('left outer join ProductPrimaryCategoryView on
					ProductPrimaryCategoryView.product = Product.id
				left outer join ProductPrimaryImageView
					on ProductPrimaryImageView.product = Product.id
				inner join VisibleProductCache on
					VisibleProductCache.product = Product.id and
					VisibleProductCache.region = %s',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		if ($result !== null)
			$join_clause.= $this->getProductFulltextJoinClause();

		return $join_clause;
	}

	// }}}
	// {{{ protected function getProductFulltextJoinClause()

	protected function getProductFulltextJoinClause()
	{
		$result = $this->nate_go_search_result;

		return sprintf('inner join %1$s on
			%1$s.document_id = Product.id and
			%1$s.unique_id = %2$s and %1$s.document_type = %3$s',
			$result->getResultTable(),
			$this->app->db->quote($result->getUniqueId(), 'text'),
			$this->app->db->quote(
				$this->getDocumentType(StoreSearchPage::TYPE_PRODUCTS),
				'integer'));
	}
	// }}}
	// {{{ abstract protected function getDocumentType()

	/**
	 * Gets the NateGo document type based on a content search type
	 *
	 * @param string $search_type the type of content to search. One of the
	 *                             StoreSearchPage::TYPE_* constants.
	 *
	 * @return integer the NateGo document type that corresponds to the content
	 *                  search type or null if no document type exists.
	 */
	abstract protected function getDocumentType($search_type);

	// }}}
}

?>
