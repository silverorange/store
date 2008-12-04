<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/dataobjects/StoreProductImage.php';
require_once 'Site/dataobjects/SiteImageSet.php';

/**
 * Edit page for product images
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $id;
	protected $ui_xml = 'Store/admin/components/Product/image-edit.xml';

	/**
	 * @var StoreProductImage
	 */
	protected $image;

	/**
	 * @var StoreProduct
	 */
	protected $product;

	/**
	 * Optional id of the product's current category. This is only used to
	 * maintain the proper navbar breadcrumbs when getting to this page by
	 * browsing the categories.
	 *
	 * @var integer
	 */
	protected $category_id;

	protected $dimensions;
	protected $dimension_files;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->id = $this->app->initVar('id');
		$this->category_id = SiteApplication::initVar('category');

		$this->initProduct();
		$this->initImage();
		$this->initDimensions();
	}

	// }}}
	// {{{ protected function initProduct()

	protected function initProduct()
	{
		$product_id = $this->app->initVar('product');
		$class_name = SwatDBClassMap::get('StoreProduct');
		$this->product = new $class_name();
		$this->product->setDatabase($this->app->db);

		if (!$this->product->load($product_id)) {
			throw new AdminNotFoundException(
				sprintf('Product with id ‘%s’ not found.', $product_id));
		}
	}

	// }}}
	// {{{ protected function initImage()

	protected function initImage()
	{
		$class_name = SwatDBClassMap::get('StoreProductImage');
		$this->image = new $class_name();
		$this->image->setDatabase($this->app->db);

		if ($this->id !== null && !$this->image->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf('Product image with id ‘%s’ not found.', $this->id));
		}
	}

	// }}}
	// {{{ protected function initDimensions()

	protected function initDimensions()
	{
		if ($this->id !== null) {
			$this->dimensions = $this->image->image_set->dimensions;
		} else {
			$class_name = SwatDBClassMap::get('SiteImageSet');
			$image_set = new $class_name();
			$image_set->setDatabase($this->app->db);
			$image_set->loadByShortname('products');
			$this->dimensions = $image_set->dimensions;
		}

		$manual_fieldset = $this->ui->getWidget('manual_fieldset');
		$note = Store::_('Maximum Dimensions: %s px');
		foreach ($this->dimensions as $dimension) {
			$dimension_text = $dimension->max_width;
			if ($dimension->max_height !== null)
				$dimension_text = sprintf('%s x %s', $dimension->max_width,
					$dimension->max_height);

			$form_field = new SwatFormField();
			$form_field->title = $dimension->title;
			$form_field->note  = sprintf($note, $dimension_text);
			$file_widget = new SwatFileEntry($dimension->shortname);
			$form_field->addChild($file_widget);
			$manual_fieldset->addChild($form_field);

			$this->dimension_files[$dimension->shortname] = $file_widget;
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		// if we're adding an image either the automatic image is uploaded, or
		// all sizes of manual uploads

		$automatic = $this->ui->getWidget('original_image');
		if ($automatic->isUploaded()) return true;

		if ($this->id === null && !$this->checkManualUploads()) {

			$message = new SwatMessage(Store::_('You need to specify all '.
				'image sizes when creating a new image or upload an image to '.
				'be automatically resized.'), SwatMessage::ERROR);

			$this->ui->getWidget('message')->add($message);
			return false;
		}
	}

	// }}}
	// {{{ private function checkManualUploads()

	protected function checkManualUploads()
	{
		$uploaded = true;
		foreach ($this->dimensions as $dimension) {
			$uploaded = $uploaded &&
				$this->dimension_files[$dimension->shortname]->isUploaded();
		}

		return $uploaded;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->processImage();
		$values = $this->ui->getValues(array('title', 'border', 'description'));

		$this->image->title       = $values['title'];
		$this->image->border      = $values['border'];
		$this->image->description = $values['description'];

		$this->image->save();

		if ($this->id == null) {
			$sql = sprintf('insert into ProductImageBinding
				(product, image) values (%s, %s)',
				$this->app->db->quote($this->product->id, 'integer'),
				$this->app->db->quote($this->image->id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		}

		$message = new SwatMessage(Store::_('Product Image has been saved.'));
		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function processImage()

	protected function processImage()
	{
		$file = $this->ui->getWidget('original_image');

		if ($file->isUploaded()) {
			$this->image->setFileBase('../images');
			$this->image->process($file->getTempFileName());
		}

		foreach ($this->dimensions as $dimension) {
			$file = $this->dimension_files[$dimension->shortname];
			if ($file->isUploaded()) {
				$this->image->setFileBase('../images');
				$this->image->processManual($file->getTempFileName(),
					$dimension->shortname);
			}
		}
	}

	// }}}

	// build phase
	// {{{ protected buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('edit_frame');
		$frame->subtitle = $this->product->title;

		if ($this->id === null)
			$frame->title = Store::_('Add Product Image for');
		else
			$this->ui->getWidget('image')->visible = true;

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('product', $this->product->id);
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->image));

		$image = $this->ui->getWidget('image');
		$image->image          = $this->image->getUri('thumb', '../');
		$image->width          = $this->image->getWidth('thumb');
		$image->height         = $this->image->getHeight('thumb');
		$image->preview_image  = $this->image->getUri('large', '../');
		$image->preview_width  = $this->image->getWidth('large');
		$image->preview_height = $this->image->getHeight('large');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();
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
			$link = sprintf('Product/Details?id=%s', $this->product->id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product->id, $this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($this->product->title,
			$link));

		if ($this->id === null)
			$last_entry = new SwatNavBarEntry(Store::_('Add Product Image'));
		else
			$last_entry = new SwatNavBarEntry(Store::_('Change Product Image'));

		$this->navbar->addEntry($last_entry);
		$this->title = $this->product->title;
	}

	// }}}
}

?>
