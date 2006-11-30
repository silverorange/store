<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Date.php';
require_once 'Image/Transform.php';
require_once 'Store/dataobjects/StoreProductImage.php';
require_once 'Store/StoreClassMap.php';

/**
 * Edit page for product images
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageEdit extends AdminPage
{
	// {{{ private properties

	private $id;
	private $product_id;
	private $product_title;
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML('Store/admin/components/Product/imageedit.xml');

		$this->id = $this->app->initVar('id');
		$this->product_id = $this->app->initVar('product');
		$this->category_id = SiteApplication::initVar('category');

		$sql = 'select title from Product where id = %s';
		$sql = sprintf($sql, 
			$this->app->db->quote($this->product_id, 'integer'));

		$row = SwatDB::queryRow($this->app->db, $sql);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('There is no image associated with product ‘%d’.'),
				$this->product_id));

		$this->product_title = $row->title;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('edit_form');

		if ($form->isProcessed()) {
			$message_text = Store::_('There is a problem with the one of the '.
				'files submitted below.');

			if (!$this->validate()) {
				$message = new SwatMessage($message_text, SwatMessage::ERROR);
				$this->app->messages->add($message);
			} else {
				if ($this->processImages()) {
					$this->saveDBData();
					$this->relocate();
				} else {
					$message = new SwatMessage($message_text,
						SwatMessage::ERROR);

					$this->app->messages->add($message);
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
		$fields = array(
			'integer:thumb_width', 'integer:thumb_height',
			'integer:small_width', 'integer:small_height',
			'integer:large_width', 'integer:large_height',
		);

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
		$product_image = $class_map->resolveClass('StoreProductImage');
		// name => max dimensions
		$sizes = call_user_func(array($product_image, 'getSizes'));

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
							array($product_image, 'processThumbnail'),
							$transformer);

						break;
					case 'small':
						call_user_func(
							array($product_image, 'processSmall'),
							$transformer);

						break;
					case 'large':
						call_user_func(
							array($product_image, 'processLarge'),
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
					$message = new SwatMessage(sprintf(
						Store::_('The %%s must be %1$s × %2$s pixels.'),
						$dimensions[0], $dimensions[1]), SwatMessage::ERROR);

					$file->addMessage($message);
				} elseif ($transformer->img_x > $dimensions[0]) {
					$validated = false;

					$message = new SwatMessage(sprintf(Store::_(
						'The %%s can be at most %1$s× pixels wide.'),
						$dimensions[0]), SwatMessage::ERROR);

					$file->addMessage($message);
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

				$message = new SwatMessage(Store::_('A database error has '.
					'occurred. The image was not changed.'),
					SwatMessage::SYSTEM_ERROR);

				$this->app->messages->add($message);
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
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$fields = array('text:title', 'boolean:border');
		$values = array(
			'title' => $this->ui->getWidget('title')->value,
			'border' => $this->ui->getWidget('border')->value
			);

		SwatDB::updateRow($this->app->db, 'Image', $fields, $values,
			'id', $this->id);

		$message = new SwatMessage(Store::_('Image has been saved.'));

		$this->app->messages->add($message);
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
		$new_id = SwatDB::insertRow($this->app->db, 'Image',
			$fields, $data, 'integer:id');

		// bind the new image to the product
		if ($old_id === null) {
			$sql = sprintf('select max(displayorder) from ProductImageBinding
					where product = %s',
					$this->app->db->quote($this->product_id, 'integer'));
			$displayorder = SwatDB::queryOne($this->app->db, $sql);
			$displayorder = ($displayorder === null) ? 0 : $displayorder + 1;

			$fields = array('integer:product',
					'integer:image',
					'integer:displayorder');

			$values = array('product' => $this->product_id,
					'image' => $new_id,
					'displayorder' => $displayorder);

			SwatDB::insertRow($this->app->db, 'ProductImageBinding',
				$fields, $values);
		} else {
			$sql = sprintf('update ProductImageBinding set image = %s
				where image = %s and product = %s',
				$this->app->db->quote($new_id, 'integer'),
				$this->app->db->quote($old_id, 'integer'),
				$this->app->db->quote($this->product_id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		}

		// no more products reference the image so delete it
		if ($old_id !== null) {
			$sql = 'select count(product) from ProductImageBinding
				where image = %s';
			$sql = sprintf($sql,
				$this->app->db->quote($old_id, 'integer'));

			$old_image_products = SwatDB::queryOne($this->app->db, $sql);

			if ($old_image_products == 0) {
				$sql = 'delete from Image where id = %s';
				$sql = sprintf($sql,
					$this->app->db->quote($old_id, 'integer'));

				SwatDB::query($this->app->db, $sql);

				// delete old files
				foreach ($sizes as $size => $dimensions)
					$delete_files[] = '../images/products/'.$size.'/'.
						$old_id.'.jpg';
			}
		}
			
		// save images
		foreach ($sizes as $size => $dimensions) {
			if (isset($images[$size])) {
				$transformer = $images[$size];
				$filename = '../images/products/'.$size.'/'.$new_id.'.jpg';
				$transformer->save($filename, false,
					StoreImage::COMPRESSION_QUALITY);

				unset($transformer);
			} else {
				// move images sizes that were not uploaded
				$old_filename = '../images/products/'.$size.'/'.$old_id.'.jpg';
				$new_filename = '../images/products/'.$size.'/'.$new_id.'.jpg';
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
		$small = $this->ui->getWidget('small_image');
		$large = $this->ui->getWidget('large_image');

		// if we're adding an image make sure enough images were uploaded
		// for all the sizes
		if ($this->id === null && !($image->isUploaded() || 
			($thumb->isUploaded() && $small->isUploaded() &&
			$large->isUploaded()))) {

			$message = new SwatMessage(Store::_('You need to specify all '.
				'image sizes when creating a new image or upload an image to '.
				'be automatically resized.'), SwatMessage::ERROR);

			$message->add($message);

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

		if ($this->id !== null)
			$this->loadDBData();

		$this->buildImage();
		$this->buildForm();
		$this->buildNavBar();

		$frame = $this->ui->getWidget('edit_frame');

		if ($this->id === null) {
			$frame->title = Store::_('Add Product Image for');

			// don't show image preview if we are adding an image
			$image = $this->ui->getWidget('image');
			$image->visible = false;
		}

		$frame->subtitle = $this->product_title;

		//set the notes on the manual image fields
		$class_map = StoreClassMap::instance();
		$product_image = $class_map->resolveClass('StoreProductImage');
		// name => max dimensions
		$sizes = call_user_func(array($product_image, 'getSizes'));

		//set the notes on the manual image fields
		$message = Store::_('Maximum Dimensions: %s px');
		$this->ui->getWidget('thumbnail_field')->note = sprintf($message,
			sprintf('%s × %s', $sizes['thumb'][0], $sizes['thumb'][1]));

		$this->ui->getWidget('small_field')->note = sprintf($message,
			$sizes['small'][0]);

		$this->ui->getWidget('large_field')->note = sprintf($message,
			$sizes['large'][0]);

		$this->buildMessages();
	}

	// }}}
	// {{{ protected function buildImage()

	protected function buildImage()
	{
		$fields = array('integer:id',
			'integer:thumb_width', 'integer:thumb_height',
			'integer:small_width', 'integer:small_height',
			'integer:large_width', 'integer:large_height');

		$row = SwatDB::queryRowFromTable($this->app->db, 'Image', 
			$fields, 'id', $this->id);

		// the product references an undefined image
		if ($row === null) {
			$this->id = null;
		} else {
			$image = $this->ui->getWidget('image');
			$image->alt = $this->product_title;

			$image->width = $row->thumb_width;
			$image->height = $row->thumb_height;
			$image->image = '../images/products/thumb/'.$row->id.'.jpg';

			$image->small_width = $row->small_width;
			$image->small_height = $row->small_height;
			$image->small_image = '../images/products/small/'.$row->id.'.jpg';

			$image->large_width = $row->large_width;
			$image->large_height = $row->large_height;
		}
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;
		$form->addHiddenField('product', $this->product_id);
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('category', $this->category_id);

		if ($form->getHiddenField(self::RELOCATE_URL_FIELD) === null) {
			$url = $this->getRefererURL();
			$form->addHiddenField(self::RELOCATE_URL_FIELD, $url);
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$sql = 'select title, border from Image
			inner join ProductImageBinding on image = id
			where id = %s and product = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote($this->product_id, 'integer'));

		$row = SwatDB::queryRow($this->app->db, $sql);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('An image with an id of ‘%d’ does not exist.'),
				$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar() 
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

		$this->navbar->addEntry(new SwatNavBarEntry($this->product_title, 
			$link));

		if ($this->id === null)
			$last_entry = new SwatNavBarEntry(Store::_('Add Product Image'));
		else
			$last_entry = new SwatNavBarEntry(Store::_('Change Product Image'));

		$this->navbar->addEntry($last_entry);
		$this->title = $this->product_title;
	}

	// }}}
}

?>
