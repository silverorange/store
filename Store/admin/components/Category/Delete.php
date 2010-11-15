<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/dataobjects/StoreCategoryImageWrapper.php';

require_once 'include/StoreCategoryProductDependency.php';

/**
 * Delete confirmation page for Categories
 *
 * @package   Store
 * @copyright 2005-2010 silverorange
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

		$categories = $this->getCategories();

		if ($this->single_delete) {
			$this->relocate_id =
				$categories->getFirst()->getInternalValue('parent');
		}

		$num = 0;
		foreach ($categories as $category) {
			if ($category->getInternalValue('image') !== null) {
				$category->image->setFileBase('../images');
			}

			$category->delete();

			$num++;
		}

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One category has been deleted.',
			'%d categories have been deleted.', $num),
			SwatString::numberFormat($num)));

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
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
	// {{{ protected function getCategories()

	protected function getCategories()
	{
		$sql = sprintf('select * from Category where id in (%s)',
			$this->getItemList('integer'));

		$categories = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreCategoryWrapper'));

		$image_sql = 'select * from Image where id in (%s)';
		$categories->loadAllSubDataObjects(
			'image',
			$this->app->db,
			$image_sql,
			SwatDBClassMap::get('StoreCategoryImageWrapper'));

		return $categories;
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
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

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
