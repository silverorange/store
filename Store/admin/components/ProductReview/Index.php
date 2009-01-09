<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Site/admin/SiteCommentVisibilityCellRenderer.php';
require_once 'Store/dataobjects/StoreProductReviewWrapper.php';

/**
 * Index page for Products Reviews
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductReviewIndex extends AdminSearch
{
	// {{{ class constants

	const SHOW_UNAPPROVED = 1;
	const SHOW_ALL        = 2;
	const SHOW_ALL_SPAM   = 3;
	const SHOW_SPAM       = 4;

	// }}}
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/ProductReview/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$visibility_options = array(
			self::SHOW_UNAPPROVED => Store::_('Pending Reviews'),
			self::SHOW_ALL        => Store::_('All Reviews'),
			self::SHOW_ALL_SPAM   => Store::_('All Reviews, Including Spam'),
			self::SHOW_SPAM       => Store::_('Spam Only'),
		);

		$visibility = $this->ui->getWidget('search_visibility');
		$visibility->addOptionsByArray($visibility_options);
		$visibility->value = self::SHOW_UNAPPROVED;
	}

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
		foreach ($view->getSelection() as $item)
			$item_list[] = $this->app->db->quote($item, 'integer');


		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('ProductReview/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'approve':
			$sql = 'update ProductReview set status = %s, spam = %s
				where id in (%s)';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(SiteComment::STATUS_PUBLISHED, 'integer'),
				$this->app->db->quote(false, 'boolean'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$num = count($view->getSelection());

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One product review has been published.',
				'%d product reviews have been published.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;

		case 'deny':
			$sql = 'update ProductReview set status = %s where id in (%s)';
			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(SiteComment::STATUS_UNPUBLISHED,
					'integer'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$num = count($view->getSelection());

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One product review has been unpushlished.',
				'%d product reviews have been unpushlished.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;

		case 'spam':
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
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$this->buildPendingProductReviews();
		parent::buildInternal();
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select count(id) from ProductReview where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select * from ProductReview where %s order by %s';
		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'ProductReview.id desc'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$reviews = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreProductReviewWrapper'));

		if (count($reviews) > 0) {
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');
		}

		$store = new SwatTableStore();
		foreach ($reviews as $review) {
			$ds = new SwatDetailsStore($review);
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$instance_id = $this->app->getInstanceId();
		$where = sprintf('instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		// keywords
		$keywords = $this->ui->getWidget('search_keywords')->value;
		if (trim($keywords) != '') {
			$clause = new AdminSearchClause('bodytext');
			$clause->table = 'ProductReview';
			$clause->value = $keywords;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'and');
		}

		// author
		$author = $this->ui->getWidget('search_author')->value;
		if (trim($author) != '') {
			$clause = new AdminSearchClause('fullname');
			$clause->table = 'ProductReview';
			$clause->value = $author;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'and');
		}

		// email
		$email = $this->ui->getWidget('search_email')->value;
		if (trim($email) != '') {
			$clause = new AdminSearchClause('email');
			$clause->table = 'ProductReview';
			$clause->value = $email;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'and');
		}

		// visibility
		$visibility = $this->ui->getWidget('search_visibility')->value;
		switch ($visibility) {
		default:
		case self::SHOW_UNAPPROVED :
			$where.= sprintf(
				' and status = %s and spam = %s',
				$this->app->db->quote(SiteComment::STATUS_PENDING,
					'integer'),
				$this->app->db->quote(false, 'boolean'));

			break;

		case self::SHOW_ALL :
			$where.= sprintf(' and spam = %s',
				$this->app->db->quote(false, 'boolean'));

			break;

		case self::SHOW_ALL_SPAM :
			// no extra where needed
			break;

		case self::SHOW_SPAM :
			$where.= sprintf(' and spam = %s',
				$this->app->db->quote(true, 'boolean'));

			break;
		}


		return $where;
	}

	// }}}

	// pending summary
	// {{{ protected function buildPendingProductReviews()

	protected function buildPendingProductReviews()
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select count(id) from ProductReview
			where status = %1$s and spam = %2$s and author_review = %2$s
				and instance %3$s %4$s',
			$this->app->db->quote(SiteComment::STATUS_PENDING, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$count = SwatDB::queryOne($this->app->db, $sql);

		$toolbar_link = $this->ui->getWidget('approve_product_reviews');
		$toolbar_link->title = sprintf(Store::_('%s pending %s'),
			($count > 0) ? '<strong>'.$count.'</strong>' : 'No',
			Store::ngettext('review', 'reviews', $count));

		$toolbar_link->sensitive = ($count > 0);
	}

	// }}}
}

?>
