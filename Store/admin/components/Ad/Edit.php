<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for Ads
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdEdit extends AdminDBEdit
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
		$this->ui->loadFromXML(dirname(__FILE__).'/admin-ad-edit.xml');

		$this->fields = array('title', 'shortname');
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('title')->value, $this->id);
			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname, $this->id)) {
			$message = new SwatMessage(
				Store::_('Short name already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from Ad
				where shortname = %s and id %s %s';

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('title', 'shortname'));

		if ($this->id === null) {
			$this->fields[] = 'date:createdate';
			$date = new SwatDate();
			$date->toUTC();
			$values['createdate'] = $date->getDate();

			$this->id = SwatDB::insertRow($this->app->db, 'Ad', $this->fields,
				$values, 'iteger:id');

			// create ad locale bindings
			$sql = sprintf('insert into AdLocaleBinding (ad, locale)
				select %s, Locale.id as locale from Locale',
				$this->app->db->quote($this->id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		} else {
			SwatDB::updateRow($this->app->db, 'Ad', $this->fields, $values,
				'id', $this->id);
		}

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Ad', 
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Ad with id ‘%s’ not found.'), $this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}

?>
