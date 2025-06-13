<?php

/**
 * An image data object.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreImage extends SiteImage
{
    // {{{ public properties

    /**
     * Whether to display with a border.
     *
     * @var int
     */
    public $border;

    /**
     * For loading primary images which are 1-to-1 with products.
     *
     * @var int
     */
    public $product;

    // }}}
    // {{{ public function hasOriginal()

    /**
     * Whether dimension exists for this image.
     *
     * @deprecated use {@link SiteImage::hasDimension()} instead
     */
    public function hasOriginal()
    {
        return false;
    }

    // }}}
    // {{{ public function getImgTag()

    public function getImgTag($shortname, $prefix = null)
    {
        $img_tag = parent::getImgTag($shortname, $prefix);

        $img_tag->class = $this->border ?
            'store-border-on' : 'store-border-off';

        return $img_tag;
    }

    // }}}
}
