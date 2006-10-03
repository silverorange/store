<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for Catalogs
 *
 * Only single deletes are supported. Deletes only happen from the details
 * page.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCatalogDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$id = $this->getFirstItem();

		if ($this->catalogIsEnabled())
			$sql = sprintf('delete from Catalog where id = %s
				and id not in (select Catalog from Product)',
				$this->app->db->quote($id, 'integer'));
		else
			$sql = sprintf('delete from Catalog where id = %s',
				$this->app->db->quote($id, 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One catalogue has been deleted.',
			'%d catalogues have been deleted.', $num),
			SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{  protected function relocate()

	protected function relocate()
	{
		$form = $this->ui->getWidget('confirmation_form');

		if ($form->button->id == 'no_button') {
			// single delete that was cancelled, go back to details page
			parent::relocate();
		} else {
			$this->app->relocate('Catalog');
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$id = $this->getFirstItem();

		$dep = new AdminListDependency();
		$dep->title = Store::_('catalog');
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Catalog', 'integer:id', null, 'text:title', 'id',
			sprintf('id = %s', $id), AdminDependency::DELETE);

		// dependent products
		$dep_products = new AdminSummaryDependency();
		$dep_products->title = Store::_('product');

		if ($this->catalogIsEnabled())
			$default_status_level = AdminDependency::NODELETE;
		else
			$default_status_level = AdminDependency::DELETE;

		$dep_products->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'Product', 'integer:id', 'integer:catalog',
			sprintf('catalog = %s', $id), $default_status_level);

		$dep->addDependency($dep_products);

		// dependent promotions (can delete if there is a clone for this
		// catalogue)
		$dep_promotions = new AdminListDependency();
		$dep_promotions->title = Store::_('promotion');

		$sql = "select Catalog.id as parent, Promotion.id,
				%s as status_level,
				('(' || Promotion.code || ') ' || Promotion.title) as title
			from Promotion
				inner join Catalog on Promotion.catalog = Catalog.id
				left outer join CatalogCloneView
					on CatalogCloneView.catalog = Catalog.id
			where Catalog.id = %s and CatalogCloneView.clone is null";

		$sql = sprintf($sql,
			$this->app->db->quote(AdminDependency::NODELETE, 'integer'),
			$this->app->db->quote($id, 'integer'));

		$dep_promotions->entries = SwatDB::query($this->app->db, $sql,
			'AdminDependencyEntryWrapper');

		$dep->addDependency($dep_promotions);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content_type = 'text/xml';
		$message->content = $dep->getMessage();

		$note = $this->ui->getWidget('note');
		$note->visible = true;
		$note->content = sprintf(Store::_('A %s must have no products and no '.
			'promotions, or be disabled in all regions and have a clone in '.
			'order to be deleted.'), Store::_('catalog'));

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();

		$this->buildNavBar($id);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar($id)
	{
		$last_entry = $this->navbar->popEntry();
		$link = sprintf('Catalog/Details?id=%s', $id);
		$sql = sprintf('select title from Catalog where id = %s', $id);
		$title = SwatDB::queryOne($this->app->db, $sql);
		$this->navbar->createEntry($title, $link);
		$this->navbar->addEntry($last_entry);
	}

	// }}}
	// {{{ private function catalogIsEnabled()

	/**
	 * Whether or not there are enabled catalogues
	 *
	 * @return boolean true if any of the catalogues are enabled and false if
	 *                  none of the catalogues are enabled.
	 */
	private function catalogIsEnabled()
	{
		$sql = sprintf('select count(region) from CatalogRegionBinding where
			catalog in (%s)', $this->getItemList('integer'));

		$count = SwatDB::queryOne($this->app->db, $sql);

		return $count > 0;
	}

	// }}}
}

?>
