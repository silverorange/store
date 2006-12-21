<?php

require_once 'Site/SiteSearchIndexer.php';
require_once 'Store/Store.php';
require_once 'Store/pages/StoreSearchPage.php';
require_once 'NateGoSearch/NateGoSearchIndexer.php';

/**
 * Store search indexer application for NateGoSearch
 *
 * This indexer indexed products, categories and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreNateGoSearchIndexer extends SiteSearchIndexer
{
	// {{{ class constants

	/**
	 * Verbosity level for showing nothing.
	 */
	const VERBOSITY_NONE = 0;

	/**
	 * Verbosity level for showing all indexing actions
	 */
	const VERBOSITY_ALL = 1;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $title, $documentation)
	{
		parent::__construct($id, $title, $documentation);

		$verbosity = new SiteCommandLineArgument(array('-v', '--verbose'),
			'setVerbosity', 'Sets the level of verbosity of the indexer. '.
			'Pass 0 to turn off all output.');

		$verbosity->addParameter('integer',
			'--verbose expects a level between 0 and 1.',
			self::VERBOSITY_ALL);

		$all = new SiteCommandLineArgument(array('-A', '--all'),
			'queue', 'Indexes all content rather than just queued '.
			'content.');

		$this->addCommandLineArgument($verbosity);
		$this->addCommandLineArgument($all);
	}

	// }}}
	// {{{ public function queue()
	
	/**
	 * Repopulates the entire search queue
	 */
	public function queue()
	{
		$this->queueArticles();
		$this->queueProducts();
		$this->queueCategories();
	}

	// }}}
	// {{{ public function run()
	
	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$this->indexArticles();
		$this->indexProducts();
		$this->indexCategories();
	}

	// }}}
	// {{{ protected function queueArticles()

	/**
	 * Repopulates the articles queue
	 */
	protected function queueArticles()
	{
		$this->output(Store::_('Repopulating article search queue ... '),
			self::VERBOSITY_ALL);

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_ARTICLES), 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue 
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from Article',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_ARTICLES), 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Store::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function queueProducts()

	/**
	 * Repopulates the products queue
	 */
	protected function queueProducts()
	{
		$this->output(Store::_('Repopulating product search queue ... '),
			self::VERBOSITY_ALL);

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_PRODUCTS), 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue 
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from Product',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_PRODUCTS), 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Store::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function queueCategories()

	/**
	 * Repopulates the categories queue
	 */
	protected function queueCategories()
	{
		$this->output(Store::_('Repopulating category search queue ... '),
			self::VERBOSITY_ALL);

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_CATEGORIES), 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from Category',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_CATEGORIES), 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Store::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function indexArticles()

	/**
	 * Indexes articles
	 *
	 * Articles are visible if they are enabled. Articles may not be shown in
	 * the menu but are still visible. Articles also have an explicit
	 * searchable field.
	 */
	protected function indexArticles()
	{
		$indexer = new NateGoSearchIndexer(
			$this->getDocumentType(StoreSearchPage::TYPE_ARTICLES),
				$this->db);

		$indexer->addTerm(new NateGoSearchTerm('title', 5));
		$indexer->addTerm(new NateGoSearchTerm('bodytext'));
		$indexer->setMaximumWordLength(32);
		$indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$sql = sprintf('select id, shortname, title, bodytext from Article
			inner join NateGoSearchQueue
				on Article.id = NateGoSearchQueue.document_id
				and NateGoSearchQueue.document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_ARTICLES), 'integer'));

		$this->output(Store::_('Indexing articles ... ').'   ',
			self::VERBOSITY_ALL);

		$articles = SwatDB::query($this->db, $sql);
		$total = count($articles);
		$count = 0;
		foreach ($articles as $article) {

			if ($count % 10 == 0) {
				$indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($article, 'id');
			$indexer->index($document);

			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Store::_('done')."\n",
			self::VERBOSITY_ALL);

		$indexer->commit();
		unset($indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_ARTICLES), 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function indexCategories()

	/**
	 * Indexes categories
	 *
	 * Categories are visible and thus searchable if they contain products
	 * for a region. Index all categories by default. Visibility is determined
	 * at search time.
	 */
	protected function indexCategories()
	{
		$indexer = new NateGoSearchIndexer(
			$this->getDocumentType(StoreSearchPage::TYPE_CATEGORIES),
				$this->db);

		$indexer->addTerm(new NateGoSearchTerm('title'));
		$indexer->setMaximumWordLength(32);
		$indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$sql = sprintf('select Category.id, Category.title, Category.bodytext
			from Category
			inner join NateGoSearchQueue
				on Category.id = NateGoSearchQueue.document_id
				and NateGoSearchQueue.document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_CATEGORIES), 'integer'));

		$this->output(Store::_('Indexing categories ... ').'   ',
			self::VERBOSITY_ALL);

		$categories = SwatDB::query($this->db, $sql);
		$total = count($categories);
		$count = 0;
		foreach ($categories as $category) {

			if ($count % 10 == 0) {
				$indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($category, 'id');
			$indexer->index($document);

			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Store::_('done')."\n",
			self::VERBOSITY_ALL);

		$indexer->commit();
		unset($indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_CATEGORIES), 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function indexProducts()

	protected function indexProducts()
	{
		$product_indexer = new NateGoSearchIndexer(
			$this->getDocumentType(StoreSearchPage::TYPE_PRODUCTS),
				$this->db);

		$product_indexer->addTerm(new NateGoSearchTerm('title', 5));
		$product_indexer->addTerm(new NateGoSearchTerm('bodytext'));
		$product_indexer->setMaximumWordLength(32);
		$product_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		// the item indexer appends, it gets called after the product indexer
		$item_indexer = new NateGoSearchIndexer(
			$this->getDocumentType(StoreSearchPage::TYPE_PRODUCTS),
				$this->db, false, true);

		$item_indexer->addTerm(new NateGoSearchTerm('sku', 3));
		$item_indexer->addTerm(new NateGoSearchTerm('description'));
		$item_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$sql = sprintf('select Product.id, Product.title,
				Product.bodytext, Item.sku, Item.description
			from Item
				inner join Product on Item.product = Product.id
				inner join NateGoSearchQueue
					on Product.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by Product.id',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_PRODUCTS), 'integer'));

		$this->output(Store::_('Indexing products ... ').'   ',
			self::VERBOSITY_ALL);

		$products = SwatDB::query($this->db, $sql);
		$total = count($products);
		$count = 0;
		$current_product_id = null;
		foreach ($products as $product) {

			if ($count % 10 == 0) {
				$product_indexer->commit();
				$item_indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($product, 'id');

			// only index product fields once
			if ($product->id !== $current_product_id) {
				$product_indexer->index($document);
				$current_product_id = $product->id;
			}

			$item_indexer->index($document);

			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Store::_('done')."\n",
			self::VERBOSITY_ALL);

		$product_indexer->commit();
		$item_indexer->commit();
		unset($product_indexer);
		unset($item_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($this->getDocumentType(
				StoreSearchPage::TYPE_PRODUCTS), 'integer'));

		SwatDB::exec($this->db, $sql);
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
