<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for payment types
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeEdit extends AdminDBEdit
{
	// {{{ private properties

	private $fields;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$this->fields = array('title', 'shortname');

		$region_list = $this->ui->getWidget('regions');
		$region_list->options = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
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

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(Store::_(
				'Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from PaymentType where shortname = %s
			and id %s %s';

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

		if ($this->id === null)
			$this->id = SwatDB::insertRow($this->app->db, 'PaymentType',
				$this->fields, $values, 'integer:id');
		else
			SwatDB::updateRow($this->app->db, 'PaymentType', $this->fields,
				$values, 'id', $this->id);

		$region_list = $this->ui->getWidget('regions');
		print_r($region_list->values); exit;

		SwatDB::updateBinding($this->app->db, 'PaymentTypeRegionBinding',
			'payment_type', $this->id, 'region', $region_list->values,
			'Region', 'id');

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'PaymentType',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf('Payment Type with id ‘%s’ not found.', $this->id));

		$this->ui->setValues(get_object_vars($row));

		$region_list = $this->ui->getWidget('regions');
		$region_list->values = SwatDB::queryColumn($this->app->db,
			'PaymentTypeRegionBinding', 'region', 'payment_type',
			$this->id);
	}

	// }}}
}

?>
