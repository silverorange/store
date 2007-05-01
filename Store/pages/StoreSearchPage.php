<?php

require_once 'Store/dataobjects/StoreArticleWrapper.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/dataobjects/StoreCategoryImageWrapper.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/dataobjects/StoreProductImageWrapper.php';
require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/StoreUI.php';

require_once 'Swat/SwatNavBar.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Page for performing complex searches and displaying search results
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreSearchPage extends StoreArticlePage
{
	// {{{ class constants

	/**
	 * Type for article search
	 */
	const TYPE_ARTICLES = 'articles';

	/**
	 * Type for product search
	 */
	const TYPE_PRODUCTS = 'products';

	/**
	 * Type for category search
	 */
	const TYPE_CATEGORIES = 'categories';

	// }}}
	// {{{ protected properties

	/**
	 * The user-interface of the search form
	 *
	 * @var StoreUI
	 */
	protected $ui;

	/**
	 * The SwatML file to load the search user-interface from
	 *
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/search.xml';

	/**
	 * The type of content to search
	 *
	 * This is one of the StoreSearchPage::TYPE_* constants. If this property
	 * is null, all content types are searched.
	 *
	 * @var string
	 */
	protected $search_type;

	/**
	 * An array of strings containing StoreSearchPage::TYPE_* constants that
	 * have search results
	 *
	 * @var array
	 */
	protected $search_has_results = array();

	// }}}

	// init phase
	// {{{ public function init

	public function init()
	{
		parent::init();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);

		$form = $this->ui->getWidget('search_form');
		$form->setMethod(SwatForm::METHOD_GET);
		$form->action = $this->source;

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$this->search_type = SiteApplication::initVar('type', null,
			SiteApplication::VAR_GET);

		$search_keywords = $this->ui->getWidget('search_keywords');
		$search_keywords->value = $this->getKeywords();

		$form = $this->ui->getWidget('search_form');
		$form->process();

		if ($this->isSubmitted()) {
			$this->searchItem($search_keywords->value);
			$this->search($search_keywords->value);
		}
	}

	// }}}
	// {{{ abstract protected function searchItem()

	/**
	 * Searches for a specific item and relocates directly to the product
	 * page containing the item if exactly one item is found
	 *
	 * @param string $keywords the keywords with which to to search.
	 */
	abstract protected function searchItem($keywords);

	// }}}
	// {{{ abstract protected function search()

	/**
	 * Performs a keyword search on content
	 *
	 * @param string $keywords the keywords with which to to search.
	 */
	abstract protected function search($keywords);

	// }}}
	// {{{ protected function getKeywords()

	/**
	 * Gets the search keywords with which to search
	 *
	 * Keywords are taken from the main search form, the quick search form and
	 * the URL.
	 *
	 * @return string the keywords with which to search.
	 */
	protected function getKeywords()
	{
		$search_keywords = $this->ui->getWidget('search_keywords')->value;

		$keywords = SiteApplication::initVar('keywords', '',
			SiteApplication::VAR_GET);

		// site-wide search box
		$quick_search_form =
			$this->layout->quick_search_ui->getWidget('quick_search_form');

		$quick_search_form->process();

		if ($quick_search_form->isProcessed()) {
			$search_keywords =
				$this->layout->quick_search_ui->getWidget('keywords')->value;
		} elseif (strlen($keywords) > 0) {
			$search_keywords = $keywords;
		}

		return $search_keywords;
	}

	// }}}
	// {{{ protected function isSubmitted()

	/**
	 * Gets whether or not this search page has been submitted
	 *
	 * @return boolean whether or not this search page has been submitted.
	 */
	protected function isSubmitted()
	{
		$keywords = SiteApplication::initVar('keywords', '',
			SiteApplication::VAR_GET);

		// site-wide search box
		$quick_search_form =
			$this->layout->quick_search_ui->getWidget('quick_search_form');

		// main search form
		$form = $this->ui->getWidget('search_form');

		return
			$form->isProcessed() ||
			$quick_search_form->isProcessed() ||
			(strlen(trim($keywords)) > 0);
	}

	// }}}
	// {{{ protected function recordSearch()

	/**
	 * Records a keyword search in the database for future statictical analysis
	 *
	 * @param string $keywords the keywords to record.
	 */
	protected function recordSearch($keywords)
	{
		$sql = sprintf('insert into Keyword (keywords) values (%s)',
			$this->app->db->quote($keywords, 'text'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	
	// build phase
	// {{{ protected function searchWasPerformed()

	/**
	 * Whether or not a search was performed
	 *
	 * @return boolean true if a search was performed and false if a search was
	 *                  not performed.
	 */
	abstract protected function searchWasPerformed();

	// }}}
	// {{{ protected function getKeywordsField()

	/**
	 * Gets the search keywords used for this search as a url encoded, sprintf
	 * escaped string
	 *
	 * This method is used by the pagination widgets.
	 *
	 * @return string the search keywords used for this search as a url encoded
	 *                 string.
	 */
	protected function getKeywordsField()
	{
		$keywords = $this->ui->getWidget('search_keywords')->value;
		$keywords = urlencode($keywords);
		$keywords = str_replace('%', '%%', $keywords);
		return $keywords;
	}

	// }}}
	// {{{ protected function displayArticles()

	/**
	 * Displays search results for a collection of articles 
	 *
	 * @param StoreArticleWrapper $articles the articles to display search
	 *                                       results for.
	 */
	protected function displayArticles(StoreArticleWrapper $articles)
	{
		echo '<ul class="search-results">';
		$paragraph_tag = new SwatHtmlTag('p');

		foreach ($articles as $article) {
			$navbar = new SwatNavBar();
			$navbar->addEntries($article->getNavBarEntries());

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $navbar->getLastEntry()->link;
			$anchor_tag->class = 'search-result-title';

			echo '<li>';
			$anchor_tag->setContent($article->title);
			$anchor_tag->display();
			$navbar->display();
			$paragraph_tag->open();
			echo SwatString::condense($article->bodytext, 150).'&nbsp;';
			$anchor_tag->setContent(Store::_('more').'&nbsp;»');
			$anchor_tag->display();
			$paragraph_tag->close();
			echo '</li>';
		}

		echo '</ul>';
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
	// {{{ protected function getNoResultsMessage()

	/**
	 * Gets the no-results message for this search page
	 *
	 * @param string $keywords the keywords for which there are no results.
	 * @param string $type an optional type of SearchPage::TYPE_* indicating
	 *                      the content search type for which there are no
	 *                      results.
	 *
	 * @return SwatMessage the no-results message.
	 */
	protected function getNoResultsMessage($keywords, $type = null)
	{
		switch ($type) {
		case StoreSearchPage::TYPE_ARTICLES:
			$title = Store::_('No article results found for “%s”.');
			break;
		case StoreSearchPage::TYPE_CATEGORIES:
			$title = Store::_('No category results found for “%s”.');
			break;
		case StoreSearchPage::TYPE_PRODUCTS:
			$title = Store::_('No product results found for “%s”.');
			break;
		default:
			$title = Store::_('No results found for “%s”.');
			break;
		}

		$title = sprintf($title, SwatString::minimizeEntities($keywords));
		$tips = sprintf('<ul><li>%s</li></ul>',
			Store::_('Try using less specific keywords'));

		$message = new SwatMessage($title);
		$message->secondary_content = $tips;
		$message->content_type = 'text/xml';

		return $message;
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
			'packages/store/styles/store-search-page.css', Store::PACKAGE_ID));
	}

	// }}}
}

?>
