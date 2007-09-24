<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Date.php';

/**
 * Edit page for Products
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;
	protected $ui_xml = 'Store/admin/components/Product/edit.xml';

	// }}}
	// {{{ private properties

	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		
		$this->fields = array('title', 'shortname', 'catalog', 'bodytext');

		$this->category_id = SiteApplication::initVar('category');

		$catalog_flydown = $this->ui->getWidget('catalog');
		$catalog_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Catalog', 'title', 'id', 'title'));

		// Only show blank option in catalogue flydown if there is more than
		// one catalogue to choose from.
		$catalog_flydown->show_blank = (count($catalog_flydown->options) > 1);

		if ($this->id === null) {
			$this->ui->getWidget('shortname_field')->visible = false;
			$this->ui->getWidget('submit_continue_button')->visible = true;
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('title')->value);

			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(
				Store::_('Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$valid = true;

		$sql = sprintf('select shortname from Product where id %s %s',
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'));

		$old_shortname = SwatDB::queryOne($this->app->db, $sql);


		// get selected catalog
		$catalog = $this->ui->getWidget('catalog');
		$catalog->process();
		$catalog_id = $catalog->value;

		/*
		 * Validate if shortname has changed. In the words of Creative Director
		 * Steven Garrity, "Allow weird data to stay weird."
		 */

		if ($this->id === null || $old_shortname != $shortname) {
			$sql = sprintf('select clone_of from Catalog where id %s %s', 
				SwatDB::equalityOperator($catalog_id, true),
				$this->app->db->quote($catalog_id, 'integer'));

			$clone_of = SwatDB::queryOne($this->app->db, $sql);

			// check shortname for uniqueness with selected catalog
			$sql = sprintf('select Catalog.id, Catalog.clone_of from Product
				inner join Catalog on Product.catalog = Catalog.id
				where Product.shortname = %s and Product.id %s %s',
				$this->app->db->quote($shortname, 'text'),
				SwatDB::equalityOperator($this->id, true),
				$this->app->db->quote($this->id, 'integer'));

			$catalogs = SwatDB::query($this->app->db, $sql);
			foreach ($catalogs as $product_catalog) {
				// shortname exists within same catalog
				if ($catalog_id == $product_catalog->id) {
					$valid = false;
					break;
				}

				// shortname exists in another catalog that is not a clone of
				// the selected catalog
				if ($clone_of != $product_catalog->id &&
					$catalog_id != $product_catalog->clone_of) {
					$valid = false;
					break;
				}
			}
		}

		return $valid;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();

		if ($this->id === null) {
			$this->fields[] = 'date:createdate';
			$date = new Date();
			$date->toUTC();
			$values['createdate'] = $date->getDate();
					
			$this->id = SwatDB::insertRow($this->app->db, 'Product',
				$this->fields, $values, 'id');

			$form = $this->ui->getWidget('edit_form');
			$category = $form->getHiddenField('category');
			if ($category !== null) {
				$sql = sprintf('insert into CategoryProductBinding
					(category, product) values (%s, %s)', $category, $this->id);

				SwatDB::query($this->app->db, $sql);
			}
		} else {
			SwatDB::updateRow($this->app->db, 'Product', $this->fields, $values,
				'id', $this->id);
		}

		$this->addToSearchQueue();

		$message = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$values['title']));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('title', 'shortname', 'catalog',
			'bodytext'));
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote(2, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote(2, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$button = $this->ui->getWidget('submit_continue_button');
		
		if ($button->hasBeenClicked()) {
			// manage skus
			$this->app->relocate(
				$this->app->getBaseHref().'Product/Details?id='.$this->id);
		} else {
			parent::relocate();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		// orphan product warning
		if ($this->id === null && $this->category_id === null) {
			$message = new SwatMessage(Store::_(
				'This product is not being created inside a category.'),
				SwatMessage::WARNING);

			$message->secondary_content = Store::_(
				'Though it may be possible to purchase from this product on '.
				'the front-end, it will not be possible to browse to this '.
				'product on the front-end.');

			$this->ui->getWidget('orphan_note')->add($message, SwatMessageDisplay::DISMISS_OFF);
		}

		// smart defaulting of the catalog
		if ($this->id === null) {
			$catalog = null;

			// check catalogue selector
			// TODO: use $this->app->session
			if (isset($_SESSION['catalog']) &&
				is_numeric($_SESSION['catalog'])) {
				$catalog = $_SESSION['catalog'];
			// check catelogue used by most products in this cateorgy
			} elseif ($this->category_id !== null) {
				$sql = 'select count(catalog) as num_products, catalog 
					from Product 
					where id in (
						select product from CategoryProductBinding 
						where category = %s) 
					group by catalog 
					order by num_products desc
					limit 1';

				$row = SwatDB::queryRow($this->app->db, sprintf($sql, 
					$this->app->db->quote($this->category_id, 'integer')));

				$catalog = ($row === null) ? null : $row->catalog;
			}

			$this->ui->getWidget('catalog')->value = $catalog;
		}
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('category', $this->category_id);
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

		if ($this->id === null) {
			$this->title = Store::_('New Product');
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('New Product')));

		} else {
			$product_title = SwatDB::queryOneFromTable($this->app->db, 
				'Product', 'text:title', 'id', $this->id);

			if ($this->category_id === null)
				$link = sprintf('Product/Details?id=%s', $this->id);
			else
				$link = sprintf('Product/Details?id=%s&category=%s', $this->id,
					$this->category_id);

			$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
			$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Edit')));
			$this->title = $product_title;
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Product',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('A product with an id of ‘%d’ does not exist.'),
				$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}

?>
