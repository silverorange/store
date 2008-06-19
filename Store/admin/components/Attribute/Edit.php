<?php

require_once 'Admin/AdminUI.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreAttribute.php';

/**
 * Edit page for Attribute Types
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeEdit extends AdminDBEdit
{
	// {{{ private properties

	/**
	 * @var StoreAttributeType
	 */
	private $attribute;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initAttribute();

		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$attribute_type_flydown = $this->ui->getWidget('attribute_type');
		$attribute_type_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'AttributeType', 'shortname', 'id', 'shortname'));
	}

	// }}}
	// {{{ protected function initAttribute()

	protected function initAttribute()
	{
		$class_name = SwatDBClassMap::get('StoreAttribute');
		$this->attribute = new $class_name();
		$this->attribute->setDatabase($this->app->db);

		if ($this->id != null) {
			if (!$this->attribute->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_('Attribute with id ‘%s’ not found.'),
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

		$class_name = SwatDBClassMap::get('StoreAttribute');
		$attribute = new $class_name();
		$attribute->setDatabase($this->app->db);

		if ($attribute->loadFromShortname($shortname->value)) {
			if ($attribute->id !== $this->attribute->id) {
				$message = new SwatMessage(
					Admin::_('Shortname already exists and must be unique.'));

				$shortname->addMessage($message);
			}
		}
	}

	// }}}

	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateAttribute();
		$this->attribute->save();

		$message = new SwatMessage(
			sprintf(Store::_('Attribute “%s” has been saved.'),
				$this->attribute_type->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateAttribute()

	protected function updateAttribute()
	{
		$values = $this->ui->getValues(array(
			'shortname',
			'title',
			'attribute_type',
		));

		$this->attribute->shortname      = $values['shortname'];
		$this->attribute->title          = $values['title'];
		$this->attribute->attribute_type = $values['attribute_type'];
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_frame');
		$form->subtitle = $this->attribute->title;
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->attribute));
		$this->ui->getWidget('attribute_type')->value =
			$this->attribute->getInternalValue('attribute_type');
	}

	// }}}
}

?>
