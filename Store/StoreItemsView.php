<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatHtmlHeadEntrySet.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/StoreMessage.php';

/**
 * Control to display and process items on a product page
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemsView extends SwatControl
{
	// {{{ public properties

	public $ui_xml = 'Store/pages/product-items-view.xml';

	// }}}
	// {{{ protected properties

	/**
	 * The product displayed by this items view
	 *
	 * @var StoreProductWrapper
	 */
	protected $product;

	protected $default_quantity;

	protected $ui;

	protected $has_description = false;

	// }}}
	// {{{ public function setProduct()

	/**
	 * Sets the product of this view
	 *
	 * @param StoreProductWrapper $product the product of this items view.
	 */
	public function setProduct(StoreProduct $product)
	{
		$this->product = $product;
	}

	// }}}
	// {{{ public function setSource()

	/**
	 * Sets the page source for this view's form action
	 *
	 * @param string $source The page source
	 */
	public function setSource($source)
	{
		$this->source = $source;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$items_form = $this->ui->getWidget('form');
		$items_form->action = $this->source;

		if ($this->ui->hasWidget('quantity')) {
			$quantity = $this->ui->getWidget('quantity');
			$this->default_quantity = $quantity->value;
		}

		$view = $this->ui->getWidget('items_view');

		$this->ui->init();
		$view->model = $this->getItemTableStore($view);
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->ui->process();
	}

	// }}}
	// {{{ public function getItemsToAdd()

	public function getItemsToAdd()
	{
		$form = $this->ui->getWidget('form');
		$items = array();

		if ($form->isProcessed()) {
			$view = $this->ui->getWidget('items_view');
			if ($view->hasSpanningColumn('quantity_column'))
				$column = $view->getSpanningColumn('quantity_column');
			else
				$column = $view->getColumn('quantity_column');

			$renderer = $column->getRenderer('quantity_renderer');

			foreach ($renderer->getClonedWidgets() as $id => $widget) {
				if (!$renderer->hasMessage($id) && $widget->value > 0) {
					$item = new StdClass();
					$item->item_id = $id;
					$item->quantity = $widget->value;
					$items[] = $item;
				}
			}
		}

		return $items;
	}

	// }}}
	// {{{ public function hasMessage()

	public function hasMessage()
	{
		$form = $this->ui->getWidget('form');
		return ($form->isProcessed() && $form->hasMessage());
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$view = $this->ui->getWidget('items_view');

		if (!$this->has_description)
			$view->getColumn('description_column')->visible = false;

		$this->ui->display();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	/**
	 * Gets the SwatHtmlHeadEntry objects needed by this view
	 *
	 * @return SwatHtmlHeadEntrySet the SwatHtmlHeadEntry objects needed by
	 *                               this view.
	 */
	public function getHtmlHeadEntrySet()
	{
		if ($this->isVisible())
			$set = $this->ui->getRoot()->getHtmlHeadEntrySet();
		else
			$set = new SwatHtmlHeadEntrySet();

		return $set;
	}

	// }}}
	// {{{ protected function getItemTableStore()

	protected function getItemTableStore(SwatTableView $view)
	{
		$store = new SwatTableStore();
		$last_sku = null;
		$tab_index = 1;

		foreach ($this->product->items as $item) {
			if ($item->isEnabled()) {
				$ds = $this->getItemDetailsStore($item);
				$ds->tab_index = $tab_index++;

				$ds->sku = ($last_sku === $item->sku) ?
					'' : $item->sku;

				$last_sku = $item->sku;
				$store->add($ds);

				if ($ds->is_available)
					$view->getRow('add_button')->visible = true;
			}
		}

		$view->getRow('add_button')->tab_index = $tab_index;
		return $store;
	}

	// }}}
	// {{{ protected function getItemDetailsStore()

	protected function getItemDetailsStore(StoreItem $item)
	{
		$ds = new SwatDetailsStore($item);

		$ds->description = $item->getDescription(false);

		if (strlen($ds->description) > 0)
			$this->has_description = true;

		$ds->is_available = $item->isAvailableInRegion();

		$ds->status = '';
		if (!$ds->is_available)
			$ds->status = sprintf('<span class="item-status">%s</span>',
				$item->getStatus()->title);

		$ds->price = $item->getPrice();

		return $ds;
	}

	// }}}
}

?>
