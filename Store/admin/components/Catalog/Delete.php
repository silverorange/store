<?php

/**
 * Delete confirmation page for Catalogs
 *
 * Only single deletes are supported. Deletes only happen from the details page
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData(): void
	{
		parent::processDBData();

		$id = $this->getFirstItem();

		if ($this->catalogIsEnabled()) {
			$sql = sprintf('delete from Catalog where id = %s
				and id not in (select Catalog from Product)',
				$this->app->db->quote($id, 'integer'));
		} else {
			$sql = sprintf('delete from Catalog where id = %s',
				$this->app->db->quote($id, 'integer'));
		}

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::_('One %s has been deleted.'),
			Store::_('catalog')), 'notice');

		$this->app->messages->add($message);

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('product');
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
		$dep->setTitle(Store::_('catalog'), Store::_('catalogs'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Catalog', 'integer:id', null, 'text:title', 'id',
			sprintf('id = %s', $id), AdminDependency::DELETE);

		$this->getDependencies($dep, $id);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content_type = 'text/xml';
		$message->content = $dep->getMessage();

		$note = $this->ui->getWidget('note');
		$note->visible = true;
		$note->content = $this->getNoteContent();

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();

		$this->buildCatalogNavBar($id);
	}

	// }}}
	// {{{ protected function getDependencies()

	protected function getDependencies($dep, $id)
	{
		// dependent products
		$dep_products = new AdminSummaryDependency();
		$dep_products->setTitle(Store::_('product'), Store::_('products'));

		if ($this->catalogIsEnabled()) {
			$default_status_level = AdminDependency::NODELETE;
		} else {
			$default_status_level = AdminDependency::DELETE;
		}

		$dep_products->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'Product', 'integer:id', 'integer:catalog',
			sprintf('catalog = %s', $id), $default_status_level);

		$dep->addDependency($dep_products);
	}

	// }}}
	// {{{ protected function getNoteContent()

	protected function getNoteContent()
	{
		return sprintf(Store::_('A %s must contain no products, or be '.
			'disabled in all regions in order to be deleted.'),
			Store::_('catalog'));
	}

	// }}}
	// {{{ private function buildCatalogNavBar()

	private function buildCatalogNavBar($id)
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
