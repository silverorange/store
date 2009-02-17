<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/Store.php';

/**
 * Delete confirmation page for PriceRanges
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRangeDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected funtion processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = $this->getProcessSQL();
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One price range has been deleted.',
			'%d price ranges have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getProcessSQL()

	protected function getProcessSQL()
	{
		return 'delete from PriceRange where id in (%s)';
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$count = $this->getItemCount();

		$content = sprintf(ngettext(
			'Delete one price range?',
			'Delete %d price ranges?', $count),
			SwatString::numberFormat($count));

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = '<h3>'.$content.'</h3>';
		$message->content_type = 'text/xml';
	}

	// }}}
}

?>
