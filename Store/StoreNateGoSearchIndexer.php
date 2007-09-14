<?php

require_once 'Site/SiteNateGoSearchIndexer.php';
require_once 'Store/Store.php';
require_once 'Store/pages/StoreSearchPage.php';

/**
 * Store search indexer application for NateGoSearch
 *
 * This indexer indexed products, categories and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreNateGoSearchIndexer extends SiteNateGoSearchIndexer
{
	// {{{ public function queue()
	
	/**
	 * Repopulates the entire search queue
	 */
	public function queue()
	{
		parent::queue();

		$this->queueProducts();
		$this->queueCategories();
	}

	// }}}
	// {{{ protected function index()

	/**
	 * Indexes documents
	 *
	 * Subclasses should override this method to add or remove additional
	 * indexed tables.
	 */
	protected function index()
	{
		parent::index();

		$this->indexProducts();
		$this->indexCategories();
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

		$type = NateGoSearch::getDocumentType($this->db, 'product');

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue 
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from Product',
			$this->db->quote($type, 'integer'));

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

		$type = NateGoSearch::getDocumentType($this->db, 'category');

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from Category',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Store::_('done')."\n", self::VERBOSITY_ALL);
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
		$indexer = new NateGoSearchIndexer('category', $this->db);

		$indexer->addTerm(new NateGoSearchTerm('title'));
		$indexer->setMaximumWordLength(32);
		$indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'category');

		$sql = sprintf('select Category.id, Category.title, Category.bodytext
			from Category
			inner join NateGoSearchQueue
				on Category.id = NateGoSearchQueue.document_id
				and NateGoSearchQueue.document_type = %s',
			$this->db->quote($type, 'integer'));

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
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function indexProducts()

	protected function indexProducts()
	{
		$product_indexer = new NateGoSearchIndexer('product', $this->db);

		$product_indexer->addTerm(new NateGoSearchTerm('title', 5));
		$product_indexer->addTerm(new NateGoSearchTerm('bodytext'));
		$product_indexer->setMaximumWordLength(32);
		$product_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		// the item indexer appends, it gets called after the product indexer
		$item_indexer = new NateGoSearchIndexer('product', $this->db, false, true);

		$item_indexer->addTerm(new NateGoSearchTerm('sku', 3));
		$item_indexer->addTerm(new NateGoSearchTerm('description'));
		$item_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'product');

		$sql = sprintf('select Product.id, Product.title,
				Product.bodytext, Item.sku, Item.description
			from Item
				inner join Product on Item.product = Product.id
				inner join NateGoSearchQueue
					on Product.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by Product.id',
			$this->db->quote($type, 'integer'));

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
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
}

?>
