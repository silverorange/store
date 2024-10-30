<?php

/**
 * Edit page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogEdit extends AdminDBEdit
{
	protected StoreCatalog $catalog;

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->getUiXml());

		$this->initCatalog();
	}

	// }}}
	// {{{ protected function initCatalog()

	protected function initCatalog()
	{
		$class_name = SwatDBClassMap::get(StoreCatalog::class);
		$this->catalog = new $class_name();
		$this->catalog->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->catalog->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Store::_('Catalog with id “%s” not found.'),
						$this->id));
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData(): void
	{
		$this->updateCatalog();
		$this->catalog->save();

		$message = new SwatMessage(sprintf(
			Store::_('“%s” has been saved.'),
			$this->catalog->title));

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}
	// {{{ protected function updateCatalog()

	protected function updateCatalog()
	{
		$values = $this->ui->getValues(array(
			'title', 'in_season'));

		$this->catalog->title     = $values['title'];
		$this->catalog->in_season = $values['in_season'];
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues($this->catalog->getAttributes());
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->catalog->title !== null)
			$this->navbar->createEntry($this->catalog->title,
				sprintf('Catalog/Details?id=%s', $this->catalog->id));

		parent::buildNavBar();
	}

	// }}}
}

?>
