<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StorePaymentType.php';

/**
 * Edit page for payment types
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $payment_type;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');
		$this->initPaymentType();

		$region_list = $this->ui->getWidget('regions');
		$region_list_options = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		$region_list->addOptionsByArray($region_list_options);

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
	}

	// }}}
	// {{{ protected function initPaymentType()

	protected function initPaymentType()
	{
		$class_name = SwatDBClassMap::get('StorePaymentType');
		$this->payment_type = new $class_name();
		$this->payment_type->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->payment_type->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_('Payment Type with an id "%s" not found'),
						$this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname');
		$title = $this->ui->getWidget('title');

		if ($this->id === null && $shortname->value === null) {
			$new_shortname = $this->generateShortname($title->value);
			$shortname->value = $new_shortname;
		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(Store::_(
				'Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$shortname->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$valid = true;

		$class_name = SwatDBClassMap::get('StorePaymentType');
		$payment_type = new $class_name();
		$payment_type->setDatabase($this->app->db);

		if ($payment_type->loadByShortname($shortname)) {
			if ($payment_type->id !== $this->payment_type->id)
				$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updatePaymentType();
		$this->payment_type->save();

		$region_list = $this->ui->getWidget('regions');
		SwatDB::updateBinding($this->app->db, 'PaymentTypeRegionBinding',
			'payment_type', $this->id, 'region', $region_list->values,
			'Region', 'id');

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updatePaymentType()

	protected function updatePaymentType()
	{
		$values = $this->ui->getValues(array('title', 'shortname'));

		$this->payment_type->title     = $values['title'];
		$this->payment_type->shortname = $values['shortname'];
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->payment_type));

		$region_list = $this->ui->getWidget('regions');
		$region_list->values = SwatDB::queryColumn($this->app->db,
			'PaymentTypeRegionBinding', 'region', 'payment_type',
			$this->id);
	}

	// }}}
}

?>
