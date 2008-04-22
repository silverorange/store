<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for Locale
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreLocaleEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $locale;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$this->initLocale();

		$id_flydown = $this->ui->getWidget('region');
		$id_flydown->show_blank = false;
		$id_flydown->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title'));
	}

	// }}}
	// {{{ protected function initLocale()

	protected function initLocale()
	{
		$class_name = SwatDBClassMap::get('StoreLocale');
		$this->locale = new $class_name();
		$this->locale->setDatabase($this->app->db);
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$localeid = $this->ui->getWidget('id');

		if (!ereg('^[a-z][a-z]_[A-Z][A-Z]$', $localeid->value)) {
			$localeid->addMessage(new SwatMessage(
				Store::_('Invalid locale identifier.'), SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateLocale();
		$this->locale->save();

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $this->locale->id));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateLocale()

	protected function updateLocale()
	{
		$values = $this->ui->getValues(array('id', 'region'));

		$this->locale->id     = $values['id'];
		$this->locale->region = $values['region'];
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$message = new SwatMessage('Locales can not be edited.',
			SwatMessage::WARNING);

		$this->app->messages->add($message);
		$this->app->relocate('Locale');
	}

	// }}}
}
?>
