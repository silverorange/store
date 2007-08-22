<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Edit page for Item Groups
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupEdit extends AdminDBEdit
{
	// {{{ private properties

	private $fields;
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML(dirname(__FILE__).'/admin-itemgroup-edit.xml');
		$this->fields = array('title');
		$this->category_id = SiteApplication::initVar('category');

		if ($this->id === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('Item Group with id ‘%s’ not found.'), $this->id));
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('title'));

		if ($this->id === null)
			$this->id = SwatDB::insertRow($this->app->db, 'ItemGroup',
				$this->fields, $values, 'id');
		else
			SwatDB::updateRow($this->app->db, 'ItemGroup', $this->fields,
				$values, 'id', $this->id);

		$message = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$values['title']));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'ItemGroup',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('Item Group with id ‘%s’ not found.', $this->id)));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
	// {{{ protected function buildInternal()
	
	protected function buildInternal()
	{
		parent::buildInternal();
		$items_view = $this->ui->getWidget('items_view');
		$items_view->model = $this->getItemsTableStore();
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ private function getItemsTableStore()

	private function getItemsTableStore()
	{
		$sql = sprintf(
			'select sku, description from Item where item_group = %s',
			$this->id);

		$store = SwatDB::query($this->app->db, $sql);

		return $store;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar() 
	{
		$this->navbar->popEntry();

		if ($this->category_id === null) {
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Search'), 'Product'));

		} else {
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		$product_id = SwatDB::queryOneFromTable($this->app->db, 'ItemGroup',
			'integer:product', 'id', $this->id);

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $product_id);

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $product_id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s', $product_id,
				$this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Edit Group')));
		$this->title = $product_title;
	}

	// }}}
}
?>
