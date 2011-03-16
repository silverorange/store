<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for Features
 *
 * @package   Store
 * @copyright 2010-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeatureDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from Feature where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(ngettext(
			'One feature has been deleted.',
			'%d features have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('StoreFeature');
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Store::_('feature'),
			Store::_('features'));

		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Feature', 'integer:id', null, 'text:title',
			'title', 'id in ('.$item_list.')',
			AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
