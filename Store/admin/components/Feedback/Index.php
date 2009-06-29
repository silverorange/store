<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Store/dataobjects/StoreFeedbackWrapper.php';

/**
 * Index page for customer feedback
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Feedback/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();
		$this->ui->getWidget('pager')->process();
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$item_list = array();
		foreach ($view->getSelection() as $item) {
			$item_list[] = $this->app->db->quote($item, 'integer');
		}

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Feedback/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

/*		case 'spam':
			$sql = 'update ProductReview set spam = %s where id in (%s)';
			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(true, 'boolean'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$num = count($view->getSelection());

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One product review has been marked as spam.',
				'%d product reviews have been marked as spam.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;*/
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf(
			'select count(id) from Feedback where %s',
			$this->getWhereClause()
		);

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select * from Feedback where %s order by %s';
		$sql = sprintf(
			$sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'Feedback.id desc')
		);

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$feedback_messages = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('StoreFeedbackWrapper')
		);

		if (count($feedback_messages) > 0) {
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');
		}

		$store = new SwatTableStore();
		foreach ($feedback_messages as $feedback) {
			$ds = new SwatDetailsStore($feedback);
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$instance_id = $this->app->getInstanceId();
		$where = sprintf(
			'instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer')
		);

		// keywords
		$keywords = $this->ui->getWidget('search_keywords')->value;
		if (trim($keywords) != '') {
			$clause = new AdminSearchClause('bodytext');
			$clause->table = 'Feedback';
			$clause->value = $keywords;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'and');
		}

		// author
		$author = $this->ui->getWidget('search_author')->value;
		if (trim($author) != '') {
			$clause = new AdminSearchClause('fullname');
			$clause->table = 'Feedback';
			$clause->value = $author;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'and');
		}

		// email
		$email = $this->ui->getWidget('search_email')->value;
		if (trim($email) != '') {
			$clause = new AdminSearchClause('email');
			$clause->table = 'Feedback';
			$clause->value = $email;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'and');
		}

		return $where;
	}

	// }}}
}

?>
