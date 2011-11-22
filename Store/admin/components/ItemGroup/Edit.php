<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreItemGroup.php';
require_once 'Store/dataobjects/StoreItemWrapper.php';

/**
 * Edit page for Item Groups
 *
 * @package   Store
 * @copyright 2005-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $item_group;

	// }}}
	// {{{ private properties

	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->getUiXml());
		$this->category_id = SiteApplication::initVar('category');
		$this->initItemGroup();
	}

	// }}}
	// {{{ protected function initItemGroup()

	protected function initItemGroup()
	{
		$class_name = SwatDBClassMap::get('StoreItemGroup');
		$this->item_group = new $class_name();
		$this->item_group->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->item_group->load($this->id))
				throw new AdminNotFoundException(sprintf(
					Store::_('An item group with id "%s" not found'),
					$this->id));
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/ItemGroup/edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateItemGroup();
		$this->item_group->save();

		$message = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$this->item_group->title));

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}
	// {{{ protected function updateItemGroup()

	protected function updateItemGroup()
	{
		$values = $this->ui->getValues(array('title'));

		$this->item_group->title = $values['title'];
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->item_group));
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
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(2);

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

		$item_group = $this->item_group;
		$product    = $item_group->product;

		if ($this->category_id === null) {
			$this->navbar->createEntry($product->title,
				sprintf('Product/Details?id=%s', $product->id));

			$this->navbar->createEntry($item_group->sku,
				sprintf('ItemGroup/Details?id=%s', $item_group->id));
		} else {
			$this->navbar->createEntry($product->title,
				sprintf('Product/Details?id=%s&category=%s',
					$product->id, $this->category_id));

			$this->navbar->createEntry($item_group->sku,
				sprintf('ItemGroup/Details?id=%s&category=%s',
					$item_group->id, $this->category_id));
		}

		$this->navbar->createEntry('Edit Item Group');
	}

	// }}}
}

?>
