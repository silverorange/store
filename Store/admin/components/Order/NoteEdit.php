<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Edit page for notes on orders
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderNoteEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var StoreOrder
	 */
	protected $order;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/note-edit.xml');

		// initialize order object
		$class_name = SwatDBClassMap::get('StoreOrder');
		$this->order = $this->getOrder();
	}

	// }}}
	// {{{ protected function getOrder()

	protected function getOrder()
	{
		if ($this->order === null) {
			$order_class = SwatDBClassMap::get('StoreOrder');
			$this->order = new $order_class();

			$this->order->setDatabase($this->app->db);

			if (!$this->order->load($this->id))
				throw new AdminNotFoundException(sprintf(
					Store::_('An order with an id of ‘%d’ does not exist.'),
					$this->id));
			elseif ($this->app->hasModule('SiteMultipleInstanceModule') &&
				$this->order->instance != $this->app->instance->getInstance())
				throw new AdminNotFoundException(sprintf(
					Store::_('Incorrect instance for order ‘%d’.'),
						$this->id));

		}
		return $this->order;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$notes = $this->ui->getWidget('notes');
		$this->order->notes = $notes->value;
		$this->order->save();

		$this->app->messages->add($this->getSaveMessage());
	}

	// }}}
	// {{{ protected function getSaveMessage()

	protected function getSaveMessage()
	{
		$message = new SwatMessage(Store::_('Note has been saved.'));

		return $message;
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$notes = $this->ui->getWidget('notes');
		$notes->value = $this->order->notes;
	}

	// }}}
}

?>
