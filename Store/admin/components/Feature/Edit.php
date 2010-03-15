<?php

require_once 'Admin/AdminUI.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreFeature.php';

/**
 * Edit page for Features
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeatureEdit extends AdminDBEdit
{
	// {{{ private properties

	/**
	 * @var StoreFeature
	 */
	private $feature;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initFeature();

		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');
	}

	// }}}
	// {{{ private function initFeature()

	private function initFeature()
	{
		$class_name = SwatDBClassMap::get('StoreFeature');
		$this->feature = new $class_name();
		$this->feature->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->feature->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_(
						'Feature with id ‘%s’ not found.'),
						$this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function updateFeature()

	protected function updateFeature()
	{
		$values = $this->ui->getValues(array(
			'shortname',
			'title',
			'description',
			'link',
			'enabled',
			'start_date',
			'end_date',
			'display_slot',
		));

		if ($values['start_date'] !== null) {
			$values['start_date']->setTZ($this->app->default_time_zone);
			$values['start_date']->toUTC();
		}

		if ($values['end_date'] !== null) {
			$values['end_date']->setTZ($this->app->default_time_zone);
			$values['end_date']->toUTC();
		}

		$this->feature->shortname    = $values['shortname'];
		$this->feature->title        = $values['title'];
		$this->feature->description  = $values['description'];
		$this->feature->link         = $values['link'];
		$this->feature->enabled      = $values['enabled'];
		$this->feature->start_date   = $values['start_date'];
		$this->feature->end_date     = $values['end_date'];
		$this->feature->display_slot = $values['display_slot'];
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateFeature();
		$this->feature->save();

		$message = new SwatMessage(sprintf(
			Store::_('Feature “%s” has been saved.'),
			$this->feature->title));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_frame');
		$form->subtitle = $this->feature->title;

		$note = sprintf('%s/%s',
			$this->app->default_time_zone->getShortName(),
			$this->app->default_time_zone->getDSTShortName());

		$this->ui->getWidget('start_date_field')->note = $note;
		$this->ui->getWidget('end_date_field')->note = $note;
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->feature));

		$start_date = $this->ui->getWidget('start_date');
		$end_date = $this->ui->getWidget('end_date');

		if ($start_date->value !== null)
			$start_date->value->convertTZ($this->app->default_time_zone);

		if ($end_date->value !== null)
			$end_date->value->convertTZ($this->app->default_time_zone);
	}

	// }}}
}

?>
