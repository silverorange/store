<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Store/dataobjects/StoreCategory.php';
require_once 'Store/dataobjects/StoreCategoryImage.php';

/**
 * Edit page for Category images
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryImageEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Category/image-edit.xml';

	/**
	 * @var StoreCategoryImage
	 */
	protected $image;

	/**
	 * @var StoreCategory
	 */
	protected $category;

	protected $dimensions;
	protected $dimension_files;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->initCategory();
		$this->initImage();
		$this->initDimensions();
	}

	// }}}
	// {{{ protected function initCategory()

	protected function initCategory()
	{
		$category_id = $this->app->initVar('category');
		$class_name = SwatDBClassMap::get('StoreCategory');
		$this->category = new $class_name();
		$this->category->setDatabase($this->app->db);

		if (!$this->category->load($category_id)) {
			throw new AdminNotFoundException(
				sprintf('Category with id ‘%s’ not found.', $category_id));
		}

		if ($this->category->image !== null) {
			$this->id = $this->category->image->id;
		}
	}

	// }}}
	// {{{ protected function initImage()

	protected function initImage()
	{
		$this->image = $this->getNewImageInstance();

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
			$image_set->loadByShortname('categories');
			$this->dimensions = $image_set->dimensions;
		}

		$manual_fieldset = $this->ui->getWidget('manual_fieldset');
		$note = Store::_('Maximum Dimensions: %s px');
		foreach ($this->dimensions as $dimension) {
			$form_field = new SwatFormField();
			$form_field->title = $dimension->title;

			$width  = $dimension->max_width;
			$height = $dimension->max_height;
			if ($height !== null || $width !== null) {
				if ($height !== null && $width !== null) {
					$dimension_text = sprintf('%s x %s', $width, $height);
				} elseif ($width === null) {
					$dimension_text = $height;
				} elseif ($height === null) {
					$dimension_text = $width;
				}
				$form_field->note  = sprintf($note, $dimension_text);
			}

			$file_widget = new SwatFileEntry($dimension->shortname);
			$form_field->addChild($file_widget);
			$manual_fieldset->addChild($form_field);

			$this->dimension_files[$dimension->shortname] = $file_widget;
		}
	}

	// }}}
	// {{{ protected function getNewImageInstance()

	protected function getNewImageInstance()
	{
		$class_name = SwatDBClassMap::get('StoreCategoryImage');
		$image = new $class_name();
		$image->setDatabase($this->app->db);

		return $image;
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	/**
	 * Valid for new images when either the original image is uploaded, or if
	 * all manual dimensions are uploaded. For edited images, always valid.
	 *
	 * @returns boolean
	 */
	protected function validate()
	{
		$valid = true;

		$automatic = $this->ui->getWidget('original_image');
		if ($automatic->isUploaded()) {
			$valid = true;
		} elseif ($this->id === null && !$this->checkManualUploads()) {
			$message = new SwatMessage(Store::_('You need to specify all '.
				'image sizes when creating a new image or upload an image to '.
				'be automatically resized.'),
				'error');

			$this->ui->getWidget('message')->add($message);
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function chackManualUploads()

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
		$this->category->image = $this->image;
		$this->category->save();

		$message = new SwatMessage(Store::_('Category Image has been saved.'));
		$this->app->messages->add($message);

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('product');
		}
	}

	// }}}
	// {{{ protected function processImage()

	protected function processImage()
	{
		$original = $this->ui->getWidget('original_image');
		if ($original->isUploaded()) {
			$image = $this->getNewImageInstance();
			$image->setFileBase('../images');
			$image->process($original->getTempFileName());

			// Delete the old image. Prevents broswer/CDN caching.
			if ($this->id !== null) {
				$this->image->setFileBase('../images');
				$this->image->delete();
			}

			$this->image = $image;
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
		$frame->subtitle = $this->category->title;

		if ($this->category->image === null) {
			$frame->title = Store::_('Add Category Image for');
		} else {
			$this->ui->getWidget('image')->visible = true;
		}

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('category', $this->category->id);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->category->image));

		$image = $this->ui->getWidget('image');
		$image->image  = $this->category->image->getUri('thumb', '../');
		$image->width  = $this->category->image->getWidth('thumb');
		$image->height = $this->category->image->getHeight('thumb');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();

		$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
			'getCategoryNavbar', array($this->category->id));

		foreach ($cat_navbar_rs as $entry) {
			$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
				'Category/Index?id='.$entry->id));
		}

		if ($this->id === null) {
			$last_entry = new SwatNavBarEntry(Store::_('Add Category Image'));
		} else {
			$last_entry = new SwatNavBarEntry(
				Store::_('Change Category Image'));
		}

		$this->navbar->addEntry($last_entry);
		$this->title = $this->category->title;
	}

	// }}}
}

?>
