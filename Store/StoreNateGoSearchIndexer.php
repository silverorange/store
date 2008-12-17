<?php

require_once 'Site/SiteNateGoSearchIndexer.php';
require_once 'Store/Store.php';
require_once 'Store/pages/StoreSearchPage.php';

if (class_exists('Blorg')) {
	require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
	require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
}

/**
 * Store search indexer application for NateGoSearch
 *
 * This indexer indexed products, categories and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
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

		if (class_exists('Blorg')) {
			$this->queuePosts();
			$this->queueComments();
		}
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);
		$config->addDefinitions(Store::getConfigDefinitions());
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

		if (class_exists('Blorg')) {
			$this->indexPosts();
			$this->indexComments();
		}
	}

	// }}}
	// {{{ protected function queueProducts()

	/**
	 * Repopulates the products queue
	 */
	protected function queueProducts()
	{
		$this->debug(Store::_('Repopulating product search queue ... '));

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

		$this->debug(Store::_('done')."\n");
	}

	// }}}
	// {{{ protected function queueCategories()

	/**
	 * Repopulates the categories queue
	 */
	protected function queueCategories()
	{
		$this->debug(Store::_('Repopulating category search queue ... '));

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

		$this->debug(Store::_('done')."\n");
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
		$spell_checker = new NateGoSearchPSpellSpellChecker('en_US', '', '',
			$this->getCustomWordList());

		$indexer = new NateGoSearchIndexer('category', $this->db);

		$indexer->setSpellChecker($spell_checker);
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

		$this->debug(Store::_('Indexing categories ... ').'   ');

		$categories = SwatDB::query($this->db, $sql);
		$total = count($categories);
		$count = 0;
		foreach ($categories as $category) {

			if ($count % 10 == 0) {
				$indexer->commit();
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total) * 100));
			}

			$document = new NateGoSearchDocument($category, 'id');
			$indexer->index($document);

			$count++;
		}

		$this->debug(str_repeat(chr(8), 3).Store::_('done')."\n");

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
		$spell_checker = new NateGoSearchPSpellSpellChecker('en_US', '', '',
			$this->getCustomWordList());

		$product_indexer = new NateGoSearchIndexer('product', $this->db);
		$product_indexer->setSpellChecker($spell_checker);
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
				right outer join Product on Item.product = Product.id
				inner join NateGoSearchQueue
					on Product.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by Product.id',
			$this->db->quote($type, 'integer'));

		$this->debug(Store::_('Indexing products ... ').'   ');

		$products = SwatDB::query($this->db, $sql);
		$total = count($products);
		$count = 0;
		$current_product_id = null;
		foreach ($products as $product) {

			if ($count % 10 == 0) {
				$product_indexer->commit();
				$item_indexer->commit();
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total) * 100));
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

		$this->debug(str_repeat(chr(8), 3).Store::_('done')."\n");

		$product_indexer->commit();
		$item_indexer->commit();
		unset($product_indexer);
		unset($item_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function queuePosts()

	/**
	 * Repopulates the posts queue
	 */
	protected function queuePosts()
	{
		$this->debug(Blorg::_('Repopulating post search queue ... '));

		$type = NateGoSearch::getDocumentType($this->db, 'post');

		// clear queue
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from BlorgPost',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->debug(Blorg::_('done')."\n");
	}

	// }}}
	// {{{ protected function indexPosts()

	protected function indexPosts()
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker('en_US', '', '',
			$this->getCustomWordList());

		$post_indexer = new NateGoSearchIndexer('post', $this->db);
		$post_indexer->setSpellChecker($spell_checker);
		$post_indexer->addTerm(new NateGoSearchTerm('title', 30));
		$post_indexer->addTerm(new NateGoSearchTerm('bodytext', 20));
		$post_indexer->addTerm(new NateGoSearchTerm('extended_bodytext', 18));
		$post_indexer->addTerm(new NateGoSearchTerm('comments', 1));
		$post_indexer->setMaximumWordLength(32);
		$post_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'post');

		$sql = sprintf('select BlorgPost.*
			from BlorgPost
				inner join NateGoSearchQueue
					on BlorgPost.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by BlorgPost.id',
			$this->db->quote($type, 'integer'));

		$this->debug(Blorg::_('Indexing posts... ').'   ');

		$posts = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgPostWrapper'));

		$total = count($posts);
		$count = 0;
		$current_post_id = null;
		foreach ($posts as $post) {
			$ds = new SwatDetailsStore($post);
			$ds->title = $post->getTitle();

			$ds->comments = '';
			foreach ($post->getVisibleComments() as $comment)
				$ds->comments.= $comment->fullname.' '.$comment->bodytext.' ';

			if ($count % 10 == 0) {
				$post_indexer->commit();
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total) * 100));
			}

			$document = new NateGoSearchDocument($ds, 'id');
			$post_indexer->index($document);
			$current_post_id = $post->id;
			$count++;
		}

		$this->debug(str_repeat(chr(8), 3).Blorg::_('done')."\n");

		$post_indexer->commit();
		unset($post_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function queueComments()

	/**
	 * Repopulates the comments queue
	 */
	protected function queueComments()
	{
		$this->debug(Blorg::_('Repopulating comment search queue ... '));

		$type = NateGoSearch::getDocumentType($this->db, 'comment');

		// clear queue
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from BlorgComment',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->debug(Blorg::_('done')."\n");
	}

	// }}}
	// {{{ protected function indexComments()

	protected function indexComments()
	{
		$type_shortname = 'comment';
		$spell_checker = new NateGoSearchPSpellSpellChecker('en_US', '', '',
			$this->getCustomWordList());

		$comment_indexer = new NateGoSearchIndexer($type_shortname, $this->db);
		$comment_indexer->setSpellChecker($spell_checker);
		$comment_indexer->addTerm(new NateGoSearchTerm('fullname', 30));
		$comment_indexer->addTerm(new NateGoSearchTerm('email', 20));
		$comment_indexer->addTerm(new NateGoSearchTerm('bodytext', 1));
		$comment_indexer->setMaximumWordLength(32);
		$comment_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, $type_shortname);

		$sql = sprintf('select BlorgComment.*
			from BlorgComment
				inner join NateGoSearchQueue
					on BlorgComment.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by BlorgComment.id',
			$this->db->quote($type, 'integer'));

		$this->debug(Blorg::_('Indexing comments... ').'   ');

		$comments = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgCommentWrapper'));

		$total = count($comments);
		$count = 0;
		foreach ($comments as $comment) {
			$ds = new SwatDetailsStore($comment);

			if ($count % 10 == 0) {
				$comment_indexer->commit();
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total) * 100));
			}

			$document = new NateGoSearchDocument($ds, 'id');
			$comment_indexer->index($document);
			$count++;
		}

		$this->debug(str_repeat(chr(8), 3).Blorg::_('done')."\n");

		$comment_indexer->commit();
		unset($comment_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
}

?>
