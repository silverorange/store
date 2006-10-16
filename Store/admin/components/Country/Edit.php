<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for Countries
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCountryEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		if ($this->id === null) {
			$this->fields = array('title', 'id');
		} else {
			$this->fields = array('title');
			$this->ui->getWidget('id_edit')->required = false;
			$this->ui->getWidget('id_edit')->visible = false;
			$this->ui->getWidget('id_non_edit')->visible = true;
			$this->ui->getWidget('id_non_edit')->content = $this->id;
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		if ($this->id === null) {
			$values = array(
				'title' => $this->ui->getWidget('title')->getState(),
				'id' => $this->ui->getWidget('id_edit')->getState());

			SwatDB::insertRow($this->app->db, 'Country', $this->fields, 
				$values);

			$this->id = $values['id'];
		} else {
			$values = $this->ui->getValues(array('title'));
			SwatDB::updateRow($this->app->db, 'Country', $this->fields, 
				$values, 'text:id', $this->id);
		}

		$msg = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		// validate country id
		if ($this->id === null) {
			$id = $this->ui->getWidget('id_edit')->getState();
			$sql = sprintf('select count(id) from Country where id = %s',
				$this->app->db->quote($id, 'text'));

			$count = SwatDB::queryOne($this->app->db, $sql);

			if ($count > 0) {
				$message = new SwatMessage(
					Store::_('<strong>Country Code</strong> already exists. '.
					'Country code must be unique for each country.'),
					SwatMessage::ERROR);

				$message->content_type = 'text/xml';
				$this->ui->getWidget('id_edit')->addMessage($message);
			}
		}
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Country', 
			$this->fields, 'text:id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Country with id ‘%s’ not found.'), 
					$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}
?>
