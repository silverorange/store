<?php


/**
 * Edit page for Features
 *
 * @package   Store
 * @copyright 2010-2016 silverorange
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

		$this->ui->loadFromXML(__DIR__.'/edit.xml');

		$region_flydown = $this->ui->getWidget('region');
		$region_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Region', 'title', 'id', 'title'));

		// only show instances if we're not on an instance, and at least
		// one instance exists
		$instance_id = $this->app->getInstanceId();
		if ($instance_id === null) {
			$instances = SwatDB::getOptionArray($this->app->db, 'Instance',
				'title', 'id', 'title');

			$this->ui->getWidget('instance')->addOptionsByArray($instances);
			$this->ui->getWidget('instance_field')->visible =
				(count($instances) != 0);
		}
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
	// {{{ protected function validate()

	protected function validate()
	{
		$valid = parent::validate();

		$start_date = $this->ui->getWidget('start_date')->value;
		$end_date   = $this->ui->getWidget('end_date')->value;

		if ($start_date !== null && $end_date !== null &&
			SwatDate::compare($start_date, $end_date) > 0) {
			$valid = false;
			$message = new SwatMessage(
				'The dates entered are not a valid set of dates. '.
				'The date entered in the <strong>Start Date</strong> '.
				'field must occur before the date entered in the '.
				'<strong>End Date</strong> field.',
				'error');

			$message->content_type = 'text/xml';

			$this->ui->getWidget('start_date')->addMessage($message);
			// massage the SwatFormFields so that the message displays on both
			// controls.
			$this->ui->getWidget('date_span_field')->display_messages  = true;
			$this->ui->getWidget('start_date_field')->display_messages = false;
			$this->ui->getWidget('end_date_field')->display_messages   = false;
		}

		return $valid;
	}

	// }}}
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
			'region',
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
		$this->feature->region       = $values['region'];

		$instance_id = $this->app->getInstanceId();
		$instance_widget = $this->ui->getWidget('instance');
		if ($instance_id === null && $instance_widget->value !== null) {
			$this->feature->instance = $instance_widget->value;
		} else {
			$this->feature->instance = $instance_id;
		}
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

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('StoreFeature');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_frame');
		$form->subtitle = $this->feature->title;

		$abbreviations = SwatDate::getTimeZoneAbbreviation(
			$this->app->default_time_zone);

		$note = sprintf('%s%s',
			$abbreviations['st'],
			array_key_exists('dt', $abbreviations) ?
				'/'.$abbreviations['dt'] : '');

		$this->ui->getWidget('start_date_field')->note = $note;
		$this->ui->getWidget('end_date_field')->note = $note;
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$values = $this->feature->getAttributes();
		$values['region'] = $this->feature->getInternalValue('region');
		$this->ui->setValues($values);

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
