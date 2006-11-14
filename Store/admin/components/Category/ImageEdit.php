<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Date.php';
require_once 'Image/Transform.php';
require_once 'Store/dataobjects/StoreCategoryImage.php';
require_once 'Store/StoreClassMap.php';

/**
 * Edit page for Category images
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryImageEdit extends AdminPage
{
	// {{{ private properties

	private $id;
	private $category_id;
	private $category_title;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/imageedit.xml');

		$this->category_id = SiteApplication::initVar('category');

		$sql = sprintf('select title, image from Category where id = %s',
			$this->app->db->quote($this->category_id, 'integer'));

		$row = SwatDB::queryRow($this->app->db, $sql);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('There is no image associated with category ‘%d’.'),
				$this->category_id));

		$this->id = $row->image;
		$this->category_title = $row->title;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('edit_form');

		if ($form->isProcessed()) {
			$msg_text = Store::_('There is a problem with the one of the '.
				'files submitted below.');

			if (!$this->validate()) {
				$msg = new SwatMessage($msg_text, SwatMessage::ERROR);
				$this->app->messages->add($msg);
			} else {
				if ($this->processImages()) {
					$this->relocate();
				} else {
					$msg = new SwatMessage($msg_text, SwatMessage::ERROR);
					$this->app->messages->add($msg);
				}
			}
		}
	}

	// }}}
	// {{{ protected function processImages()

	/**
	 * This resizes and renames images and updates the database
	 *
	 * If invalid dimensions are uploaded this returns false and the database
	 * is not updated.
	 */
	protected function processImages()
	{
		$fields = array('integer:thumb_width', 'integer:thumb_height');

		// stores new fields and dimensions
		$data = array();

		$old_row = SwatDB::queryRowFromTable($this->app->db, 'Image',
			$fields, 'id', $this->id);

		if ($old_row !== null)
			foreach ($old_row as $field => $value)
				$data[$field] = $value;

		$validated = true;
		$changed = false;

		// this stores images that have been processed before they are saved
		$images = array();

		// this stores the temporary uploaded files that are deleted
		$delete_files = array();

		$class_map = StoreClassMap::instance();
		$category_image = $class_map->resolveClass('StoreCategoryImage');
		// name => max dimensions
		$sizes = call_user_func(array($category_image, 'getSizes'));

		// automatically resize images
		$image = $this->ui->getWidget('orig_image');
		if ($image->isUploaded()) {

			foreach ($sizes as $size => $dimensions) {
				$file = $this->ui->getWidget($size.'_image');
				if (!$file->isUploaded()) {

					$transformer = Image_Transform::factory('Imagick2');
					if (PEAR::isError($transformer))
						throw new AdminException($transformer);

					$transformer->load($image->getTempFileName());
					switch ($size) {
					case 'thumb':
						call_user_func(
							array($category_image, 'processThumbnail'),
							$transformer);

						break;
					}

					$images[$size] = $transformer;

					$data[$size.'_width'] = $transformer->new_x;
					$data[$size.'_height'] = $transformer->new_y;

					$changed = true;
				}
			}
			$delete_files[] = $image->getTempFileName();
		}

		// manually sized images
		foreach ($sizes as $size => $dimensions) {
			$file = $this->ui->getWidget($size.'_image');
			if ($file->isUploaded()) {
				$transformer = Image_Transform::factory('Imagick2');
				$transformer->load($file->getTempFileName());

				$images[$size] = $transformer;

				// check for invalid dimensions here
				if ($size == 'thumb' &&
					($transformer->img_x != $dimensions[0] ||
					$transformer->img_y != $dimensions[1])) {

					$validated = false;
					$msg = new SwatMessage(sprintf(
						Store::_('The %%s must be %1$s × %2$s pixels.'),
						$dimensions[0], $dimensions[1]), SwatMessage::ERROR);

					$file->addMessage($msg);
				} elseif ($transformer->img_x > $dimensions[0]) {
					$validated = false;
					$msg = new SwatMessage(sprintf(Store::_(
						'The %%s can be at most %1$s× pixels wide.'),
						$dimensions[0]), SwatMessage::ERROR);

					$file->addMessage($msg);
				} else {
					$data[$size.'_width'] = $transformer->img_x;
					$data[$size.'_height'] = $transformer->img_y;
				}

				$delete_files[] = $file->getTempFileName();

				$changed = true;
			}
		}

		// write to database
		if ($changed && $validated) {

			$transaction = new SwatDBTransaction($this->app->db);
			try {
				$old_image_files = $this->saveImages($fields, $data, $images,
					$sizes);

				// remove old images
				foreach ($old_image_files as $filename)
					if (file_exists($filename))
						unlink($filename);

			} catch (SwatDBException $e) {
				$transaction->rollback();

				$msg = new SwatMessage(Store::_('A database error has '.
					'occurred. The image was not changed.'),
					SwatMessage::SYSTEM_ERROR);

				$this->app->messages->add($msg);
				$e->process();

				$validated = true;
			}
			$transaction->commit();
		}

		// remove temporary images
		foreach ($delete_files as $filename)
			if (file_exists($filename))
				unlink($filename);

		return $validated;
	}

	// }}}
	// {{{ private function saveImages()

	/**
	 * Inserts new image data into the database and updates products
	 *
	 * @param 
	 *
	 * @return array image files that were replaced and should be deleted.
	 */
	private function saveImages($fields, $data, $images, $sizes)
	{
		$old_id = $this->id;
		$delete_files = array();

		// create a row for the new image
		$new_id = SwatDB::insertRow($this->app->db, 'Image', $fields, $data,
			'integer:id');

		// update the product to use the new image
		SwatDB::updateRow($this->app->db, 'Category', array('integer:image'),
			array('image' => $new_id), 'integer:id', $this->category_id);

		if ($old_id !== null) {
			$sql = 'delete from Image where id = %s';
			$sql = sprintf($sql, $this->app->db->quote($old_id, 'integer'));

			SwatDB::query($this->app->db, $sql);

			// delete old files
			foreach ($sizes as $size => $dimensions)
				$delete_files[] = '../images/categories/'.$size.'/'.$old_id.
					'.jpg';
		}

		// save images
		foreach ($sizes as $size => $width) {
			if (isset($images[$size])) {
				$transformer = $images[$size];
				$filename = '../images/categories/'.$size.'/'.$new_id.'.jpg';
				$transformer->save($filename, false,
					StoreImage::COMPRESSION_QUALITY);

				unset($transformer);
			} else {
				// move images sizes that were not uploaded
				$old_filename = '../images/categories/'.$size.'/'.$old_id.
					'.jpg';

				$new_filename = '../images/categories/'.$size.'/'.$new_id.
					'.jpg';

				if (file_exists($old_filename))
					rename($old_filename, $new_filename);
			}
		}

		$this->id = $new_id;

		return $delete_files;
	}

	// }}}
	// {{{ private function relocate()

	private function relocate()
	{
		$form = $this->ui->getWidget('edit_form');
		$url = $form->getHiddenField(self::RELOCATE_URL_FIELD);
		$this->app->relocate($url);
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		$form = $this->ui->getWidget('edit_form');
		$message = $this->ui->getWidget('message');

		$image = $this->ui->getWidget('orig_image');
		$thumb = $this->ui->getWidget('thumb_image');

		// if we're adding an image make sure enough images were uploaded
		// for all the sizes
		if ($this->id === null && !($image->isUploaded() || 
			$thumb->isUploaded())) {

			$msg = new SwatMessage(Store::_('You need to specify a thumbnail '.
				'image when creating a new image or upload an image to be '.
				'automatically resized.'), SwatMessage::ERROR);

			$message->add($msg);

			return false;
		}

		return !$form->hasMessage();
	}

	// }}}

	// build phase
	// {{{ protected buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildImage();
		$this->buildForm();
		$this->buildNavBar();

		$frame = $this->ui->getWidget('edit_frame');

		if ($this->id === null) {
			$frame->title = Store::_('Add Category Image for');

			// don't show image preview if we are adding an image
			$image = $this->ui->getWidget('image');
			$image->visible = false;
		}

		$frame->subtitle = $this->category_title;

		//set the notes on the manual image fields
		$class_map = StoreClassMap::instance();
		$category_image = $class_map->resolveClass('StoreCategoryImage');
		// name => max dimensions
		$sizes = call_user_func(array($category_image, 'getSizes'));

		$message = Store::_('Maximum Dimensions: %s px');
		$this->ui->getWidget('thumbnail_field')->note = sprintf($message,
			sprintf('%s × %s', $sizes['thumb'][0], $sizes['thumb'][1]));

		$this->buildMessages();
	}

	// }}}
	// {{{ protected function buildImage()

	protected function buildImage()
	{
		$fields = array(
			'integer:id',
			'integer:thumb_width',
			'integer:thumb_height',
		);

		$row = SwatDB::queryRowFromTable($this->app->db, 'Image', 
			$fields, 'id', $this->id);

		// the product references an undefined image
		if ($row === null) {
			$this->id = null;
		} else {
			$image = $this->ui->getWidget('image');
			$image->alt = sprintf(Store::_('Image of %s'),
				$this->category_title);

			$image->width = $row->thumb_width;
			$image->height = $row->thumb_height;
			$image->image = '../images/categories/thumb/'.$row->id.'.jpg';
		}
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('category', $this->category_id);

		if ($form->getHiddenField(self::RELOCATE_URL_FIELD) === null) {
			$url = $this->getRefererURL();
			$form->addHiddenField(self::RELOCATE_URL_FIELD, $url);
		}
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar() 
	{
		$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
			'getCategoryNavbar', array($this->category_id));

		foreach ($cat_navbar_rs as $entry)
			$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
				'Category/Index?id='.$entry->id));

		if ($this->id === null)
			$last_entry = new SwatNavBarEntry(Store::_('Add Category Image'));
		else
			$last_entry = new SwatNavBarEntry(
				Store::_('Change Category Image'));

		$this->navbar->addEntry($last_entry);
		$this->title = $this->category_title;
	}

	// }}}
}

?>
