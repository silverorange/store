<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/dataobjects/StoreAttributeTypeWrapper.php';
require_once 'Store/dataobjects/StoreAttributeWrapper.php';
require_once 'Store/StoreCatalogSelector.php';
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

	protected $ui_xml = 'Store/admin/components/Product/edit.xml';
	protected $product;

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

		$this->category_id = SiteApplication::initVar('category');

		$this->initProduct();
		$this->initAttributeList();

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
	// {{{ protected function initProduct()

	protected function initProduct()
	{
		$class_name = SwatDBClassMap::get('StoreProduct');
		$this->product = new $class_name();
		$this->product->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->product->load($this->id))
				throw new AdminNotFoundException(sprintf(
					Store::_('A product with an id of ‘%d’ does not exist.'),
					$this->id));
		}
	}

	// }}}
	// {{{ private function initAttributeList()

	/**
	 * Builds the list of attributes using an image and a title
	 */
	private function initAttributeList()
	{
		$replicators = array();

		$attribute_types = SwatDB::query($this->app->db,
			'select * from AttributeType order by shortname',
			SwatDBClassMap::get('StoreAttributeTypeWrapper'));

		foreach ($attribute_types as $type)
			$replicators[$type->id] = ucfirst($type->shortname);

		$attributes_field = $this->ui->getWidget('attributes_form_field');
		$attributes_field->replicators = $replicators;
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname');
		$title = $this->ui->getWidget('title');

		if ($this->id === null && $shortname->value === null) {
			// only generate a shortname if there is a title to generate it from
			if ($title->value !== null) {
				$new_shortname = $this->generateShortname($title->value);
				$shortname->value = $new_shortname;
			}
		} elseif (!$this->validateShortname($shortname->value)) {
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
		// calling process more than once caused multiple error messages on this
		// field if it is currently null
		if (!$catalog->isProcessed())
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
		$this->updateProduct();
		$this->product->save();

		$form = $this->ui->getWidget('edit_form');
		$category = $form->getHiddenField('category');

		if ($category !== null && $this->id === null) {
			$sql = sprintf('insert into CategoryProductBinding
				(category, product) values (%s, %s)', $category,
					$this->product->id);

			SwatDB::query($this->app->db, $sql);
		}

		$this->saveAttributes();
		$this->addToSearchQueue();

		$message = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$this->product->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateProduct()

	protected function updateProduct()
	{
		$values = $this->ui->getValues(array('title', 'shortname', 'catalog',
			'bodytext'));

		if ($this->id === null) {
			$now = new Date();
			$now->toUTC();
			$this->product->createdate = $now->getDate();
		}

		$this->product->title     = $values['title'];
		$this->product->shortname = $values['shortname'];
		$this->product->catalog   = $values['catalog'];
		$this->product->bodytext  = $values['bodytext'];
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'product');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->product->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->product->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

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
				$this->app->getBaseHref().'Product/Details?id='.
					$this->product->id);
		} else {
			parent::relocate();
		}
	}

	// }}}
	// {{{ private function saveAttributes()

	private function saveAttributes()
	{
		$attributes_field = $this->ui->getWidget('attributes_form_field');
		$attribute_array = array();

		foreach ($attributes_field->replicators as $id => $title)
			$attribute_array = array_merge($attribute_array,
				$attributes_field->getWidget('attributes', $id)->values);

		SwatDB::updateBinding($this->app->db, 'ProductAttributeBinding',
			'product', $this->product->id, 'attribute',
			$attribute_array, 'Attribute', 'id');
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

			$this->ui->getWidget('orphan_note')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		$form = $this->ui->getWidget('edit_form');
		if ($this->id === null && !$form->isProcessed())
			$this->smartDefaultCatalog();

		$this->buildAttributes();
	}

	// }}}
	// {{{ protected function smartDefaultCatalog()

	protected function smartDefaultCatalog()
	{
		$catalog = null;

		// check catalogue selector
		$catalog_selector = new StoreCatalogSelector();
		$catalog_selector->setState(SiteApplication::initVar('catalog', null,
			SiteApplication::VAR_SESSION));

		if ($catalog_selector->catalog !== null) {
			$catalog = $catalog_selector->catalog;
		} elseif ($this->category_id !== null) {
			// check catelogue used by most products in this cateorgy
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
	// {{{ protected function buildAttributes()

	protected function buildAttributes()
	{
		$sql = 'select id, shortname, title, attribute_type from Attribute
			order by attribute_type, displayorder, title, id';

		$attributes = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreAttributeWrapper'));
		$attributes_field = $this->ui->getWidget('attributes_form_field');

		foreach ($attributes as $attribute) {
			ob_start();
			$this->displayAttribute($attribute);
			$option = ob_get_clean();

			$attributes_field->getWidget('attributes',
				$attribute->attribute_type->id)->addOption(
					$attribute->id, $option, 'text/xml');
		}
	}

	// }}}
	// {{{ protected function displayAttribute()

	protected function displayAttribute(StoreAttribute $attribute)
	{
		$attribute->display();
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->product));

		// make sure that the catalog defaults correctly
		$this->ui->getWidget('catalog')->value =
			$this->product->getInternalValue('catalog');

		$this->loadAttributes();
	}

	// }}}
	// {{{ protected function loadAttributes()

	protected function loadAttributes()
	{
		$attribute_values = SwatDB::queryColumn($this->app->db,
			'ProductAttributeBinding', 'attribute', 'product', $this->id);

		$attributes_field = $this->ui->getWidget('attributes_form_field');
		$attribute_array = array();

		foreach ($attributes_field->replicators as $id => $title)
			$attributes_field->getWidget('attributes', $id)->values =
				$attribute_values;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/admin/styles/store-product-edit-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
