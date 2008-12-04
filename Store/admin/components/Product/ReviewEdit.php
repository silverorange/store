<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreProductReview.php';

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

	protected $ui_xml = 'Store/admin/components/Product/review-edit.xml';

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

			$this->review->product = $product;

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
			'link',
			'email',
			'bodytext',
			'status',
		));

		if ($this->review->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->review->createdate = $now;
		}

		$this->review->fullname = $values['fullname'];
		$this->review->link     = $values['link'];
		$this->review->email    = $values['email'];
		$this->review->bodytext = $values['bodytext'];
		$this->review->status   = $values['status'];

		if ($this->review->status === null) {
			$this->review->status = SiteComment::STATUS_PUBLISHED;
		}
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

		$statuses = SiteComment::getStatusArray();
		$this->ui->getWidget('status')->addOptionsByArray($statuses);
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
