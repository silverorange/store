<?php

require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'Store/dataobjects/StoreOrderItem.php';

/**
 * @package   Store
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderItemsTableStoreFactory
{
	// {{{ protected properties

	/**
	 * @var StoreOrder
	 */
	protected $order = null;

	/**
	 * @var string
	 */
	protected $image_dimension = 'thumb';

	/**
	 * @var array
	 */
	protected $item_counts = null;

	/**
	 * @var boolean
	 */
	protected $include_title = true;

	/**
	 * @var boolean
	 */
	protected $include_group_title = true;

	// }}}
	// {{{ public function __construct()

	public function __construct(StoreOrder $order)
	{
		$this->setOrder($order);
	}

	// }}}
	// {{{ public function get()

	public function get()
	{
		$store = new SwatTableStore();

		foreach ($this->order->items as $item) {
			$ds = $this->getOrderItemDetailsStore($item);
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ public function setImageDimension()

	public function setImageDimension($shortname)
	{
		if ($shortname instanceof SiteImageDimension) {
			$shortname = $shortname->shortname;
		}

		$this->image_dimension = $shortname;
	}

	// }}}
	// {{{ public function setIncludeTitle()

	public function setIncludeTitle($include_title)
	{
		$this->include_title = (bool)$include_title;
	}

	// }}}
	// {{{ public function setIncludeGroupTitle()

	public function setIncludeGroupTitle($include_group_title)
	{
		$this->include_group_title = (bool)$include_group_title;
	}

	// }}}
	// {{{ public function setOrder()

	public function setOrder(StoreOrder $order)
	{
		$this->order = $order;
		$this->item_counts = null;
	}

	// }}}
	// {{{ protected function getOrderItemDetailsStore()

	protected function getOrderItemDetailsStore(StoreOrderItem $item)
	{
		$ds = new SwatDetailsStore($item);

		$ds->item        = $item;
		$ds->description = $this->getOrderItemDescription($item);
		$ds->item_count  = $this->getProductItemCount($item);

		if ($item->alias_sku !== null && $item->alias_sku != '') {
			$ds->sku.= sprintf(Store::_(' (%s)'), $item->alias_sku);
		}

		$store_item = $item->getAvailableItem($this->order->locale->region);
		if ($store_item instanceof StoreItem &&
			$store_item->product->primary_image !== null) {

			$image = $store_item->product->primary_image;
			$ds->image = $image->getUri($this->image_dimension);
			$ds->image_width = $image->getWidth($this->image_dimension);
			$ds->image_height = $image->getHeight($this->image_dimension);

		} else {
			$ds->image = null;
			$ds->image_width = null;
			$ds->image_height = null;
		}

		if ($ds->sku !== null) {
			$ds->sku_formatted = sprintf(
				'<span class="item-sku">%s</span> ',
				SwatString::minimizeEntities($ds->sku));
		} else {
			$ds->sku_formatted = '&nbsp;&nbsp;&nbsp;&nbsp;';
		}

		return $ds;
	}

	// }}}
	// {{{ protected function getOrderItemDescription()

	protected function getOrderItemDescription(StoreOrderItem $item)
	{
		$description = '';

		if ($this->include_title) {
			$description.= $this->getOrderItemTitle($item);
		}

		$parts = array();

		if ($this->include_group_title && $item->item_group_title != '') {
			$parts[] = $item->item_group_title;
		}

		if ($item->description != '') {
			$parts[] = strip_tags($item->description);
		}

		$description.= '<div>'.implode(', ', $parts).'</div>';;

		return $description;
	}

	// }}}
	// {{{ protected function getOrderItemTitle()

	protected function getOrderItemTitle(StoreOrderItem $item)
	{
		$title = array();

		if ($item->sku !== null) {
			$title[] = $item->sku;
		}

		if ($item->product_title != '') {
			$title[] = $item->product_title;
		}

		if (count($title) > 0) {
			$header = new SwatHtmlTag('h4');
			$header->setContent(implode(' - ', $title));
			$title = $header->__toString();
		} else {
			$title = '';
		}

		return $title;
	}

	// }}}
	// {{{ protected function getProductItemCount()

	protected function getProductItemCount(StoreOrderItem $item)
	{
		// build item count cache if it doesn't exist
		if ($this->item_counts === null) {
			$this->item_counts = array();

			// clone because we often call getProductItemCount inside an
			// iteration of the order items. SwatDBDataObject doesn't support
			// nested iteration or external iterators
			$items = clone $this->order->items;

			foreach ($items as $current_item) {
				$id = $this->getItemIndex($current_item);
				if (isset($this->item_counts[$id])) {
					$this->item_counts[$id]++;
				} else {
					$this->item_counts[$id] = 1;
				}
			}
		}

		// return value from cache if it exists, or return 1
		$id = $this->getItemIndex($item);
		if (isset($this->item_counts[$id])) {
			$count = $this->item_counts[$id];
		} else {
			$count = 1;
		}

		return $count;
	}

	// }}}
	// {{{ protected function getItemIndex()

	protected function getItemIndex(StoreOrderItem $item)
	{
		return $item->product;
	}

	// }}}
}

?>
