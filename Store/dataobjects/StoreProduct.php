<?php

require_once 'Store/dataobjects/StoreDataObject.php';

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
 * The load one product, use something like the following:
 *
 * <code>
 * $sql = '-- select a product here';
 * $product = $db->query($sql, null, true, 'StoreProduct');
 * </code>
 *
 * If there are many StoreProduct objects that must be loaded for a page
 * request, the MDB2 wrapper class called StoreProductWrapper should be used to
 * load the objects.
 *
 * To load many products, use something like the following:
 *
 * <code>
 * $sql = '-- select many products here';
 * $products = $db->query($sql, null, true, 'StoreProductWrapper');
 * foreach ($products as $product) {
 *     // do something with each product here ...
 * }
 * </code>
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreProductWrapper
 */
class StoreProduct extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Identifier used in URL
	 *
	 * Unique within a catalog.
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

	protected $join_region = null;
	protected $limit_by_region = true;

	// }}}
	// {{{ public function setRegion()

	public function setRegion($region, $limiting = true)
	{
		$this->join_region = $region;
		$this->limit_by_region = $limiting;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateField('createdate');

		$this->table = 'Product';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadItems()

	protected function loadItems()
	{
		$items = null;
		$wrapper = $this->class_map->resolveClass('StoreItemWrapper');

		if ($this->join_region === null) {
			$sql = 'select id from Item where product = %s';
			$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
			$items = call_user_func(array($wrapper, 'loadSetFromDB'),
				$this->db, $sql);
		} else {
			$sql = 'select id from Item where product = %s';
			$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
			$items = call_user_func(array($wrapper, 'loadSetFromDB'),
				$this->db, $sql, $this->join_region, $this->limit_by_region);
		}

		return $items;
	}

	// }}}
}

?>
