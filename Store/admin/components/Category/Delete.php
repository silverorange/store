<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'SwatDB/SwatDB.php';

require_once 'include/StoreCategoryProductDependency.php';

/**
 * Delete confirmation page for Categories
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryDelete extends AdminDBDelete
{
	// {{{ private properties

	// used for custom relocate
	private $relocate_id = null;

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		// in case we cancel, relocate to the current item
		if ($this->single_delete)
			$this->relocate_id = $this->getFirstItem();

		parent::processInternal();
	}

	// }}}
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		if ($this->single_delete) {
			$sql = sprintf('select parent from Category where id = %s',
				$this->app->db->quote($this->getFirstItem(), 'integer'));

			$this->relocate_id = SwatDB::queryOne($this->app->db, $sql);
		}

		$sql = 'delete from Category where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One category has been deleted.',
			'%d categories have been deleted.', $num),
			SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	/**
	 * Relocate after process
	 */
	protected function relocate()
	{
		if ($this->single_delete) {
			if ($this->relocate_id === null)
				$this->app->relocate('Category/Index');
			else
				$this->app->relocate(sprintf('Category/Index?id=%s',
					$this->relocate_id));
		} else {
			parent::relocate();
		}
	}

	// }}}
	
	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Store::_('category'), Store::_('categories'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Category', 'integer:id', null, 'text:title', 'title',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$this->getDependentCategories($dep, $item_list);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content_type = 'text/xml';
		$message->content = $dep->getMessage();

		$note = $this->ui->getWidget('note');
		$note->visible = true;
		$note->content_type = 'text/xml';
		$note->content = Store::_('Products contained in deleted categories '.
			'will <em>not</em> be deleted. A product will not be shown on the '.
			'website if all of the categories it belonged to are deleted.');

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function getDependentCategories()

	private function getDependentCategories($dep, $item_list)
	{
		$dep_subcategories = new AdminListDependency();
		$dep_subcategories->setTitle(
			Store::_('sub-category'), Store::_('sub-categories'));

		$dep_subcategories->entries = AdminListDependency::queryEntries(
			$this->app->db, 'Category', 'integer:id', 'integer:parent',
			'text:title', 'title', 'parent in ('.$item_list.')',
			AdminDependency::DELETE);

		$dep->addDependency($dep_subcategories);
	
		$this->getDependentProducts($dep, $item_list);

		if ($dep_subcategories->getItemCount() > 0) {
			$entries = array();
			foreach ($dep_subcategories->entries as $entry)
				$entries[] = $this->app->db->quote($entry->id, 'integer');

			$item_list = implode(',', $entries);

			$this->getDependentCategories($dep_subcategories, $item_list);
		}
	}

	// }}}
	// {{{ protected function getDependentProducts()

	private function getDependentProducts($dep, $item_list)
	{
		$dep_products = new StoreCategoryProductDependency();
		$dep_products->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'CategoryProductBinding', 'integer:product',
			'integer:category', 'category in ('.$item_list.')',
			AdminDependency::DELETE);

		$dep->addDependency($dep_products);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$id = $this->getFirstItem();
		$delete_entry = $this->navbar->popEntry();

		$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
			'getCategoryNavbar', array($id));
			
		foreach ($navbar_rs as $row)
			$this->navbar->addEntry(new SwatNavBarEntry($row->title,
				'Category/Index?id='.$row->id));

		if (!$this->single_delete)
			$this->navbar->popEntry();

		$this->title = $this->navbar->getLastEntry()->title;
		$this->navbar->addEntry($delete_entry);
	}

	// }}}
}

?>
