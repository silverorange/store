<?php

require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatFlydown.php';

/**
 *
 *
 * @package   Store
 * @copyright 2006 silverorange
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
			$this->form_field->title = null;
			$this->items_flydown->options = array();
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
		$this->form_field->display();
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
			$this->db, $sql, $this->region->id, false);

		return $items;
	}

	// }}}
	// {{{ protected function clearItems()

	protected function clearItems()
	{
		$this->form_field->title = null;
		$this->items_flydown->options = array();
	}

	// }}}
	// {{{ protected function getItemSql()

	protected function getItemSql()
	{
		$sku = strtolower($this->sku);
		$sql = sprintf('select id from Item where lower(sku) = %s',
			$this->db->quote($sku, 'text'));

		return $sql;
	}

	// }}}
	// {{{ protected function buildItemsFlydown()

	protected function buildItemsFlydown($items)
	{
		foreach ($items as $item) {
			$description = $item->getDescription();
			$description.= ' '.SwatString::moneyFormat($item->price);
			$this->items_flydown->addOption($item->id, $description);
		}
	}

	// }}}
	// {{{ protected function createEmbeddedWidgets()

	protected function createEmbeddedWidgets()
	{
		if (!$this->widgets_created) {
			$this->items_flydown = new SwatFlydown($this->id.'_items');
			$this->items_flydown->show_blank = false;

			$this->form_field = new SwatFormField($this->id.'_field');
			$this->form_field->parent = $this;
			$this->form_field->add($this->items_flydown);

			$this->widgets_created = true;
		}
	}

	// }}}
}

?>
