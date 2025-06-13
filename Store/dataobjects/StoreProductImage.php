<?php

/**
 * An image data object for products.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImage extends StoreImage
{
    // {{{ public function getUri()

    public function getUri($shortname = 'large', $prefix = null)
    {
        return parent::getUri($shortname, $prefix);
    }

    // }}}
    // {{{ protected function init()

    protected function init()
    {
        parent::init();

        $this->image_set_shortname = 'products';
    }

    // }}}
}
