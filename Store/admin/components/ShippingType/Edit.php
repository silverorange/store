<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Store/dataobjects/StoreShippingType.php';

/**
 * Edit page for Shipping Types
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/ShippingType/edit.xml';

	/**
	 * @var VanBourgondienShippingType
	 */
	protected $shipping_type;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initShippingType();

		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ private function initShippingType()

	private function initShippingType()
	{
		$class_name = SwatDBClassMap::get('StoreShippingType');
		$this->shipping_type = new $class_name();
		$this->shipping_type->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->shipping_type->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Store::_(
						'Shipping Type with id ‘%s’ not found.'),$this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function updateShippingType()

	protected function updateShippingType()
	{
		$values = $this->ui->getValues(array(
			'title',
			'shortname',
			'note',
		));

		$this->shipping_type->title     = $values['title'];
		$this->shipping_type->shortname = $values['shortname'];
		$this->shipping_type->note      = $values['note'];
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateShippingType();
		$this->shipping_type->save();

		$message = new SwatMessage(sprintf(
			Store::_('Shipping Type “%s” has been saved.'),
			$this->shipping_type->title));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$final_entry = $this->navbar->popEntry();

		if ($this->id !== null) {
			$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Details'),
				sprintf('ShippingType/Details?id=%s', $this->id)));
		}

		$this->navbar->addEntry($final_entry);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->shipping_type));
	}

	// }}}
}

?>
