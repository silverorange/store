<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreItemMinimumQuantityGroup.php';

/**
 * Edit page for item minimum quantity groups
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemMinimumQuantityGroupEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $item_group;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');
		$this->initItemMinimumQuantityGroup();

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
	}

	// }}}
	// {{{ protected function initItemMinimumQuantityGroup()

	protected function initItemMinimumQuantityGroup()
	{
		$class_name = SwatDBClassMap::get('StoreItemMinimumQuantityGroup');
		$this->item_group = new $class_name();
		$this->item_group->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->item_group->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_('Item group with an id "%s" not found'),
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

		$class_name = SwatDBClassMap::get('StoreItemMinimumQuantityGroup');
		$item_group = new $class_name();
		$item_group->setDatabase($this->app->db);

		if ($item_group->loadByShortname($shortname)) {
			if ($item_group->id !== $this->item_group->id)
				$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateItemMinimumQuantityGroup();
		$this->item_group->save();

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'),
				$this->item_group->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateItemMinimumQuantityGroup()

	protected function updateItemMinimumQuantityGroup()
	{
		$values = $this->ui->getValues(array(
			'title', 'shortname', 'minimum_quantity'));

		$this->item_group->title            = $values['title'];
		$this->item_group->shortname        = $values['shortname'];
		$this->item_group->minimum_quantity = $values['minimum_quantity'];
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->item_group));
	}

	// }}}
}

?>
