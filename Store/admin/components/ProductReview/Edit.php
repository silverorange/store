<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreProductReview.php';
if (class_exists('Blorg'))
	require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';

/**
 * Edit page for Product reviews
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductReviewEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/ProductReview/edit.xml';

	/**
	 * @var StoreProductReview
	 */
	protected $review;

	protected $category_id;
	protected $product_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->category_id = SiteApplication::initVar('category');
		$this->product_id  = SiteApplication::initVar('product');

		$this->initProductReview();

		if (class_exists('Blorg') &&
			($this->id === null || $this->review->author !== null)) {
			$this->ui->getWidget('author_field')->visible   = true;
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('status_field')->visible   = false;
		}
	}

	// }}}
	// {{{ protected function initProductReview()

	protected function initProductReview()
	{
		$class_name = SwatDBClassMap::get('StoreProductReview');
		$this->review = new $class_name();
		$this->review->setDatabase($this->app->db);

		if ($this->id === null) {
			$class_name = SwatDBClassMap::get('StoreProduct');
			$product = new $class_name();
			$product->setDatabase($this->app->db);

			if (!$product->load($this->product_id, $this->app->getInstance())) {
				throw new AdminNotFoundException(
					sprintf(Store::_('Product with id ‘%s’ not found.'),
						$product_id));
			}

			$this->review->product  = $product;
			$this->review->instance = $this->app->getInstance();

		} elseif (!$this->review->load($this->id, $this->app->getInstance())) {
			throw new AdminNotFoundException(
				sprintf(Store::_('Product review with id ‘%s’ not found.'),
					$this->id));
		}

		if ($this->product_id === null)
			$this->product_id = $this->review->product->id;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateReview();
		$this->review->save();

		if ($this->review->isModified()) {
			$this->review->save();

			$message = new SwatMessage(
				Store::_('Product Review has been saved.'));

			$this->app->messages->add($message);
		}
	}

	// }}}
	// {{{ protected function updateReview()

	protected function updateReview()
	{
		$values = $this->ui->getValues(array(
			'fullname',
			'email',
			'bodytext',
			'status',
			'author',
		));

		if ($this->review->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->review->createdate = $now;

			// all new reviews posted in the admin are tagged as author reviews
			$this->review->author_review = true;
		}

		$this->review->fullname = $values['fullname'];
		$this->review->email    = $values['email'];
		$this->review->bodytext = $values['bodytext'];
		$this->review->status   = $values['status'];
		$this->review->author   = $values['author'];
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('product', $this->product_id);

		if (class_exists('Blorg') &&
			($this->id === null || $this->review->author !== null)) {

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

				if ($this->id === null &&
					$row->usernum == $this->app->session->user->id)
					$this->ui->getWidget('author')->value = $row->id;
			}

			$this->ui->getWidget('author')->addOptionsByArray($authors);
		}

		$statuses = SiteComment::getStatusArray();
		$this->ui->getWidget('status')->addOptionsByArray($statuses);

		$this->ui->getWidget('edit_frame')->subtitle =
			$this->review->product->title;
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->review));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->category_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $this->product_id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product_id, $this->category_id);

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $this->product_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Product Review')));

		$this->title = $product_title;
	}

	// }}}
}

?>
