<?php

/**
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreBingShoppingGenerator extends StoreProductFileGenerator
{
    // {{{ constants

    public const DELIMITER = "\t";

    public const EOL = "\r\n";

    // }}}
    // {{{ public function generate()

    public function generate()
    {
        ob_start();

        $this->displayHeaderRow();

        foreach ($this->getItems() as $item) {
            $this->displayItemRow($item);
        }

        return ob_get_clean();
    }

    // }}}
    // {{{ protected function displayHeaderRow()

    protected function displayHeaderRow()
    {
        $headers = $this->getHeaders();
        $this->last_header = end($headers);

        foreach ($headers as $header) {
            $suffix_with_delimiter = ($header === $this->last_header) ?
                false : true;

            $this->printField($header, $suffix_with_delimiter);
        }

        echo self::EOL;
    }

    // }}}
    // {{{ protected function getHeaders()

    protected function getHeaders()
    {
        return [
            'MPID',
            'Title',
            'SKU',
            'ProductURL',
            'Price',
            'Availability',
            'Description',
            'ImageURL',
            'MerchantCategory',
            'B_Category',
        ];
    }

    // }}}
    // {{{ protected function displayItemRow()

    protected function displayItemRow(StoreItem $item)
    {
        foreach ($this->getHeaders() as $header) {
            $value = null;
            $suffix_with_delimiter = ($header === $this->last_header) ?
                false : true;

            switch ($header) {
                case 'MPID':
                    $value = $this->getId($item);
                    break;

                case 'Title':
                    $value = $this->getTitle($item);
                    break;

                case 'SKU':
                    $value = $this->getSku($item);
                    break;

                case 'ProductURL':
                    $value = $this->getUri($item);
                    break;

                case 'Price':
                    $value = $this->getPrice($item);
                    break;

                case 'Availability':
                    $value = $this->getAvailability($item);
                    break;

                case 'Description':
                    $value = $this->getDescription($item);
                    break;

                case 'ImageURL':
                    $value = $this->getImageUri($item);
                    break;

                case 'MerchantCategory':
                    $value = $this->getCategory($item);
                    break;

                case 'B_Category':
                    $value = $this->getBingCategory($item);
                    break;

                default:
                    throw new SiteException(sprintf(
                        'Row missing column ‘%s’',
                        $header
                    ));

                    break;
            }

            $this->printField($value, $suffix_with_delimiter);
        }

        echo self::EOL;
    }

    // }}}
    // {{{ protected function getId()

    protected function getId(StoreItem $item)
    {
        // ID needs to be distinct per row, so we can't use product.id, and
        // item.id could change over time.
        return $item->sku;
        // this is unnecessary - double check.
        /*
        if ($item->part_count > 1)
            $id.= '_part'.$item->part_count;
        */
    }

    // }}}
    // {{{ protected function getTitle()

    protected function getTitle(StoreItem $item)
    {
        return $item->product->title;
    }

    // }}}
    // {{{ protected function getSku()

    protected function getSku(StoreItem $item)
    {
        return $item->sku;
    }

    // }}}
    // {{{ protected function getUri()

    protected function getUri(StoreItem $item)
    {
        return $this->getBaseHref() . 'store/' . $item->product->path;
    }

    // }}}
    // {{{ protected function getPrice()

    protected function getPrice(StoreItem $item)
    {
        return round($item->getDisplayPrice(), 2);
    }

    // }}}
    // {{{ protected function getAvailability()

    protected function getAvailability(StoreItem $item)
    {
        $status = $item->getStatus();

        // normalize to Bing expected Values.
        switch ($status->shortname) {
            case 'available':
                $availability = 'In Stock';
                break;

            case 'outofstock':
                $availability = 'Out of Stock';
                break;

            default:
                throw new SiteException(sprintf(
                    'Bing availability string missing for status ‘%s’',
                    $status->shortname
                ));

                break;
        }

        return $availability;
    }

    // }}}
    // {{{ protected function getDescription()

    protected function getDescription(StoreItem $item)
    {
        return $item->getDescription();
    }

    // }}}
    // {{{ protected function getImageUri()

    protected function getImageUri(StoreItem $item)
    {
        $image = null;

        if ($item->product->primary_image !== null) {
            $image = $item->product->primary_image->getURI(
                'small',
                $this->getBaseHref()
            );
        }

        return $image;
    }

    // }}}
    // {{{ protected function getCategory()

    protected function getCategory(StoreItem $item)
    {
        $category = null;

        if ($item->product->primary_category !== null) {
            $category = $item->product->primary_category->title;
        }

        return $category;
    }

    // }}}
    // {{{ abstract protected function getBingCategory()

    abstract protected function getBingCategory(StoreItem $item);

    // }}}
    // {{{ protected function printField()

    protected function printField($value, $suffix_with_delimiter = true)
    {
        // escape any delimiters
        $value = str_replace(self::DELIMITER, ' ', $value);

        echo SwatString::stripXHTMLTags($value);

        if ($suffix_with_delimiter) {
            echo self::DELIMITER;
        }
    }

    // }}}
}
