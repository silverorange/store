<?php

/**
 * Edit page for product images.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageEdit extends AdminDBEdit
{
    // {{{ protected properties

    protected $id;

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
     * @var int
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

        $this->ui->loadFromXML($this->getUiXml());

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
                sprintf('Product with id ‘%s’ not found.', $product_id)
            );
        }
    }

    // }}}
    // {{{ protected function initImage()

    protected function initImage()
    {
        $this->image = $this->getNewImageInstance();

        if ($this->id !== null && !$this->image->load($this->id)) {
            throw new AdminNotFoundException(
                sprintf('Product image with id ‘%s’ not found.', $this->id)
            );
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
            $form_field = new SwatFormField();
            $form_field->title = $dimension->title;

            $width = $dimension->max_width;
            $height = $dimension->max_height;
            if ($height !== null || $width !== null) {
                if ($height !== null && $width !== null) {
                    $dimension_text = sprintf('%s x %s', $width, $height);
                } elseif ($width === null) {
                    $dimension_text = $height;
                } elseif ($height === null) {
                    $dimension_text = $width;
                }
                $form_field->note = sprintf($note, $dimension_text);
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
        $class_name = SwatDBClassMap::get('StoreProductImage');
        $image = new $class_name();
        $image->setDatabase($this->app->db);

        return $image;
    }

    // }}}
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/image-edit.xml';
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
    protected function validate(): void
    {
        $automatic = $this->ui->getWidget('original_image');
        if ($automatic->isUploaded()) {
            return;
        }
        if ($this->id === null && !$this->checkManualUploads()) {
            $message = new SwatMessage(
                Store::_('You need to specify all ' .
                'image sizes when creating a new image or upload an image to ' .
                'be automatically resized.'),
                'error'
            );

            $this->ui->getWidget('message')->add($message);
            $valid = false;
        }
    }

    // }}}
    // {{{ protected function chackManualUploads()

    protected function checkManualUploads()
    {
        $uploaded = true;
        foreach ($this->dimensions as $dimension) {
            $uploaded = $uploaded
                && $this->dimension_files[$dimension->shortname]->isUploaded();
        }

        return $uploaded;
    }

    // }}}
    // {{{ protected function saveDBData()

    protected function saveDBData(): void
    {
        $this->processImage();
        $values = $this->ui->getValues(['title', 'border', 'description']);

        $this->image->title = $values['title'];
        $this->image->border = $values['border'];
        $this->image->description = $values['description'];

        $this->image->save();

        if ($this->id != $this->image->id) {
            $sql = sprintf(
                'insert into ProductImageBinding
				(product, image) values (%s, %s)',
                $this->app->db->quote($this->product->id, 'integer'),
                $this->app->db->quote($this->image->id, 'integer')
            );

            SwatDB::exec($this->app->db, $sql);
        }

        $message = new SwatMessage(Store::_('Product Image has been saved.'));
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
                $this->image->processManual(
                    $file->getTempFileName(),
                    $dimension->shortname
                );
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

        if ($this->id === null) {
            $frame->title = Store::_('Add Product Image for');
        } else {
            $this->ui->getWidget('image')->visible = true;
        }

        $form = $this->ui->getWidget('edit_form');
        $form->addHiddenField('product', $this->product->id);
        $form->addHiddenField('id', $this->id);
        $form->addHiddenField('category', $this->category_id);
    }

    // }}}
    // {{{ protected function loadDBData()

    protected function loadDBData()
    {
        $this->ui->setValues($this->image->getAttributes());

        $image = $this->ui->getWidget('image');
        $image->image = $this->image->getUri('thumb', '../');
        $image->width = $this->image->getWidth('thumb');
        $image->height = $this->image->getHeight('thumb');
        $image->preview_image = $this->image->getUri('large', '../');
        $image->preview_width = $this->image->getWidth('large');
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
                Store::_('Product Categories'),
                'Category'
            ));

            $cat_navbar_rs = SwatDB::executeStoredProc(
                $this->app->db,
                'getCategoryNavbar',
                [$this->category_id]
            );

            foreach ($cat_navbar_rs as $entry) {
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $entry->title,
                    'Category/Index?id=' . $entry->id
                ));
            }
        }

        if ($this->category_id === null) {
            $link = sprintf('Product/Details?id=%s', $this->product->id);
        } else {
            $link = sprintf(
                'Product/Details?id=%s&category=%s',
                $this->product->id,
                $this->category_id
            );
        }

        $this->navbar->addEntry(new SwatNavBarEntry(
            $this->product->title,
            $link
        ));

        if ($this->id === null) {
            $last_entry = new SwatNavBarEntry(Store::_('Add Product Image'));
        } else {
            $last_entry = new SwatNavBarEntry(Store::_('Change Product Image'));
        }

        $this->navbar->addEntry($last_entry);
        $this->title = $this->product->title;
    }

    // }}}
}
