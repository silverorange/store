<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Store/dataobjects/StorePriceRange.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for PriceRanges
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRangeEdit extends AdminDBEdit
{
	// {{{ protected properties

	/*
	 * @var StorePriceRange
	 */
	protected $price_range;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/PriceRange/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInteral()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initPriceRange();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ protected function initPriceRange()

	protected function initPriceRange()
	{
		$class_name = SwatDBClassMap::get('StorePriceRange');
		$this->price_range = new $class_name();
		$this->price_range->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->price_range->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Admin::_('Price range with an id "%s"'.
						' not found'), $this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$start_price = floor($this->ui->getWidget('start_price')->value);
		$end_price = floor($this->ui->getWidget('end_price')->value);
		if ($start_price > $end_price) {
			$this->ui->getWidget('end_price')->addMessage(new SwatMessage(
				Store::_('End Price must be greater than start price.'),
					SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updatePriceRange();
		$this->price_range->save();

		$message = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$this->price_range->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updatePriceRange()

	protected function updatePriceRange()
	{
		$values = $this->ui->getValues(array(
			'start_price',
			'end_price',
			'original_price'
		));

		$this->price_range->start_price    = floor($values['start_price']);
		$this->price_range->end_price      = floor($values['end_price']);
		$this->price_range->original_price = $values['original_price'];
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->price_range));
	}

	// }}}
}

?>
