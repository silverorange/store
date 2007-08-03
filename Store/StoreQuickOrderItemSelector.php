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
	// {{{ protected properties

	/**
	 * @var SwatFlydown
	 */
	protected $items_flydown = null;

	/**
	 * @var SwatFormField
	 */
	protected $form_field = null;

	/**
	 * @var boolean
	 */
	protected $widgets_created = false;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->createEmbeddedWidgets();

		if ($this->sku === null) {
			$this->clearItems();
		} else {
			$items = $this->getItems();

			if (count($items) > 0)
				$this->form_field->title = $items->getFirst()->product->title;

			$this->buildItemsFlydown($items);
			$this->form_field->init();
		}
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		$this->createEmbeddedWidgets();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->open();
		$this->form_field->display();
		$div_tag->close();
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->createEmbeddedWidgets();
		$this->form_field->process();
		$this->value = $this->items_flydown->value;
	}

	// }}}
	// {{{ public function getState()

	public function getState()
	{
		return $this->items_flydown->getState();
	}

	// }}}
	// {{{ public function setState()

	public function setState($state)
	{
		$this->items_flydown->setState($state);
	}

	// }}}
	// {{{ public function __clone()

	public function __clone()
	{
		// re-create widgets incase our id changed before clone is called
		$this->widgets_created = false;
		$this->form_field = null;
		$this->items_flydown = null;
	}

	// }}}
	// {{{ protected function getItems()

	protected function getItems()
	{
		$sql = $this->getItemSql();
		$items = StoreItemWrapper::loadSetFromDBWithRegion(
			$this->db, $sql, $this->region, false);

		return $items;
	}

	// }}}
	// {{{ protected function clearItems()

	protected function clearItems()
	{
		$this->form_field->title = null;
		$this->items_flydown->setTree(new SwatTreeFlydownNode(null, 'root'));
	}

	// }}}
	// {{{ protected function getItemSql()

	protected function getItemSql()
	{
		$sku = strtolower($this->sku);
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

	protected function buildItemsFlydown($items)
	{
		$tree = new SwatTreeFlydownNode(null, 'root');

		if (count($items) <= 1) {
			foreach ($items as $item)
				$tree->addChild($this->getItemNode($item, true));
		} else {
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
		}

		$this->items_flydown->setTree($tree);
	}

	// }}}
	// {{{ protected function createEmbeddedWidgets()

	protected function createEmbeddedWidgets()
	{
		if (!$this->widgets_created) {
			$this->items_flydown = $this->getItemsFlydown();

			$this->form_field = new SwatFormField($this->id.'_field');
			$this->form_field->parent = $this;
			$this->form_field->add($this->items_flydown);

			$this->widgets_created = true;
		}
	}

	// }}}
	// {{{ protected function getItemsFlydown()

	protected function getItemsFlydown()
	{
		if ($this->items_flydown === null) {
			$this->items_flydown = new SwatGroupedFlydown($this->id.'_items');
			$this->items_flydown->show_blank = false;
		}

		return $this->items_flydown;
	}

	// }}}
	// {{{ protected function getItemNode()

	protected function getItemNode($item, $show_item_group = false)
	{
		$renderer = new StoreItemPriceCellRenderer();
		$description = $item->getDescription($show_item_group);
		$renderer->value = $item->getPrice();
		$renderer->quantity_discounts = $item->quantity_discounts;

		ob_start();
		$renderer->render();
		$description.= ' '.ob_get_clean();

		return new SwatTreeFlydownNode($item->id, $description);
	}

	// }}}
}

?>
