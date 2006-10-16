<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for Accounts
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */

class AccountEdit extends AdminDBEdit
{
	// {{{ private properties

	private $fields;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$this->fields = array('fullname', 'email', 'integer:veseys_number', 
			'phone');
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('fullname', 'email', 
			'veseys_number', 'phone'));
		
		SwatDB::updateRow($this->app->db, 'Account', $this->fields, $values,
			'id', $this->id);

		$msg = new SwatMessage(
			sprintf('Account &#8220;%s&#8221; has been saved.', 
				$values['fullname']));

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		if ($this->id !== null) {
			$email = $this->ui->getWidget('email');
			if ($email->hasMessage())
				return;
	
			$query = SwatDB::query($this->app->db, sprintf('select email
				from Account where lower(email) = lower(%s) and id %s %s',
				$this->app->db->quote($email->value, 'text'),
				SwatDB::equalityOperator($this->id, true),
				$this->app->db->quote($this->id, 'integer')));
	
			if (count($query) > 0) {
				$message = new SwatMessage(
					'An account already exists with this email address.',
					SwatMessage::ERROR);
	
				$email->addMessage($message);
			}
		}
	}

	// }}}

	// build phase
	// {{{ private function buildNavBar()

	protected function buildNavBar() 
	{
		$account_fullname = SwatDB::queryOneFromTable($this->app->db, 
			'Account', 'text:fullname', 'id', $this->id);

		$this->navbar->addEntry(new SwatNavBarEntry($account_fullname, 
			sprintf('Account/Details?id=%s', $this->id)));
		$this->navbar->addEntry(new SwatNavBarEntry('Edit'));
		$this->title = $account_fullname;
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Account', 
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf("Account with id '%s' not found.", $this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}

?>
