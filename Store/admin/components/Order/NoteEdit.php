<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once '../../include/dataobjects/Order.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Edit page for admin notes on orders
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderNoteEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $order;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->loadFromXML(dirname(__FILE__).'/noteedit.xml');
		parent::initInternal();

		$this->order = new Order();
		$this->order->setDatabase($this->app->db);
		$this->order->load($this->id);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()
	protected function processInternal()
	{
		$edit_form = $this->ui->getWidget('edit_form');

		if ($edit_form->isSubmitted())
			$this->saveDBData();
	}

	// }}}
	// {{{ protected function savdDBData()
	protected function saveDBData()
	{
		$notes = $this->ui->getWidget('notes');
		$this->order->notes = $notes->value;
		$this->order->save();

		$message = new SwatMessage('The Admin Note has been saved.');
		$this->app->messages->add($message);
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
