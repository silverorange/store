<?php

require_once 'Admin/pages/AdminApproval.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Store/dataobjects/StoreProductReview.php';
if (class_exists('Blorg')) {
	require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';
}

/**
 * Approval page for Product reviews
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductReviewApproval extends AdminApproval
{
	// init phase
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/approval.xml';
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$title = Store::_('Product Review Approval');
		$this->navbar->createEntry($title);
		$this->ui->getWidget('frame')->title = $title;

		if (class_exists('Blorg')) {
			$this->ui->getWidget('author_field')->visible = true;
			$this->initAuthors();
		}
	}

	// }}}
	// {{{ protected function initDataObject()

	protected function initDataObject($id)
	{
		$class_name = SwatDBClassMap::get('StoreProductReview');
		$this->data_object = new $class_name();
		$this->data_object->setDatabase($this->app->db);

		if (!$this->data_object->load($id))
			throw new AdminNotFoundException(
				sprintf(Store::_('Review with id ‘%s’ not found.'), $id));
	}

	// }}}
	// {{{ protected function getPendingIds()

	protected function getPendingIds()
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select id from ProductReview
			where status = %s and spam = %s and instance %s %s
			order by createdate asc',
			$this->app->db->quote(SiteComment::STATUS_PENDING, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$rows = SwatDB::query($this->app->db, $sql);

		$ids = array();
		foreach ($rows as $row)
			$ids[] = $row->id;

		return $ids;
	}

	// }}}
	// {{{ protected function initAuthors()

	protected function initAuthors()
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select BlorgAuthor.*,
				AdminUserInstanceBinding.usernum
			from BlorgAuthor
			left outer join AdminUserInstanceBinding on
				AdminUserInstanceBinding.default_author = BlorgAuthor.id
			where BlorgAuthor.instance %s %s and BlorgAuthor.visible = %s
			order by displayorder',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$rs = SwatDB::query($this->app->db, $sql);

		$authors = array();
		foreach ($rs as $row) {
			$authors[$row->id] = $row->name;

			if ($row->usernum == $this->app->session->user->id) {
				$this->ui->getWidget('author')->value = $row->id;
			}
		}

		$this->ui->getWidget('author')->addOptionsByArray($authors);
	}

	// }}}

	// process phase
	// {{{ protected function save()

	protected function save()
	{
		if ($this->ui->getWidget('approve_button')->hasBeenClicked() ||
			$this->ui->getWidget('reply_button')->hasBeenClicked()) {
			$this->approve();
		} elseif ($this->ui->getWidget('delete_button')->hasBeenClicked()) {
			$this->delete();
		}
	}

	// }}}
	// {{{ protected function approve()

	protected function approve()
	{
		if (mb_strlen($this->ui->getWidget('bodytext')->value)) {
			$class_name = SwatDBClassMap::get('StoreProductReview');
			$reply = new $class_name();
			$reply->setDatabase($this->app->db);
			$reply->author_review = true;
			$reply->product       = $this->data_object->product;
			$reply->parent        = $this->data_object;
			$reply->bodytext      = $this->ui->getWidget('bodytext')->value;
			$reply->status        = SiteComment::STATUS_PUBLISHED;
			$reply->createdate    = new SwatDate();
			$reply->createdate->toUTC();

			if (class_exists('Blorg')) {
				$reply->author = $this->ui->getWidget('author')->value;
			}

			$reply->save();
		}

		$this->data_object->status = SiteComment::STATUS_PUBLISHED;
		$this->data_object->save();
	}

	// }}}

	// build phase
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$review = $this->data_object;

		// link the product title if you have access to the component.
		echo '<div class="product-title">';
		if ($this->app->session->user->hasAccessByShortname('Product')) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf('Product/Details?id=%s',
				$this->data_object->product->id);

			$a_tag->setContent($this->data_object->product->title);
			$a_tag->display();
		} else {
			echo SwatString::minimizeEntities(
				$this->data_object->product->title);
		}
		echo '</div>';

		$view = $this->getView();
		$view->display($this->data_object);
	}

	// }}}
	// {{{ protected function getView()

	protected function getView()
	{
		$view = SiteViewFactory::get($this->app, 'product-review');

		$view->setPartMode('replies',    SiteView::MODE_NONE);
		$view->setPartMode('javascript', SiteView::MODE_NONE);

		return $view;
	}

	// }}}
}

?>
