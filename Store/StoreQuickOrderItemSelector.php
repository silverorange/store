<?php

require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/StoreItemPriceCellRenderer.php';
require_once 'Swat/SwatGroupedFlydown.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatInputControl.php';

/**
 * Item selector that puts items into optgroups based on item groups
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuickOrderItemSelector extends SwatInputControl implements SwatState
{
	// {{{ public properties

	/**
	 * @var StoreRegion
	 */
	public $region;

	/**
	 * @var MDB2_Driver_Common
	 */
	public $db;

	/**
	 * @var string
	 */
	public $sku;

	/**
	 * @var integer
	 */
	public $value;

	// }}}
	// {{{ private properties

	/**
	 * Cached items
	 *
	 * @var StoreItemWrapper
	 */
	private $items;

	/**
	 * Item SKU used to get list of cached items
	 *
	 * If this is different than the {@Link StoreQuickOrderItemSelector::$sku},
	 * the cached items are cleared and regenerated for the new sku.
	 *
	 * @var string
	 */
	private $items_sku;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$items_flydown = $this->getCompositeWidget('items_flydown');
		$items_flydown->setTree($this->getItemTree());

		parent::init();
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'store-quick-order-description';
		$div_tag->open();
		$this->displayContent();
		$div_tag->close();
	}

	// }}}
	// {{{ public function displayContent()

	public function displayContent()
	{
		if (!$this->visible)
			return;

		$items = $this->getItems();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id.'_product';
		$div_tag->class = 'store-quick-order-product';
		$div_tag->open();

		if (count($items) > 0) {
			$product = $items->getFirst()->product;

			$image = $this->getImage($items);
			if ($image !== null)
				$this->displayImage($image, $product);

			$content_div_tag = new SwatHtmlTag('div');
			$content_div_tag->class = 'store-quick-order-product-content';
			$content_div_tag->open();

			$this->displayProduct($product);

			$item_div_tag = new SwatHtmlTag('div');
			$item_div_tag->class = 'store-quick-order-item';
			$item_div_tag->open();

			if (count($items) == 1)
				$this->displayItem();
			else
				$this->displayItems();

			$item_div_tag->close();
			$content_div_tag->close();
		}

		$div_tag->close();
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->value = $this->getCompositeWidget('items_flydown')->value;
	}

	// }}}
	// {{{ public function getState()

	public function getState()
	{
		return $this->getCompositeWidget('items_flydown')->getState();
	}

	// }}}
	// {{{ public function setState()

	public function setState($state)
	{
		$this->getCompositeWidget('items_flydown')->setState($state);
	}

	// }}}
	// {{{ protected function getImage()

	protected function getImage(StoreItemWrapper $items)
	{
		$product = $items->getFirst()->product;
		$image = $product->primary_image;

		return $image;
	}

	// }}}
	// {{{ protected function displayImage()

	protected function displayImage(SiteImage $image, StoreProduct $product)
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->open();

		$img_tag = $image->getImgTag('pinky');
		$img_tag->display();

		if (!$product->isAvailableInRegion($this->region)) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'out-of-stock';
			$span_tag->setContent('');
			$span_tag->display();
		}

		$span_tag->close();
	}

	// }}}
	// {{{ protected function displayProduct()

	protected function displayProduct(StoreProduct $product)
	{
		$product_title_tag = new SwatHtmlTag('span');
		$product_title_tag->class = 'store-quick-order-product-title';
		$product_title_tag->setContent($product->title);
		$product_title_tag->display();
	}

	// }}}
	// {{{ protected function displayItems()

	/**
	 * Displays this item selector when there are multiple items for the given
	 * sku
	 */
	protected function displayItems()
	{
		$this->getCompositeWidget('items_flydown')->display();
	}

	// }}}
	// {{{ protected function displayItem()

	/**
	 * Displays this item selector when there is only one item for the given
	 * sku
	 */
	protected function displayItem()
	{
		$item = $this->getItems()->getFirst();
		echo $this->getItemDescription($item);
	}

	// }}}
	// {{{ protected function getItems()

	/**
	 * Gets the list of items selectable by this selector based on this
	 * selector's sku
	 *
	 * The results are cached across multiple calls to this method as long as
	 * the sku remains the same.
	 *
	 * @return StoreItemWrapper
	 */
	protected function getItems()
	{
		if ($this->items_sku !== $this->sku) {
			$sku = $this->normalizeSku($this->sku);
			if ($sku != '') {
				$sql = $this->getItemSql();
				$this->items = StoreItemWrapper::loadSetFromDBWithRegion(
					$this->db, $sql, $this->region, false);
			} else {
				$this->items = null;
			}

			$this->items_sku = $this->sku;
		}

		return $this->items;
	}

	// }}}
	// {{{ protected function getItemSql()

	protected function getItemSql()
	{
		$sku = $this->normalizeSku($this->sku);

		$sql = sprintf('select Item.id from Item
			inner join VisibleProductCache on
				Item.product = VisibleProductCache.product and
					VisibleProductCache.region = %1$s
			where lower(Item.sku) = %2$s
				or Item.id in (select item from ItemAlias where
				lower(ItemAlias.sku) = %2$s)',
		$this->db->quote($this->region->id, 'integer'),
		$this->db->quote($sku, 'text'));

		return $sql;
	}

	// }}}
	// {{{ protected function getItemTree()

	protected function getItemTree()
	{
		$items = $this->getItems();

		$tree = new SwatTreeFlydownNode(null, 'root');

		if ($items !== null) {
			$item_group = false; // set to false to initialize loop
			$item_group_node = null;
			$num_item_groups = 0;
			foreach ($items as $item) {
				if ($item->getInternalValue('item_group') !== $item_group) {
					$item_group = $item->getInternalValue('item_group');

					if ($item_group === null) {
						$group_title = Store::_('[ungrouped]');
					} else {
						$group_title = ($item->item_group->title === null) ?
							'—' : $item->item_group->title;

						$num_item_groups++;
					}

					$item_group_node =
						new SwatTreeFlydownNode(null, $group_title);

					$tree->addChild($item_group_node);
				}

				$item_group_node->addChild($this->getItemTreeNode($item));
			}

			// flatten tree if there are no item groups
			if ($num_item_groups < 2 && $item_group_node !== null) {
				$item_group_node->parent = null;
				$tree = $item_group_node;
			}
		}

		return $tree;
	}

	// }}}
	// {{{ protected function getItemTreeNode()

	/**
	 * Gets an item tree node for the item selector flydown
	 *
	 * @param StoreItem the item for which to get the tree node.
	 * @param boolean $show_item_group optional. Whether or not to include the
	 *                                  item group (if it exists) in the
	 *                                  display value of tree node.
	 *
	 * @return SwatTreeFlydownNode a tree flydown node for the specified item.
	 */
	protected function getItemTreeNode(StoreItem $item,
		$show_item_group = false)
	{
		return new SwatTreeFlydownNode($item->id,
			$this->getItemTreeNodeDescription($item, $show_item_group));
	}

	// }}}
	// {{{ protected function getItemTreeNodeDescription()

	/**
	 * Gets a string containing the item description for an item intended to
	 * be used inside a tree flydown node
	 *
	 * @param StoreItem $item the item to get the description for.
	 * @param boolean $show_item_group optional. Whether or not to include the
	 *                                  item group (if it exists) in the
	 *                                  description.
	 *
	 * @return string a string containing the description of the item.
	 */
	protected function getItemTreeNodeDescription(StoreItem $item,
		$show_item_group = false)
	{
		/*
		 * Note: Entities don't need to be escaped here because the resulting
		 * string is used in a tree flydown and the tree flydown automatically
		 * escapes all entities.
		 */

		$parts = $item->getDescriptionArray();
		$description = '';

		if (isset($parts['description']))
			$description.= SwatString::minimizeEntities($parts['description']);

		if (!$item->hasAvailableStatus()) {
			if ($description != '')
				$description.= ' ';

			$description.= '('.SwatString::minimizeEntities(
				$item->getStatus()->title).')';
		}

		if ($description != '')
			$description.= ' ';

		$locale = SwatI18NLocale::get();
		$description.= SwatString::minimizeEntities(
			$locale->formatCurrency($item->getDisplayPrice()));

		$extras = array();

		if (isset($parts['group']))
			$extras[] = SwatString::minimizeEntities($parts['group']);

		if (isset($parts['part_count']))
			$extras[] = SwatString::minimizeEntities($parts['part_count']);

		if ($description != '' && count($extras) > 0)
			$description.= ' - ';

		$description.= implode(', ', $extras);

		return $description;
	}

	// }}}
	// {{{ protected function getItemDescription()

	/**
	 * Gets an XHTML fragment containing the item description for an item
	 *
	 * @param StoreItem $item the item to get the description for.
	 *
	 * @return string an XHTML fragment containing the description of the item.
	 */
	protected function getItemDescription(StoreItem $item)
	{
		$description = array();

		foreach ($item->getDescriptionArray() as $element)
			$description[] = '<div>'.SwatString::minimizeEntities($element).
				'</div>';

		$description = implode("\n", $description);

		if (!$item->hasAvailableStatus()) {
			$description.= sprintf('<div class="item-status">%s</div>',
				SwatString::minimizeEntities($item->getStatus()->title));
		}

		$renderer = $this->getItemPriceCellRenderer($item);
		ob_start();
		$renderer->render();
		$description.= ' '.ob_get_clean();

		return $description;
	}

	// }}}
	// {{{ protected function getItemPriceCellRenderer()

	protected function getItemPriceCellRenderer(StoreItem $item)
	{
		$renderer = new StoreItemPriceCellRenderer();

		$renderer->value = $item->getDisplayPrice();
		$renderer->original_value = $item->getPrice();
		$renderer->quantity_discounts = $item->quantity_discounts;
		$renderer->singular_unit = $item->singular_unit;
		$renderer->plural_unit = $item->plural_unit;

		return $renderer;
	}

	// }}}
	// {{{ protected function normalizeSku()

	protected function normalizeSku($sku)
	{
		$sku = trim(strtolower($sku));

		if (strlen($sku) > 1 && $sku[0] === '#')
			$sku = substr($sku, 1);

		return $sku;
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$items_flydown = new SwatGroupedFlydown($this->id.'_items');
		$items_flydown->show_blank = false;

		$this->addCompositeWidget($items_flydown, 'items_flydown');
	}

	// }}}
}

?>
