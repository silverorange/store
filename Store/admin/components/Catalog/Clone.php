<?php

/**
 * Clone tool for catalogs
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogClone extends AdminDBEdit
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->getUiXml());
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/clone.xml';
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData(): void
	{
		$title = $this->ui->getWidget('title')->value;

		$clone_id = SwatDB::executeStoredProcOne($this->app->db,
			'cloneCatalog', array(
				$this->app->db->quote($this->id, 'integer'),
				$this->app->db->quote($title, 'text')));

		if ($clone_id == -1) {
			$message = new SwatMessage(
				sprintf(Store::_('The %s “%s” could not be cloned.'),
				Store::_('catalog'), $title), 'system-error');

		} else {
			// add all new products to search queue
			$message = new SwatMessage(
				sprintf(Store::_('The %s “%s” has been cloned.'),
				Store::_('catalog'), $title));
		}

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$component_details = $this->ui->getWidget('original_details');

		$sql = 'select Catalog1.id, Catalog1.title,
					Catalog2.title as clone_title,
					Catalog1.clone_of as clone_id
				from Catalog as Catalog1
					left outer join Catalog as Catalog2
						on Catalog1.clone_of = Catalog2.id
				where Catalog1.id = %s';

		$sql = sprintf($sql, $this->app->db->quote($this->id, 'integer'));
		$row = SwatDB::queryRow($this->app->db, $sql);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('%s with id ‘%s’ not found.',
				Store::_('Catalog'), $this->id)));

		if ($row->clone_id !== null)
			throw new AdminNoAccessException(
				sprintf(Store::_('Cannot clone a %s that is already a clone.'),
					Store::_('catalog')));

		$component_details->data = $row;

		$status_renderer =
			$component_details->getField('status')->getRendererByPosition();

		$status_renderer->db = $this->app->db;
		$status_renderer->regions = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$link = sprintf('Catalog/Details?id=%s', $this->id);
		$sql = sprintf('select title from Catalog where id = %s', $this->id);
		$title = SwatDB::queryOne($this->app->db, $sql);
		$this->navbar->createEntry($title, $link);
		$this->navbar->createEntry(Store::_('Clone'));
	}

	// }}}
}

?>
