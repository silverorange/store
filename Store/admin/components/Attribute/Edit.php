<?php

/**
 * Edit page for Attributes
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = __DIR__.'/edit.xml';

	/**
	 * @var StoreAttribute
	 */
	protected $attribute;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initAttribute();

		$this->ui->loadFromXML($this->ui_xml);

		$attribute_type_flydown = $this->ui->getWidget('attribute_type');
		$attribute_type_flydown->addOptionsByArray(
			SwatDB::getOptionArray(
				$this->app->db,
				'AttributeType',
				'shortname',
				'id',
				'shortname'
			)
		);
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
					sprintf(
						Store::_('Attribute with id ‘%s’ not found.'),
						$this->id
					)
				);
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
					Store::_('Shortname already exists and must be unique.')
				);
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
			sprintf(
				Store::_('Attribute “%s” has been saved.'),
				$this->attribute->title
			)
		);

		$this->app->messages->add($message);

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('product');
		}
	}

	// }}}
	// {{{ protected function updateAttribute()

	protected function updateAttribute()
	{
		$values = $this->ui->getValues(
			array(
				'shortname',
				'title',
				'attribute_type',
			)
		);

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
		$this->ui->setValues($this->attribute->getAttributes());
		$this->ui->getWidget('attribute_type')->value =
			$this->attribute->getInternalValue('attribute_type');
	}

	// }}}
}

?>
