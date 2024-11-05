<?php

/**
 * Edit page for Shipping Rates
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeRateEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * The id of the parent shipping type
	 *
	 * @var integer
	 */
	protected $parent;

	/**
	 * The shipping rate we are editing
	 *
	 * @var StoreShippingRate
	 */
	protected $shipping_rate;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initShippingRate();
		$this->ui->loadFromXML($this->getUiXml());
		$this->parent = SiteApplication::initVar('parent');

		$this->ui->getWidget('region')->addOptionsByArray(
			SwatDB::getOptionArray($this->app->db, 'Region', 'title', 'id'));
	}

	// }}}
	// {{{ private function initShippingRate()

	private function initShippingRate()
	{
		$class_name = SwatDBClassMap::get('StoreShippingRate');

		$this->shipping_rate = new $class_name();
		$this->shipping_rate->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->shipping_rate->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_('Shipping Rate with id ‘%s’ not found.'),
						$this->id));
			}
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/rate-edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate(): void
	{
		parent::validate();

		if ($this->ui->getWidget('amount')->value == null &&
			$this->ui->getWidget('percentage')->value == null) {
			$message = new SwatMessage(sprintf(Store::_('Either an '.
				'%3$%1$s%4$s or a %3$s%2$s%4$s is required.'),
				$this->ui->getWidget('amount')->parent->title,
				$this->ui->getWidget('percentage')->parent->title,
				'<strong>', '</strong>'),
				'error');

			$message->content_type = 'text/xml';

			$this->ui->getWidget('amount')->addMessage($message);
			$this->ui->getWidget('percentage')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData(): void
	{
		$this->updateShippingRate();
		$this->shipping_rate->save();

		$message = new SwatMessage(Store::_('Shipping Rate has been saved.'));
		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateShippingRate()

	protected function updateShippingRate()
	{
		$values = $this->ui->getValues(array(
			'region',
			'threshold',
			'amount',
			'percentage',
		));

		$this->shipping_rate->region     = $values['region'];
		$this->shipping_rate->threshold  = $values['threshold'];
		$this->shipping_rate->percentage = $values['percentage'];
		$this->shipping_rate->amount     = $values['amount'];

		if ($this->parent !== null)
			$this->shipping_rate->shipping_type = $this->parent;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('parent', $this->parent);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$final_entry = $this->navbar->popEntry();
		$parent_id = ($this->parent !== null) ? $this->parent :
			$this->shipping_rate->getInternalValue('shipping_type');

		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Shipping Type Details'),
			sprintf('ShippingType/Details?id=%s', $parent_id)));

		$this->navbar->addEntry($final_entry);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues($this->shipping_rate->getAttributes());
		$this->ui->getWidget('region')->value =
			$this->shipping_rate->getInternalValue('region');

		$this->setZeros();
	}

	// }}}
	// {{{ protected function setZeros()

	protected function setZeros()
	{
		foreach($this->getWidgetIds() as $widget_id) {
			$widget = $this->ui->getWidget($widget_id);
			if ($widget->value === null)
				$widget->value = 0;
		}
	}

	// }}}
	// {{{ protected function getWidgetIds()

	protected function getWidgetIds()
	{
		return array('amount', 'threshold', 'percentage');
	}

	// }}}
}

?>
