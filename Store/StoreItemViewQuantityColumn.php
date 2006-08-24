<?php

require_once('Store/StoreQuantityCellRenderer.php');
require_once('Swat/SwatTableViewColumn.php');
require_once('Swat/SwatTableViewCheckAllRow.php');
require_once('Swat/SwatHtmlTag.php');

/**
 * A quantity entry column.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemViewQuantityColumn extends SwatTableViewColumn
{
	// {{{ private properties

	private $items = null;

	private $quantity_renderer = null;

	// }}}
	// {{{ public function init()

	public function init()
	{
		// TODO: autogenerate an id here
		if ($this->id === null)
			$this->id == 'quantity';

		/*
		if ($this->view !== null)
			$this->view->addJavaScript(
				'packages/store/javascript/store-item-view-quantity-column.js',
				Store::PACKAGE_ID);
		*/
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$name = $this->getRendererName();

		if (isset($_POST[$name]) && is_array($_POST[$name]))
			$this->items = $_POST[$name];
	}

	// }}}
	// {{{ public function getItems()

	public function getItems()
	{
		return $this->items;
	}

	// }}}
	// {{{ private function getRendererName()

	private function getRendererName()
	{
		$renderer = $this->getQuantityRenderer();

		return $renderer->id;
	}

	// }}}
	// {{{ private function getQuantityRenderer()

	private function getQuantityRenderer()
	{
		foreach ($this->renderers as $renderer) 
			if ($renderer instanceof StoreQuantityCellRenderer)
				return $renderer;

		throw new SwatException("The column '{$this->id}' must contain a ".
			'quantity cell renderer.');
	}

	// }}}
}

?>
