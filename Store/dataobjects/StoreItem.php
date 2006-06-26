<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreQuantityDiscountWrapper.php';
require_once 'Store/dataobjects/StoreItemRegionBindingWrapper.php';

/**
 * An item for an e-commerce web application
 *
 * Items are the lowest level in the product structure. Each product can have
 * several items. For example, you could have a tee-shirt product and several
 * items under the product describing different sizes or colours.
 *
 * <pre>
 * Category
 * |
 * -- Product
 *    |
 *    -- Item
 * </pre>
 *
 * Creating StoreItem objects is necessary when the items are on the current
 * page and must be displayed. Some StoreItem objects are stored in the
 * customer's session because they are in the customer's cart.
 *
 * If there are many StoreItem objects that must be loaded for a page request,
 * the MDB2 wrapper class called StoreItemWrapper should be used to load the
 * objects.
 *
 * This class contains mostly data.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemWrapper
 */
abstract class StoreItem extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Price
	 *
	 * This field is joined from the ItemRegionBinding table.
	 *
	 * @var float
	 */
	public $price;

	// }}}
	// {{{ public function setRegion()

	public function setRegion($region, $limiting = true)
	{
		$this->join_region = $region;
		$this->limit_by_region = $limiting;
	}

	// }}}
	// {{{ protected properties

	protected $join_region = null;
	protected $limit_by_region = true;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('region',
			$this->class_map->resolveClass('StoreRegion'));

		$this->registerInternalProperty('product',
			$this->class_map->resolveClass('StoreProduct'));

		$this->table = 'Item';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadInteral()

	/**
	 * If a limiting region is specified, load() will automatically load
	 * region specific fields for this item
	 *
	 * @param integer $id the id of the item to load into this object.
	 *
	 * @see StoreItem::setRegion()
	 */
	protected function loadInternal($id)
	{
		if ($this->join_region === null)
			return parent::loadInternal($id);

		$id_field = new SwatDBField($this->id_field, 'integer');
		$sql = 'select Item.*, ItemRegionBinding.price
			from Item
			%s ItemRegionBinding on item = Item.id
				and ItemRegionBinding.region = %s
			where Item.id = %s';

		$sql = sprintf($sql,
			$this->limit_by_region ? 'inner join' : 'left outer join',
			$this->db->quote($this->join_region, 'integer'),
			$this->db->quote($id, 'integer'));

		$rs = SwatDB::query($this->db, $sql, null);
		$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);

		return $row;
	}

	// }}}

	// loader methods
	// {{{ protected function loadQuantityDiscounts()

	protected function loadQuantityDiscounts()
	{
		if (!$this->hasInternalValue('region'))
			return null;

		$region = $this->getInternalValue('region');

		$sql = 'select QuantityDiscount.*, QuantityDiscountRegionBinding.price
			from QuantityDiscount 
			inner join QuantityDiscountRegionBinding on
			quantity_discount = QuantityDiscount.id ';

		if ($region !== null)
			$sql.= sprintf(' and region = %s',
				$this->db->quote($region, 'integer'));
                  
		$sql.= 'where QuantityDiscount.item = %s
			order by QuantityDiscount.quantity desc';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper =
			$this->class_map->resolveClass('StoreQuantityDiscountWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function loadRegionBindings()

	protected function loadRegionBindings()
	{
		$sql = 'select * from ItemRegionBinding where item = %s';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper =
			$this->class_map->resolveClass('StoreItemRegionBindingWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
