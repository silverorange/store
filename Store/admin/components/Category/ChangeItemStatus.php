<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminDependency.php';
require_once 'Store/StoreCatalogSwitcher.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/StoreClassMap.php';

/**
 * Remove products confirmation page for Categories
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryChangeItemStatus extends AdminDBConfirmation
{
	// {{{ private properties

	private $category_id;
	private $status;

	// }}}
	// {{{ public function setCategory()

	public function setCategory($category_id)
	{
		$this->category_id = $category_id;
	}

	// }}}
	// {{{ public function setStatus()

	public function setStatus($status)
	{
		$this->status = $status;
	}

	// }}}
	
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->category_id = SiteApplication::initVar('category');
		$this->status = SiteApplication::initVar('status');

		$this->catalog_switcher = new StoreCatalogSwitcher();
		$this->catalog_switcher->db = $this->app->db;
		$this->catalog_switcher->init();
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');
		
		$sql = sprintf('update Item set status = %s where id in (%s)',
			$this->app->db->quote($this->status, 'integer'),
			$this->getItemQuerySQL());

		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One item has had its status set as “%s”.',
			'%s items have had their status set as “%s”.', $num),
			SwatString::numberFormat($num), $this->getStatusTitle()),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}
	
	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildNavBar();

		$rs = SwatDB::query($this->app->db, $this->getItemQuerySQL());
		$count = count($rs);

		if ($count == 0) {
			$this->switchToCancelButton();
			$msg = Store::_('There are no items in the selected categories.');
		} else {
			$msg = sprintf(Store::ngettext(
				'If you proceed, %s item will be have its status set as “%s”.',
				'If you proceed, %s items will be have their status set as '.
				'“%s”.', $count), SwatString::numberFormat($count),
				$this->getStatusTitle());
		}

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $msg;
		$message->content_type = 'text/xml';

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('status', $this->status);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->popEntry();

		if ($this->category_id !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getCategoryNavbar', array($this->category_id));
			
			foreach ($navbar_rs as $row)
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Change Item Status Confirmation')));
	}

	// }}}

	// {{{ private function getItemQuerySQL()

	private function getItemQuerySQL()
	{
		$item_list = $this->getItemList('integer');

		$sql = 'select distinct Item.id
				from Item
					inner join Product on Product.id = Item.product
					inner join CategoryProductBinding on 
						CategoryProductBinding.product = Product.id
					inner join getCategoryDescendents(null) as
						category_descendents on
						category_descendents.descendent =
							CategoryProductBinding.category
				where category_descendents.category in (%s)
					and Product.catalog in (%s)';

		$sql = sprintf($sql,
			$item_list,
			$this->catalog_switcher->getSubquery());

		return $sql;
	}

	// }}}
	// {{{ private function getItemQuerySQL()

	private function getItemQuerySQL()
	{
		$class_map = StoreClassMap::instance();
		$item = $class_map->resolveClass('StoreItem');

		return call_user_func(array($item, 'getStatusTitle'), $this->status);
	}

	// }}}
}

?>
