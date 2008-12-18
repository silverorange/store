<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Store/dataobjects/StoreShippingType.php';

/**
 * Edit page for Shipping Rates
 *
 * @package   Store
 * @copyright 2008 silverorange
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

	protected $ui_xml = 'Store/admin/components/ShippingType/rate-edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initShippingRate();
		$this->ui->loadFromXML($this->ui_xml);
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

	// process phase
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
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateShippingRate();
		$this->shipping_rate->save();

		$message = new SwatMessage(Store::_('Shipping Rate has been saved.'));
		$this->app->messages->add($message);
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
		$this->ui->setValues(get_object_vars($this->shipping_rate));
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
