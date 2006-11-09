<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for ItemGroups
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupDelete extends AdminDBDelete
{
	// {{{ private properties

	private $category_id = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->category_id = SiteApplication::initVar('category');
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');
		
		$sql = sprintf(
			'update Item set item_group = NULL where item_group in (%s)',
			$item_list);

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('delete from ItemGroup where id in (%s)', $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One group has been deleted.',
			'%d groups have been deleted.', $num),
			SwatString::numberForamt($num)),
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

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('category', $this->category_id);

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->title = Store::_('Group');
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'ItemGroup', 'integer:id', null, 'text:title', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$message_content = Store::_('%s<p>Items in removed groups will '.
			'%snot%s be deleted. Items in removed groups will still be '.
			'available for sale and will appear ungrouped on the website.</p>');

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = sprintf($message_content, $dep->getMessage(),
			'<em>', '</em>';

		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar() 
	{
		$this->navbar->popEntry();

		if ($this->category_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry) {
				$this->title = $entry->title;
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
			}
		}

		$id = $this->getFirstItem();

		$product_id = SwatDB::queryOneFromTable($this->app->db, 'ItemGroup',
			'integer:product', 'id', $id);

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $product_id);

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $product_id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s', $product_id, 
				$this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Delete Group')));
		$this->title = $product_title;
	}

	// }}}
}

?>
