<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Store/dataobjects/StoreFeedbackWrapper.php';

/**
 * Delete confirmation page for feedback
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');

		// delete feedback
		$sql = sprintf('delete from Feedback where id in (%s)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(
			sprintf(
				Store::ngettext(
					'One feedback message has been deleted.',
					'%d feedback messages have been deleted.',
					$num
				),
				SwatString::numberFormat($num)
			)
		);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$dep = new AdminListDependency();
		$dep->setTitle(
			Store::_('feedback message'),
			Store::_('feedback messages')
		);

		$sql = sprintf(
			'select * from Feedback
			where instance %s %s and id in (%s)
			order by createdate desc, id',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$item_list
		);

		$entries = array();
		$feedback_messages = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('StoreFeedbackWrapper')
		);

		foreach ($feedback_messages as $feedback) {
			$entry = new AdminDependencyEntry();

			$entry->id = $feedback->id;

			$entry->title = SwatString::ellipsizeRight(
				SwatString::condense(
					SiteCommentFilter::toXhtml($feedback->bodytext)
				),
				100
			);

			$entry->status_level = AdminDependency::DELETE;
			$entry->parent       = null;

			$entries[] = $entry;
		}

		$dep->entries = $entries;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) === 0) {
			$this->switchToCancelButton();
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		// Take "Delete" off the navbar
		$this->navbar->popEntry();

		$this->navbar->addEntry(
			new SwatNavBarEntry(
				Store::ngettext(
					'Delete Feedback Message',
					'Delete Feedback Messages',
					count($this->items)
				)
			)
		);
	}

	// }}}
}

?>
