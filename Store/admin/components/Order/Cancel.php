<?php

/**
 * Page to mark an order as cancelled
 *
 * @package   Store
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderCancel extends AdminConfirmation
{
	// {{{ protected properties

	protected $id;
	protected $order;

	/**
	 * If we came from an account page, this is the id of the account.
	 * Otherwise it is null.
	 *
	 * @var integer
	 */
	protected $account;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->loadFromXML('Admin/pages/confirmation.xml');

		$this->id = SiteApplication::initVar('id');
		$this->account = SiteApplication::initVar('account');

		$this->initOrder();
	}

	// }}}
	// {{{ protected function initOrder()

	protected function initOrder()
	{
		$order_class = SwatDBClassMap::get('StoreOrder');
		$this->order = new $order_class();

		$this->order->setDatabase($this->app->db);

		if (!$this->order->load($this->id)) {
			throw new AdminNotFoundException(sprintf(
				Store::_('An order with an id of ‘%d’ does not exist.'),
				$this->id));
		}

		$instance_id = $this->app->getInstanceId();
		if ($instance_id !== null) {
			$order_instance_id = ($this->order->instance === null) ?
				null : $this->order->instance->id;

			if ($order_instance_id !== $instance_id)
				throw new AdminNotFoundException(sprintf(Store::_(
					'Incorrect instance for order ‘%d’.'), $this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function processResponse()

	protected function processResponse()
	{
		$form = $this->ui->getWidget('confirmation_form');

		if ($form->button->id === 'yes_button') {
			$this->order->cancel_date = new SwatDate();
			$this->order->save();

			$this->app->messages->add(new SwatMessage(sprintf(
				Store::_('%s has been marked as canceled.'),
				$this->order->getTitle())));
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('account', $this->account);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $this->getConfirmationMessage();
		$message->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function getConfirmationMessage()

	protected function getConfirmationMessage()
	{
		$locale = SwatI18NLocale::get();

		ob_start();

		$content = Store::_('Are you sure you want to cancel %s?');
		$content = sprintf($content, $this->order->getTitle());

		$confirmation_title = new SwatHtmlTag('h3');
		$confirmation_title->setContent($content);
		$confirmation_title->display();

		$total = SwatString::minimizeEntities(
				$locale->formatCurrency($this->order->total));

		printf(Store::_('%s, which totals %s, will be marked as cancelled.'),
			$this->order->getTitle(), $total);

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->account === null) {
			$this->navbar->createEntry($this->order->getTitle(),
				sprintf('Order/Details?id=%s', $this->id));
		} else {
			// use account navbar
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Customer Accounts'), 'Account'));

			$this->navbar->addEntry(new SwatNavBarEntry(
				$this->order->account->fullname,
				'Account/Details?id='.$this->order->account));

			$this->title = $this->order->account->fullname;

			$this->navbar->createEntry($this->order->getTitle(),
				sprintf('Order/Details?id=%s&account=%s', $this->id,
				$this->account));
		}

		$this->navbar->createEntry(Store::_('Cancel Order'));
	}

	// }}}
}

?>
