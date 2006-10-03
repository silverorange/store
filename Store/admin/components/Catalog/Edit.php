<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'Store/StoreClassMap.php';

/**
 * Edit page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCatalogEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Catalog/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->fields = array('title');

		$id = SiteApplication::initVar('id', null);

		$status_flydown = $this->ui->getWidget('status');
		$status_options = array();

		$class_map = StoreClassMap::instance();
		$catalog = $class_map->resolveClass('StoreCatalog');

		foreach (call_user_func(array($catalog, 'getStatuses')) as 
			$id => $title)
				$status_options[] = new SwatOption($id, $title);

		$status_flydown->options = $status_options;

		$status_replicator = $this->ui->getWidget('status_replicator');

		$id = SiteApplication::initVar('id', null);
		if ($id === null) {
			$status_replicator->replicators =
				SwatDB::getOptionArray($this->app->db, 'Region', 'title', 'id',
					'title');
		} else {
			$status_replicator->visible = false;
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('title'));

		if ($this->id === null)
			$this->id = SwatDB::insertRow($this->app->db, 'Catalog',
				$this->fields, $values, 'id');
		else
			SwatDB::updateRow($this->app->db, 'Catalog', $this->fields, $values,
				'id', $this->id);

		$this->saveStatus();

		$msg = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ private function saveStatus()

	private function saveStatus()
	{
		$status_replicator = $this->ui->getWidget('status_replicator');
		if ($status_replicator->visible) {

			$regions = array();
			$available_regions = array();
			$unavailable_regions = array();

			foreach ($status_replicator->replicators as $region => $dummy) {
				$status_flydown =
					$status_replicator->getWidget('status', $region);

				if ($status_flydown->value != Catalog::STATUS_DISABLED)
					$regions[] = $region;

				if ($status_flydown->value == Catalog::STATUS_ENABLED_IN_SEASON)
					$available_regions[] = $region;

				if ($status_flydown->value == 
					Catalog::STATUS_ENABLED_OUT_OF_SEASON)
						$unavailable_regions[] = $region;
			}

			SwatDB::updateBinding($this->app->db, 'CatalogRegionBinding',
				'catalog', $this->id, 'region', $regions, 'Region', 'id');

			$where_clause = sprintf('catalog = %s',
				$this->app->db->quote($this->id, 'integer'));

			SwatDB::updateColumn($this->app->db, 'CatalogRegionBinding',
				'boolean:available', true, 'region', $available_regions,
				$where_clause);

			SwatDB::updateColumn($this->app->db, 'CatalogRegionBinding',
				'boolean:available', false, 'region', $unavailable_regions,
				$where_clause);
		}
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Catalog',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('%s with id ‘%s’ not found.'),
				Store::_('Catalog'), $this->id));

		$this->ui->setValues(get_object_vars($row));

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
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$sql = sprintf('select title from Catalog where id = %s',
			$this->app->db->quote($this->id, 'integer'));

		$title = SwatDB::queryOne($this->app->db, $sql);
		if ($title !== null) {
			$link = sprintf('Catalog/Details?id=%s', $this->id);
			$this->navbar->createEntry($title, $link);
		}

		parent::buildNavBar();
	}

	// }}}
}

?>
