<?php

require_once 'Admin/AdminUI.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreAttributeType.php';

/**
 * Edit page for Attributes
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeTypeEdit extends AdminDBEdit
{
	// {{{ private properties

	/**
	 * @var StoreAttributeType
	 */
	private $attribute_type;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initAttributeType();

		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');
	}

	// }}}
	// {{{ protected function initAttributeType()

	protected function initAttributeType()
	{
		$class_name = SwatDBClassMap::get('StoreAttributeType');
		$this->attribute_type = new $class_name();
		$this->attribute_type->setDatabase($this->app->db);

		if ($this->id != null) {
			if (!$this->attribute_type->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_('Attribute Type with id ‘%s’ not found.'),
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

		$class_name = SwatDBClassMap::get('StoreAttributeType');
		$attribute_type = new $class_name();
		$attribute_type->setDatabase($this->app->db);

		if ($attribute_type->loadFromShortname($shortname->value)) {
			if ($attribute_type->id !== $this->attribute_type->id) {
				$message = new SwatMessage(
					Store::_('Shortname already exists and must be unique.'));

				$shortname->addMessage($message);
			}
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateAttributeType();
		$this->attribute_type->save();

		$message = new SwatMessage(
			sprintf(Store::_('Attribute Type “%s” has been saved.'),
				$this->attribute_type->shortname));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateAttributeType()

	protected function updateAttributeType()
	{
		$values = $this->ui->getValues(array(
			'shortname',
		));

		$this->attribute_type->shortname = $values['shortname'];
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_frame');
		$form->subtitle = $this->attribute_type->shortname;
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->attribute_type));
	}

	// }}}
}

?>
