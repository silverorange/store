<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for ProvStates
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreProvStateEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/ProvState/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInteral()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);

		$this->fields = array('title', 'abbreviation', 'country');
		
		$country_flydown = $this->ui->getWidget('country');
		$country_flydown->show_blank = false;
		$country_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Country', 'text:title', 'integer:id', 'title'));
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();

		if ($this->id === null)
			SwatDB::insertRow($this->app->db, 'ProvState', $this->fields,
				$values);
		else
			SwatDB::updateRow($this->app->db, 'ProvState', $this->fields, 
				$values, 'id', $this->id);

		$msg = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('title', 'abbreviation', 'country'));
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'ProvState', 
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Province/State with id ‘%s’ not found.'), 
					$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}

?>
