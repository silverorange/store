<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Change status page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogStatus extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Catalog/status.xml';

	// }}}
	// {{{ private properties

	private $catalog;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$id = SwatDB::queryOneFromTable($this->app->db, 'Catalog',
			'title', 'id', $this->id);

		if ($id === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('%s with id ‘%s’ not found.'), Store::_('Catalog'),
				$this->id));

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);

		$status_replicator = $this->ui->getWidget('status_replicator');
		$status_replicator->replicators = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		$sql = 'select Catalog.id, Catalog.title, clone, is_parent,
				CloneCatalog.title as clone_title
			from Catalog
				left outer join CatalogCloneView on
					Catalog.id = CatalogCloneView.catalog
				left outer join Catalog as CloneCatalog on
					CatalogCloneView.clone = CloneCatalog.id
			where Catalog.id = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'));

		$this->catalog = SwatDB::queryRow($this->app->db, $sql);
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$catalog_enabled = $this->saveStatus();

		// disable clone
		if ($catalog_enabled && $this->catalog->clone !== null)
			$this->disableClone();

		$message = new SwatMessage(
			sprintf(Store::_('The status of “%s” has been updated.'),
				$this->catalog->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function disableClone()

	protected function disableClone()
	{
		$sql = 'update CatalogRegionBinding
			set available = %s
			where catalog in
				(select clone from CatalogCloneView where catalog = %s)';

		$sql = sprintf($sql,
			$this->app->db->quote(false, 'boolean'),
			$this->app->db->quote($this->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$message->secondary_content = sprintf(Store::_(
			'“%s” has been automatically disabled in all regions.'),
			$this->catalog->clone_title);
	}

	// }}}
	// {{{ protected function saveStatus()

	protected function saveStatus()
	{
		$regions = array();
		$available_regions = array();
		$unavailable_regions = array();

		$status_replicator = $this->ui->getWidget('status_replicator');
		foreach ($status_replicator->replicators as $region => $dummy) {
			$available = $status_replicator->getWidget(
				'available', $region)->value;

			if ($available)
				$available_regions[] = $region;
			else
				$unavailable_regions[] = $region;

			$regions[] = $region;
		}

    	SwatDB::updateBinding($this->app->db, 'CatalogRegionBinding', 'catalog',
			$this->id, 'region', $regions, 'Region', 'id');

		$where_clause = sprintf('catalog = %s',
			$this->app->db->quote($this->id, 'integer'));

		SwatDB::updateColumn($this->app->db, 'CatalogRegionBinding',
			'boolean:available', true, 'region', $available_regions,
			$where_clause);

		SwatDB::updateColumn($this->app->db, 'CatalogRegionBinding',
			'boolean:available', false, 'region', $unavailable_regions,
			$where_clause);

		return (count($available_regions) > 0);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		if ($this->catalog->clone !== null) {
			$note = $this->ui->getWidget('clone_note');
			$note->visible = true;
			$note->title = Store::_('Warning');
			$note->content_type = 'text/xml';
			if ($this->catalog->is_parent) {
				$note->content = sprintf(Store::_(
					'<p>The %1$s <strong>%2$s</strong> has a clone. Enabling '.
					'the %1$s <strong>%2$s</strong> in any region will '.
					'disable the %1$s <strong>%3$s</strong> in all '.
					'regions.</p>'),
					Store::_('catalog'),
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities($this->catalog->clone_title));
			} else {
				$note->content = sprintf(Store::_(
					'<p>The %1$s <strong>%2$s</strong> is a cloned  %1$s. '.
					'Enabling the %1$s <strong>%2$s</strong> in any region '.
					'will disable the %1$s <strong>%3$s</strong> in all '.
					'regions.</p>'),
					Store::_('catalog'),
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities($this->catalog->clone_title));
			}

			$note->content.= sprintf(Store::_('<p>Enable this %1$s only if '.
				'you are done making %1$s changes, and you want to apply the '.
				'changes to the live website.</p>'), Store::_('catalog'));
		}

		$statuses = SwatDB::getOptionArray($this->app->db,
			'CatalogRegionBinding',	'available', 'region', null,
			sprintf('catalog = %s',
				$this->app->db->quote($this->id, 'integer')));

		$status_replicator = $this->ui->getWidget('status_replicator');
		foreach ($status_replicator->replicators as $region => $dummy) {
			$available = $status_replicator->getWidget(
				'available', $region)->value = $statuses[$region];
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$link = sprintf('Catalog/Details?id=%s', $this->id);

		$this->navbar->createEntry($this->catalog->title, $link);
		$this->navbar->createEntry(Store::_('Change Status'));
	}

	// }}}
}

?>
