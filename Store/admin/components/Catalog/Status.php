<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'Store/StoreClassMap.php';

/**
 * Change status page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
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
			throw new AdminNotFoundException(
				sprintf("Catalog with id '%s' not found.", $this->id));

		$this->ui->loadFromXML($this->ui_xml);

		$status_flydown = $this->ui->getWidget('status');
		$status_options = array();

		$class_map = StoreClassMap::instance();
		$catalog = $class_map->resolveClass('StoreCatalog');

		foreach (call_user_func(array($catalog, 'getStatuses')) as 
			$id => $title)
				$status_options[] = new SwatOption($id, $title);

		$status_flydown->options = $status_options;

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
		$enabled = false;
		$regions = array();
		$available_regions = array();
		$unavailable_regions = array();

		$status_replicator = $this->ui->getWidget('status_replicator');
		foreach ($status_replicator->replicators as $region => $dummy) {
			$status_flydown = $status_replicator->getWidget('status', $region);

			if ($status_flydown->value != Catalog::STATUS_DISABLED) {
				$regions[] = $region;
				$enabled = true;
			}

			if ($status_flydown->value == Catalog::STATUS_ENABLED_IN_SEASON)
				$available_regions[] = $region;

			if ($status_flydown->value == Catalog::STATUS_ENABLED_OUT_OF_SEASON)
				$unavailable_regions[] = $region;
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

		$msg = new SwatMessage(
			sprintf('The status of “%s” has been updated.', $this->catalog->title));

		// disable clone
		if ($enabled && $this->catalog->clone !== null) {
			$sql = 'update Promotion set catalog = %s where catalog =
				(select clone from CatalogCloneView where catalog = %s)';

			$sql = sprintf($sql,
				$this->app->db->quote($this->id, 'integer'),
				$this->app->db->quote($this->id, 'integer'));

			SwatDB::exec($this->app->db, $sql);

			$sql = 'delete from CatalogRegionBinding
				where catalog in
					(select clone from CatalogCloneView where catalog = %s)';

			$sql = sprintf($sql,
				$this->app->db->quote($this->id, 'integer'));

			SwatDB::exec($this->app->db, $sql);

			$msg->secondary_content =
				sprintf('“%s” has been automatically disabled in all regions.',
					$this->catalog->clone_title);
		}

		$this->app->messages->add($msg);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		if ($this->catalog->clone !== null) {
			$note = $this->ui->getWidget('clone_note');
			$note->visible = true;
			$note->title = 'Warning';
			$note->content_type = 'text/xml';
			if ($this->catalog->is_parent) {
				$note->content = sprintf(
					'<p>The catalogue <strong>%s</strong> has a clone. Enabling '.
					'the catalogue <strong>%s</strong> in any region will '.
					'disable the catalogue <strong>%s</strong> in all '.
					'regions.</p>',
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities($this->catalog->clone_title));
			} else {
				$note->content = sprintf(
					'<p>The catalogue <strong>%s</strong> is a cloned '.
					'catalogue. Enabling the catalogue <strong>%s</strong> '.
					'in any region will disable the catalogue '.
					'<strong>%s</strong> in all regions.',
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities($this->catalog->clone_title));
			}

			$note->content.=
				'<p>Enable this catalogue only if you are done making '.
				'catalogue changes, and you want to apply the changes to the '.
				'live website.</p>';
		}

		$status_replicator = $this->ui->getWidget('status_replicator');
		$sql = 'select region, available from CatalogRegionBinding
			where catalog = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'));

		$statuses = SwatDB::query($this->app->db, $sql);

		foreach ($statuses as $status) {
			$status_flydown = $status_replicator->getWidget('status',
				$status->region);

			if ($status->available)
				$status_flydown->value = Catalog::STATUS_ENABLED_IN_SEASON;
			else
				$status_flydown->value = Catalog::STATUS_ENABLED_OUT_OF_SEASON;
		}
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame2()
	{
		// don't do any frame title stuff
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$sql = sprintf('select title from Catalog where id = %s',
			$this->app->db->quote($this->id, 'integer'));

		$title = SwatDB::queryOne($this->app->db, $sql);
		$link = sprintf('Catalog/Details?id=%s', $this->id);

		$this->navbar->createEntry($title, $link);
		$this->navbar->createEntry(Store::_('Change Status'));
	}

	// }}}
}

?>
