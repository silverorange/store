<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/admin/components/Product/'.
	'include/StoreProductRelatedProductDependency.php';

/**
 * Delete confirmation page for Related Products
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductRelatedProductDelete extends AdminDBDelete
{
	// {{{ private properties

	private $id;
	private $category_id = null;

	// }}}
	// {{{ public function setId()

	public function setId($id)
	{
		$this->id = $id;
	}

	// }}}
	// {{{ public function setCategory()

	public function setCategory($category_id)
	{
		$this->category_id = $category_id;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->id = SiteApplication::initVar('id', null,
			SiteApplication::VAR_POST);

		$this->category_id = SiteApplication::initVar('category', null,
			SiteApplication::VAR_POST);

		$yes_button = $this->ui->getWidget('yes_button');
		$yes_button->title = Store::_('Remove Related Products');
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from ProductRelatedProductBinding
			where source_product = %s and related_product in (%s)';

		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One related product has been removed.',
			'%d related products have been removed.', $num),
			SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildNavBar();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('category', $this->category_id);

		$item_list = $this->getItemList('integer');

		$dep = new StoreProductRelatedProductDependency();
		$dep->product_title = SwatDB::queryOneFromTable($this->app->db,
			'Product', 'text:title', 'id', $this->id);

		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Product', 'integer:id', null, 'text:title', 'title',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
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

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $this->id);

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $this->id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s', $this->id, 
				$this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Remove Related')));

		$this->title = $product_title;
	}

	// }}}
}

?>
