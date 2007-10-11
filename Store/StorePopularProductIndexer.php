<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Store/Store.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Class for populating the ProductPopularProductBinding table which caches
 * values used to display "Customers who bought this also bought X" data.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePopularProductIndexer extends SiteCommandLineApplication
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
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

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
			'selectAll', 'Re-indexes all orders rather than just '.
			'updating indexes for new orders ');

		$this->addCommandLineArgument($verbosity);
		$this->addCommandLineArgument($all);
	}

	// }}}
	// {{{ public function run()
	
	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();
		$this->index();
	}

	// }}}
	// {{{ public function reindex()
	
	public function reindex()
	{
		$this->output(Store::_('Reindexing all orders ... '),
			self::VERBOSITY_ALL);

		SwatDB::exec($this->db, 'truncate ProductPopularProductBinding');
		SwatDB::exec($this->db, sprintf('update Orders set
			popular_products_processed = %s',
			$this->db->quote(false, 'boolean')));
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
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
		$products = $this->getRelatedProducts();

		$total = count($products);
		$count = 0;
		$inserted_pairs = array();
		$order_ids = array();

		foreach ($products as $product) {
			if ($count % 10 == 0) {
			$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			if ($order_id !== $product->ordernum)

			$value = array($product->source_product,
				$product->related_product);

			if ($product->order_count === null &&
				!in_array($value, $inserted_pairs)) {
				SwatDB::insertRow($this->db, 'ProductPopularProductBinding',
					array('integer:source_product',
						'integer:related_product',
						'integer:order_count'),
					array('source_product' => $product->source_product,
						'related_product' => $product->related_product,
						'order_count' => 1));

				$inserted_pairs[] = $value;
			} else {
				SwatDB::query($this->db, sprintf('
					update ProductPopularProductBinding
					set order_count = order_count + 1
					where source_product = %s and
						related_product = %s',
					$this->db->quote($product->source_product, 'integer'),
					$this->db->quote($product->related_product, 'integer')));
			}

			$order_ids[$product->ordernum] = $product->ordernum;

			$count++;
		}

		if (count($order_ids) > 0)
			SwatDB::updateColumn($this->db, 'Orders',
				'boolean:popular_products_processed',
				true, $order_ids);
	}

	// }}}
	// {{{ protected function getRelatedProducts()

	/**
	 * Gets a list of related products
	 *
	 * Selects a cross-list of all products in an order and check if
	 * a relation aleady exists between the products in the popular
	 * products binding table
	 */
	protected function getRelatedProducts()
	{
		$this->output(Store::_('Querying order items ... ').'   ',
			self::VERBOSITY_ALL);

		$sql = sprintf('select distinct
				OrderItem.product as source_product,
				RelatedOrderItem.product as related_product,
				ProductPopularProductBinding.order_count,
				OrderItem.ordernum
			from OrderItem
			inner join Orders on OrderItem.ordernum = Orders.id
			inner join OrderItem as RelatedOrderItem 
				on OrderItem.ordernum = RelatedOrderItem.ordernum
			left outer join ProductPopularProductBinding on
				ProductPopularProductBinding.source_product
					= OrderItem.product
				and ProductPopularProductBinding.related_product
					= RelatedOrderItem.product
			where RelatedOrderItem.product != OrderItem.product
				and Orders.popular_products_processed = %s',
			$this->db->quote(false, 'boolean'));

		return SwatDB::query($this->db, $sql);
	}

	// }}}
}

?>
