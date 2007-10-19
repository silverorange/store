<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Edit page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCatalogEdit extends AdminDBEdit
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

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);

		$this->fields = array('title');

		$id = SiteApplication::initVar('id', null);

		$status_flydown = $this->ui->getWidget('status');
		$status_options = array();

		$catalog_class = SwatDBClassMap::get('StoreCatalog');

		foreach (call_user_func(array($catalog_class, 'getStatuses')) as
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
			$this->ui->getWidget('status_fieldset')->visible = false;
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

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ abstract protected function saveStatus()

	abstract protected function saveStatus();

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

		$this->loadStatus();
	}

	// }}}
	// {{{ abstract protected function loadStatus()

	abstract protected function loadStatus();

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
