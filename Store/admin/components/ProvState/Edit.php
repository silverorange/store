<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for ProvStates
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProvStateEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;
	protected $prov_state;

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
		$this->initProvState();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);

		$this->fields = array('title', 'abbreviation', 'country');

		$country_flydown = $this->ui->getWidget('country');
		$country_flydown->show_blank = false;
		$country_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Country', 'text:title', 'integer:id', 'title'));
	}

	// }}}
	// {{{ protected function initProvState()

	protected function initProvState()
	{
		$class_name = SwatDBClassMap::get('StoreProvState');
		$this->prov_state = new $class_name();
		$this->prov_state->setDatabase($this->app->db);

		if (!$this->id !== null) {
			if (!$this->prov_state->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Admin::_('Province/State with an id "%s"'.
						' not found'), $this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();
		$this->prov_state->title        = $values['title'];
		$this->prov_state->abbreviation = $values['abbreviation'];
		$this->prov_state->country      = $values['country'];
		$this->prov_state->save();

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($message);
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
		$this->ui->setValues(get_object_vars($this->prov_state));
	}

	// }}}
}

?>
