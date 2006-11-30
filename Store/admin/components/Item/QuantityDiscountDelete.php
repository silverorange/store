<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for Quantity Discounts
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemQuantityDiscountDelete extends AdminDBDelete
{
	// {{{ private properties

	private $relocate_url;

	// }}}
	// {{{ public function setRelocateURL()

	public function setRelocateURL($url)
	{
		$this->relocate_url = $url;
	}

	// }}}

	// process phaes
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');
		
		$sql = sprintf('delete from QuantityDiscount where id in (%s)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One quantity discount has been deleted.',
			'%d quantity discounts have been deleted.', $num),
			SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(
			Store::_('quantity discount'), Store::_('quantity discounts'));

		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'QuantityDiscount', 'integer:id', null, 'integer:quantity', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('confirmation_form');
		$form->action = $this->source;

		if ($form->getHiddenField(self::RELOCATE_URL_FIELD) === null) {
			if ($this->relocate_url === null)
				$url = $this->getRefererURL();
			else
				$url = $this->relocate_url;

			$form->addHiddenField(self::RELOCATE_URL_FIELD, $url);
		}
	}

	// }}}
}

?>
