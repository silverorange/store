<?php

/**
 * A special image display with tools for product images.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageDisplay extends SwatImageDisplay
{
    /**
     * Image Id.
     *
     * The image id for the product image.
     *
     * @var int
     */
    public $image_id;

    /**
     * Product Id.
     *
     * The product id for the product image.
     *
     * @var int
     */
    public $product_id;

    /**
     * Category Id.
     *
     * The category id for the product.
     *
     * @var int
     */
    public $category_id;

    /**
     * Whether or not to show the edit tool link.
     *
     * @var bool
     */
    public $show_edit_link = true;

    /**
     * Whether or not to show the delete tool link.
     *
     * @var bool
     */
    public $show_delete_link = true;

    /**
     * Displays this image.
     */
    public function display()
    {
        if (!$this->visible) {
            return;
        }

        $div_tag = new SwatHtmlTag('div');
        $div_tag->class = 'store-product-image-display';
        $div_tag->open();

        parent::display();

        if ($this->category_id === null) {
            $get_vars = sprintf('product=%s', $this->product_id);
        } else {
            $get_vars = sprintf(
                'product=%s&category=%s',
                $this->product_id,
                $this->category_id
            );
        }

        $toolbar = $this->getCompositeWidget('toolbar');
        $toolbar->setToolLinkValues(
            sprintf('%s&%s', $this->image_id, $get_vars)
        );

        $toolbar->display();

        $div_tag->close();
    }

    public function showEditLink($show)
    {
        $this->show_edit_link = $show;
    }

    public function showDeleteLink($show)
    {
        $this->show_delete_link = $show;
    }

    protected function createCompositeWidgets()
    {
        $toolbar = new SwatToolbar();

        $edit = new SwatToolLink();
        $edit->link = 'Product/ImageEdit?id=%s';
        $edit->setFromStock('edit');
        $edit->title = Store::_('Edit');
        $edit->visible = $this->show_edit_link;
        $toolbar->addChild($edit);

        $delete = new SwatToolLink();
        $delete->link = 'Product/ImageDelete?id=%s';
        $delete->setFromStock('delete');
        $delete->title = Store::_('Remove');
        $delete->visible = $this->show_delete_link;
        $toolbar->addChild($delete);

        $this->addCompositeWidget($toolbar, 'toolbar');
    }
}
