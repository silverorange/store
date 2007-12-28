<?php

require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/StoreItemPriceCellRenderer.php';
require_once 'Swat/SwatGroupedFlydown.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatInputControl.php';

/**
 * Item selector that puts items into optgroups based on item
 * groups
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
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
	 * @var StoreItemWrapper
	 */
	private $items;

	// }}}
	// {{{ public function init()

	public function init()
	{
		if ($this->sku === null)
			$this->clearItems();
		elseif (count($this->getItems()) > 1)
			$this->buildItemsFlydown($this->getItems());
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

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
		$div_tag->id = $this->id;
		$div_tag->class = 'store-quick-order-product';
		$div_tag->open();

		if (count($items) > 0) {
			$this->displayProduct($items->getFirst()->product);

			if (count($items) > 1)
				$this->displayItems();
			else
				$this->displayItem();
		}

		$div_tag->close();
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->value = $this->getCompositeWidget('items_flydown')->value;
	}

	// }}}
	// {{{ public function __clone()

	public function __clone()
	{
		// TODO: think about adding a resetCompositeWidgets() method to
		// SwatWidget

		/*
		// re-create widgets in case our id changed before clone is called
		$this->widgets_created = false;
		$this->form_field = null;
		$this->items_flydown = null;
		*/
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
	// {{{ protected function displayProduct()

	protected function displayProduct(StoreProduct $product)
	{
		echo SwatString::minimizeEntities($product->title);
	}

	// }}}
	// {{{ protected function displayItems()

	protected function displayItems()
	{
		$this->getCompositeWidget('items_flydown')->display();
	}

	// }}}
	// {{{ protected function displayItem()

	protected function displayItem()
	{
		$item = $this->getItems()->getFirst();
		echo $this->getItemDescription($item);
	}

	// }}}
	// {{{ protected function getNodeDescription()

	protected function getNodeDescription(StoreItem $item,
		$show_item_group = false)
	{
		$renderer = $this->getItemPriceCellRenderer($item);
		$description = $item->getDescription($show_item_group);

		ob_start();

		if (!$item->hasAvailableStatus())
			printf('(%s)',
				$item->getStatus()->title);

		$renderer->render();
		$description.= ' '.ob_get_clean();

		return $description;
	}

	// }}}
	// {{{ protected function getItemDescription()

	protected function getItemDescription(StoreItem $item,
		$show_item_group = false)
	{
		$renderer = $this->getItemPriceCellRenderer($item);
		$description = $item->getDescription($show_item_group);

		ob_start();

		if (!$item->hasAvailableStatus())
			printf('<div class="item-status">%s</div>',
				$item->getStatus()->title);

		$renderer->render();
		$description.= ' '.ob_get_clean();

		return $description;
	}

	// }}}
	// {{{ protected function getItems()

	/**
	 * @return StoreItemWrapper
	 */
	protected function getItems()
	{
		if ($this->items === null) {
			$sql = $this->getItemSql();
			$this->items = StoreItemWrapper::loadSetFromDBWithRegion(
				$this->db, $sql, $this->region, false);
		}

		return $this->items;
	}

	// }}}
	// {{{ protected function clearItems()

	protected function clearItems()
	{
		$flydown = $this->getCompositeWidget('items_flydown');
		$flydown->setTree(new SwatTreeFlydownNode(null, 'root'));
	}

	// }}}
	// {{{ protected function getItemSql()

	protected function getItemSql()
	{
		$sku = trim(strtolower($this->sku));

		if (substr($sku, 0, 1) === '#' && strlen($sku) > 1)
			$sku = substr($sku, 1);

		$sql = sprintf('select id from Item
			inner join VisibleProductCache on
				Item.product = VisibleProductCache.product and
					VisibleProductCache.region = %s
			where lower(sku) = %s',
			$this->db->quote($this->region->id, 'integer'),
			$this->db->quote($sku, 'text'));

		return $sql;
	}

	// }}}
	// {{{ protected function buildItemsFlydown()

	protected function buildItemsFlydown(StoreItemWrapper $items)
	{
		$tree = new SwatTreeFlydownNode(null, 'root');

		$item_group = false;
		$num_item_groups = 0;
		foreach ($items as $item) {
			if ($item->getInternalValue('item_group') !== $item_group) {
				$item_group = $item->getInternalValue('item_group');

				if ($item_group === null) {
					$group_title = Store::_('[ungrouped]');
				} else {
					$group_title = $item->item_group->title;
					$num_item_groups++;
				}

				$item_group_node =
					new SwatTreeFlydownNode(null, $group_title);

				$tree->addChild($item_group_node);
			}

			$item_group_node->addChild($this->getItemNode($item));
		}

		// flatten tree is there are no item groups
		if ($num_item_groups == 0) {
			$item_group_node->parent = null;
			$tree = $item_group_node;
		}

		$this->getCompositeWidget('items_flydown')->setTree($tree);
	}

	// }}}
	// {{{ protected function getItemPriceCellRenderer()

	protected function getItemPriceCellRenderer(StoreItem $item)
	{
		$renderer = new StoreItemPriceCellRenderer();
		$renderer->value = $item->getDisplayPrice();
		$renderer->original_value = $item->getPrice();
		$renderer->quantity_discounts = $item->quantity_discounts;

		return $renderer;
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
	// {{{ private function getItemNode()

	private function getItemNode(StoreItem $item, $show_item_group = false)
	{
		return new SwatTreeFlydownNode($item->id,
			$this->getNodeDescription($item, $show_item_group));
	}

	// }}}
}

?>
