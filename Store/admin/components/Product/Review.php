<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatString.php';

/**
 * Edit page for Product reviews
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 */
class StoreProductReview extends AdminDBEdit
{
	// {{{ private properties

	private $fields;
	private $category_id;
	private $product_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		if ($this->id === null)
			$this->product_id = SiteApplication::initVar('product');
		else
			$this->product_id = SwatDB::queryOne($this->app->db, sprintf(
				'select product from ProductReview where id = %s',
				$this->app->db->quote($this->id, 'integer')));

		$this->category_id = SiteApplication::initVar('category');

		$this->ui->loadFromXML(dirname(__FILE__).'/review.xml');

		$this->fields = array('description', 'bodytext', 'fullname',
			'email', 'boolean:enabled');

	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('description', 'bodytext',
			'fullname', 'email', 'enabled'));

		if ($this->id === null) {
			$values['product'] =
				$this->ui->getWidget('edit_form')->getHiddenField('product');

			$this->fields[] = 'integer:product';

			$this->id = SwatDB::insertRow($this->app->db, 'ProductReview',
				$this->fields, $values, 'integer:id');

		} else {
			SwatDB::updateRow($this->app->db, 'ProductReview', $this->fields,
				$values, 'id', $this->id);
		}

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'),
				SwatString::ellipsizeRight($values['description'], 50)));

		$this->app->messages->add($message);
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
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$fields = $this->fields;

		$row = SwatDB::queryRowFromTable($this->app->db, 'ProductReview',
			$fields, 'id', $this->id);

		if ($row === null) {
			throw new AdminNotFoundException(
				sprintf(Store::_('Product review with id ‘%s’ not found.'),
					$this->id));
		}

		$this->ui->setValues(get_object_vars($row));
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
