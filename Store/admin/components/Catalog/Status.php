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
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCatalogStatus extends AdminDBEdit
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

		$status_flydown = $this->ui->getWidget('status');
		$status_options = array();

		$class_map = StoreClassMap::instance();
		$catalog_class = $class_map->resolveClass('StoreCatalog');

		foreach (call_user_func(array($catalog_class, 'getStatuses')) as 
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
		$sql = 'delete from CatalogRegionBinding
			where catalog in
				(select clone from CatalogCloneView where catalog = %s)';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$message->secondary_content = sprintf(Store::_(
			'“%s” has been automatically disabled in all regions.'),
			$this->catalog->clone_title);
	}

	// }}}
	// {{{ abstract protected function saveStatus()

	/**
	 * Each subclass must do its own saving of status
	 * 
	 * @return boolean true if a catalog has been enabled in any region.
	 */
	abstract protected function saveStatus();

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

		$status_replicator = $this->ui->getWidget('status_replicator');
		$sql = 'select region, available from CatalogRegionBinding
			where catalog = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'));

		$class_map = StoreClassMap::instance();
		$catalog_class = $class_map->resolveClass('StoreCatalog');

		$statuses = SwatDB::query($this->app->db, $sql);

		foreach ($statuses as $status) {
			$status_flydown = $status_replicator->getWidget('status',
				$status->region);

			$status_constant = call_user_func(array($catalog_class,
				'getStatusConstant'), $status->available);

			$status_flydown->value = $status_constant;
		}
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
