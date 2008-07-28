<?php

require_once 'Swat/SwatMessage.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Store/dataobjects/StoreCatalog.php';

/**
 * Change status page for catalogs
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
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

	/**
	 * @var StoreCatalog
	 */
	private $catalog;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->initCatalog();

		$region_list = $this->ui->getWidget('regions');
		$region_list_options = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		$region_list->addOptionsByArray($region_list_options);
	}

	// }}}
	// {{{ protected function initCatalog()

	protected function initCatalog()
	{
		$class_name = SwatDBClassMap::get('StoreCatalog');
		$this->catalog = new $class_name();
		$this->catalog->setDatabase($this->app->db);

		if ($this->id === null) {
			throw new AdminNotFoundException(
				Store::_('Catalog id is required to edit status.'));
		}

		if (!$this->catalog->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf(Store::_('Catalog with an id "%s" not found'),
					$this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$region_list = $this->ui->getWidget('regions');
		SwatDB::updateBinding($this->app->db, 'CatalogRegionBinding',
			'catalog', $this->catalog->id, 'region', $region_list->values,
			'Region', 'id');

		$message = new SwatMessage(
			sprintf(Store::_('The status of “%s” has been updated.'),
				$this->catalog->title));

		// disable clone
		if (count($region_list->values) > 0 && $this->catalog->clone !== null) {
			$sql = sprintf('delete from CatalogRegionBinding
				where catalog = %s',
				$this->app->db->quote($this->catalog->clone->id, 'integer'));

			SwatDB::exec($this->app->db, $sql);

			$message->secondary_content = sprintf(Store::_(
				'“%s” has been automatically disabled in all regions.'),
				$this->catalog->clone->title);
		}

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		if ($this->catalog->clone !== null) {
			$note = $this->ui->getWidget('clone_note');
			$note->visible = true;
			$note->content_type = 'text/xml';
			if ($this->catalog->clone_of === null) {
				$note->title = Store::_('Catalog has a Clone');
				$note->content = sprintf(Store::_(
					'<p>The catalog <strong>%1$s</strong> has a clone. '.
					'Enabling the catalog <strong>%1$s</strong> in any '.
					'region will disable the clone catalog '.
					'<strong>%2$s</strong> in all regions.</p>'),
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities(
						$this->catalog->clone->title));
			} else {
				$note->title = Store::_('Catalog is a Clone');
				$note->content = sprintf(Store::_(
					'<p>The catalog <strong>%1$s</strong> is a cloned '.
					'catalog. Enabling the catalog <strong>%1$s</strong> '.
					'in any region will disable the parent catalog '.
					'<strong>%2$s</strong> in all regions.</p>'),
					SwatString::minimizeEntities($this->catalog->title),
					SwatString::minimizeEntities(
						$this->catalog->clone->title));
			}

			$note->content.= sprintf(Store::_('<p>Only enable this catalog '.
				'if you are done making catalog changes, and want to apply '.
				'the changes in <strong>%s</strong> to the live website.</p>'),
				$this->catalog->title);
		}

		// load region bindings
		$region_list = $this->ui->getWidget('regions');
		$region_list->values = SwatDB::queryColumn($this->app->db,
			'CatalogRegionBinding', 'region', 'catalog',
			$this->catalog->id);
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		$this->ui->getWidget('edit_frame')->subtitle = $this->catalog->title;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->createEntry($this->catalog->title,
			sprintf('Catalog/Details?id=%s', $this->catalog->id));

		$this->navbar->createEntry(Store::_('Change Catalog Status'));
	}

	// }}}
}

?>
