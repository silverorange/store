<?php

require_once 'Admin/pages/AdminConfirmation.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreInvoice.php';

/**
 * Page to resend the notification email for an invoice
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceEmailNotification extends AdminConfirmation
{
	// {{{ protected properties

	protected $id;
	protected $invoice;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->loadFromXML('Admin/pages/confirmation.xml');

		$this->id = SiteApplication::initVar('id');

		$this->getInvoice();
	}

	// }}}

	// process phase
	// {{{ protected function processResponse()

	protected function processResponse()
	{
		$form = $this->ui->getWidget('confirmation_form');

		if ($form->button->id == 'yes_button') {
			$this->invoice->sendNotificationEmail($this->app);

			$message = new SwatMessage(sprintf(
				Store::_('A notification of “%s” has been emailed to %s.'),
				$this->getInvoiceTitle(), $this->invoice->account->email),
					SwatMessage::NOTIFICATION);

			$this->app->messages->add($message);
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

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $this->getConfirmationMessage();
		$message->content_type = 'text/xml';

		$this->ui->getWidget('yes_button')->title =
			Store::_('Send Notification');

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function getConfirmationMessage()

	protected function getConfirmationMessage()
	{
		ob_start();
		$notification_title = new SwatHtmlTag('h3');

		$notification_title->setContent(
			sprintf(Store::_('Are you sure you want to send the '.
				'invoice notification email for %s?'), 
				$this->getInvoiceTitle()));

		$notification_title->display();

		$email_anchor = new SwatHtmlTag('a');
		$email_anchor->href = sprintf('mailto:%s', $this->invoice->account->email);
		$email_anchor->setContent($this->invoice->account->email);

		printf(Store::_('A notification for “%s” will be sent to '),
			$this->getInvoiceTitle());

		$email_anchor->display();

		echo '.';

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function getInvoice()

	protected function getInvoice() 
	{
		if ($this->invoice === null) {
			$class_map = SwatDBClassMap::instance();
			$invoice_class = $class_map->resolveClass('StoreInvoice');
			$this->invoice = new $invoice_class();

			$this->invoice->setDatabase($this->app->db);

			if (!$this->invoice->load($this->id))
				throw new AdminNotFoundException(sprintf(
					Store::_('An invoice with an id of ‘%d’ does not exist.'),
					$this->id));

		}
		return $this->invoice;
	}

	// }}}
	// {{{ protected function getInvoiceTitle()

	protected function getInvoiceTitle() 
	{
		return sprintf(Store::_('Invoice %s'), $this->invoice->id);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar() 
	{
		$account = $this->invoice->account;

		$fullname = $account->fullname;

		$last_entry = $this->navbar->popEntry();

		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($fullname,
			'Account/Details?id='.$account->id));

		$this->navbar->addEntry(new SwatNavBarEntry(
			sprintf(Store::_('Invoice %s'), $this->id),
			sprintf('Invoice/Details?id=%s', $this->id)));

		$this->navbar->addEntry($last_entry);
	}

	// }}}
}

?>
