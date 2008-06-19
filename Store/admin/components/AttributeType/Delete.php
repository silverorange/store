<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';

/**
 * Delete confirmation page for Attribute Types
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeTypeDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');

		$sql = sprintf('delete from AttributeType where id in (%s)
			and id not in (select attribute_type from Attribute)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One attribute type has been deleted.',
			'%s attribute types have been deleted.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

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
		$dep->setTitle(Store::_('attribute type'), Store::_('attribute types'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'AttributeType', 'integer:id', null, 'text:shortname', 'shortname',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		// dependent order addresses
		$attribute_dependency = new AdminSummaryDependency();
		$attribute_dependency->setTitle(
			Store::_('attribute'), Store::_('attributes'));

		$attribute_dependency->summaries =
			AdminSummaryDependency::querySummaries(
			$this->app->db, 'Attribute', 'integer:id', 'integer:attribute_type',
			'attribute_type in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($attribute_dependency);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
