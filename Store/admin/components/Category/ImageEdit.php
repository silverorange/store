<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
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

	// }}}
	// {{{ private properties

	/**
	 * @var StoreCategory
	 */
	private $category;

	/**
	 * @var StoreCategoryImage
	 */
	private $image;

	private $dimensions;
	private $dimension_files;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->initCategory();
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

		if ($this->category->image !== null)
			$this->id = $this->category->image->id;
	}

	// }}}
	// {{{ protected function initDimensions()

	protected function initDimensions()
	{
		if ($this->category->image !== null) {
			$this->dimensions = $this->category->image->image_set->dimensions;
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
		if ($automatic->isUploaded())
			return true;

		if ($this->id === null && !$this->checkManualUploads()) {
			$message = new SwatMessage(Store::_('You need to specify all '.
				'image sizes when creating a new image or upload an image to '.
				'be automatically resized.'), SwatMessage::ERROR);

			$this->ui->getWidget('message')->add($message);
			return false;
		}
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
		$new_image = $this->processImage();
		$this->category->image = $new_image;
		$this->category->save();

		$message = new SwatMessage(Store::_('Category Image has been saved.'));
		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function processImage()

	protected function processImage()
	{
		$automatic = $this->ui->getWidget('original_image');
		if (!$automatic->isUploaded() && !$this->checkManualUploads()) {
			$image = $this->category->image;
		} else {
			$class_name = SwatDBClassMap::get('StoreCategoryImage');
			$image = new $class_name();
			$image->setDatabase($this->app->db);
			$file = $this->ui->getWidget('original_image');

			if ($file->isUploaded()) {
				$image->setFileBase('../images');
				$image->process($file->getTempFileName());
			}

			foreach ($this->dimensions as $dimension) {
				$file = $this->dimension_files[$dimension->shortname];
				if ($file->isUploaded()) {
					$image->setFileBase('../images');
					$image->processManual($file->getTempFileName(),
						$dimension->shortname);
				}
			}

			// delete the old image
			if ($this->category->image !== null) {
				$this->category->image->setFileBase('../images');
				$this->category->image->delete();
			}
		}

		return $image;
	}

	// }}}

	// build phase
	// {{{ protected buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('edit_frame');
		$frame->subtitle = $this->category->title;

		if ($this->category->image === null)
			$frame->title = Store::_('Add Category Image for');
		else
			$this->ui->getWidget('image')->visible = true;

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

		foreach ($cat_navbar_rs as $entry)
			$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
				'Category/Index?id='.$entry->id));

		if ($this->id === null)
			$last_entry = new SwatNavBarEntry(Store::_('Add Category Image'));
		else
			$last_entry = new SwatNavBarEntry(
				Store::_('Change Category Image'));

		$this->navbar->addEntry($last_entry);
		$this->title = $this->category->title;
	}

	// }}}
}

?>
