<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Dataobject to group {@link StoreItem} objects within a {@link StoreProduct}
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroup extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Id of Product with ItemGroup belongs to
	 *
	 * @var integer
	 */
	public $product;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	// }}}
	// {{{ protected properties

	/**
	 * The region to use when loading region-specific fields in item sub-data-
	 * objects
	 *
	 * @var StoreRegion
	 * @see StoreItemGroup::setRegion()
	 */
	protected $region = null;

	// }}}
	// {{{ public function setRegion()

	/**
	 * Sets the region to use when loading region-specific fields for item
	 * sub-data-objects
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to exclude items unavailable in
	 *                           the current join region when loading item
	 *                           sub-data-objects.
	 */
	public function setRegion(StoreRegion $region)
	{
		$this->region = $region;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ItemGroup';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadCheapestItem()

	/**
	 * Loads the cheapest item of this item group
	 */
	protected function loadCheapestItem()
	{
		$cheapest_item = null;

		if ($this->hasInternalValue('cheapest_item') &&
			$this->getInternalValue('cheapest_item') !== null) {
			$cheapest_item_id = $this->getInternalValue('cheapest_item');

			$sql = 'select * from Item where id = %s';

			$sql = sprintf($sql,
				$this->db->quote($cheapest_item_id, 'integer'));
		} else {
			$sql = 'select * from Item where id in
				(select getItemGroupCheapestItem(%s, %s))';

			$sql = sprintf($sql,
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($this->region->id, 'integer'));
		}

		$wrapper = SwatDBClassMap::get('StoreItemWrapper');
		$rs = SwatDB::query($this->db, $sql, $wrapper);
		$cheapest_item = $rs->getFirst();
		$cheapest_item->setRegion($this->region);

		return $cheapest_item;
	}

	// }}}
}

?>
