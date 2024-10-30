<?php

/**
 * Delete confirmation page for shipping types
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData(): void
	{
		parent::processDBData();

		$item_list = $this->getItemList('text');

		$sql = sprintf('delete from ShippingType where id in (%s)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One shipping type has been deleted.',
			'%s shipping types have been deleted.', $num),
			SwatString::numberFormat($num)),
			'notice');

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
		$dep->setTitle(Store::_('shipping type'), Store::_('shipping types'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'ShippingType', 'integer:id', null, 'text:title', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$dep_rates = new AdminSummaryDependency();
		$dep_rates->setTitle(
			Store::_('shipping rate'), Store::_('shipping rates'));

		$dep_rates->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'ShippingRate', 'integer:id',
			'integer:shipping_type', 'shipping_type in ('.$item_list.')',
			AdminDependency::DELETE);

		$dep->addDependency($dep_rates);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
