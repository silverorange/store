<?php


/**
 * Delete confirmation page for shipping rates
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeRateDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('text');

		$sql = sprintf('delete from ShippingRate where id in (%s)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One shipping rate has been deleted.',
			'%s shipping rates have been deleted.', $num),
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

		$count = $this->getItemCount();

		$content = sprintf(Store::ngettext(
			'Delete one shipping rate?',
			'Delete %s shipping rates?', $count),
			SwatString::numberFormat($count));

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = '<h3>'.$content.'</h3>';
		$message->content_type = 'text/xml';
	}

	// }}}
}

?>
