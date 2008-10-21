<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Store/Store.php';
require_once 'Admin/Admin.php';
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
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ protected properties

	protected $inserted_pairs = array();

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $title, $documentation)
	{
		parent::__construct($id, $title, $documentation);

		$all = new SiteCommandLineArgument(
			array('-A', '--all'), 'reindex',
			Store::_('Re-indexes all orders rather than just updating '.
				'indexes for new orders.'));

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
		$this->debug(Store::_('Reindexing all orders ... '));

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
		$config->addDefinitions(Admin::getConfigDefinitions());
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
		$orders = $this->getOrders();
		$total_orders = count($orders);
		$count = 0;

		$this->debug(Store::_('Indexing orders ... ').'   ');

		foreach ($orders as $order) {
			if ($count % 10 == 0) {
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total_orders) * 100));
			}

			$products_inserted = array();

			foreach ($this->getProductCrossList($order->id) as $product) {
				// ProductPopularity rows
				if (!in_array($product->source_product, $products_inserted)) {
					$this->insertPopularProduct($product);
					$products_inserted[] = $product->source_product;
				}

				// ProductPopularProductBinding rows
				$this->insertProductPopularProductBinding($product);
			}

			SwatDB::updateColumn($this->db, 'Orders',
				'boolean:popular_products_processed',
				true, 'id', array($order->id));

			$count++;
		}

		$this->debug(str_repeat(chr(8), 3));
		$this->debug(Store::_('done')."\n\n");
	}

	// }}}
	// {{{ protected function insertPopularProduct()

	protected function insertPopularProduct($product)
	{
		if ($product->popularity === null)
			SwatDB::insertRow($this->db, 'ProductPopularity',
				array('integer:product',
					'integer:order_count'),
				array('product' => $product->source_product,
					'order_count' => 1));
		else
			SwatDB::exec($this->db, sprintf('
				update ProductPopularity
				set order_count = order_count + 1
				where product = %s',
				$this->db->quote($product->source_product,
					'integer')));
	}

	// }}}
	// {{{ protected function insertProductPopularProductBinding()

	protected function insertProductPopularProductBinding($product)
	{
		$pair = array($product->source_product,
			$product->related_product);

		$insert_row = ($product->order_count === null &&
			!in_array($pair, $this->inserted_pairs));

		if ($insert_row) {
			SwatDB::insertRow($this->db, 'ProductPopularProductBinding',
				array('integer:source_product',
					'integer:related_product',
					'integer:order_count'),
				array('source_product' => $product->source_product,
					'related_product' => $product->related_product,
					'order_count' => 1));

			$this->inserted_pairs[] = $pair;
		} else {
			SwatDB::exec($this->db, sprintf('
				update ProductPopularProductBinding
				set order_count = order_count + 1
				where source_product = %s and
					related_product = %s',
				$this->db->quote($product->source_product, 'integer'),
				$this->db->quote($product->related_product, 'integer')));
		}
	}

	// }}}
	// {{{ protected function getOrders()

	/**
	 * Gets a list of orders to process
	 */
	protected function getOrders()
	{
		$this->debug(Store::_('Querying orders ... '));

		$sql = sprintf('select Orders.id from Orders
			where Orders.popular_products_processed = %s',
			$this->db->quote(false, 'boolean'));

		$orders = SwatDB::query($this->db, $sql);

		$this->debug(Store::_('done')."\n\n");

		return $orders;
	}

	// }}}
	// {{{ protected function getProductCrossList()

	/**
	 * Gets a list of related products
	 *
	 * Selects a cross-list of all products in an order and check if
	 * a relation aleady exists between the products in the popular
	 * products binding table
	 */
	protected function getProductCrossList($order_id)
	{
		$sql = sprintf('select * from
			OrderProductCrossListView
			where ordernum = %s',
			$this->db->quote($order_id, 'integer'));

		return SwatDB::query($this->db, $sql);
	}

	// }}}
}

?>
