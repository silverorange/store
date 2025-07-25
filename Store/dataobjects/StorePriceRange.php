<?php

/**
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRange extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Inclusive start of price range.
     *
     * @var ?int
     */
    public $start_price;

    /**
     * Inclusive end of price range.
     *
     * @var ?int
     */
    public $end_price;

    /**
     * Match the original price - don't take into account sale discount.
     */
    public bool $original_price = false;

    public function getShortname()
    {
        if ($this->start_price === null) {
            $shortname = sprintf('0-%s', $this->end_price);
        } elseif ($this->end_price === null) {
            $shortname = (string) $this->start_price;
        } else {
            $shortname = sprintf(
                '%s-%s',
                $this->start_price,
                $this->end_price
            );
        }

        if ($this->original_price) {
            $shortname = 'orig' . $shortname;
        }

        return $shortname;
    }

    public function getTitle()
    {
        $locale = SwatI18NLocale::get();

        if ($this->start_price === null || $this->start_price == 0) {
            $title = sprintf(
                Store::_('%s or less'),
                $locale->formatCurrency($this->end_price)
            );
        } elseif ($this->end_price === null) {
            $title = sprintf(
                Store::_('%s+'),
                $locale->formatCurrency($this->start_price)
            );
        } elseif ($this->start_price == $this->end_price) {
            $title = sprintf(
                Store::_('%s'),
                $locale->formatCurrency($this->start_price)
            );
        } else {
            $title = sprintf(
                Store::_('%s - %s'),
                $locale->formatCurrency($this->start_price),
                $locale->formatCurrency($this->end_price)
            );
        }

        return $title;
    }

    public function normalize()
    {
        $changed = false;

        if ($this->start_price > $this->end_price
            && $this->end_price !== null) {
            $temp = $this->start_price;
            $this->start_price = $this->end_price;
            $this->end_price = $temp;
            $changed = true;
        }

        return $changed;
    }

    protected function init()
    {
        $this->table = 'PriceRange';
        $this->id_field = 'integer:id';
    }

    protected function initFromRow($value)
    {
        if (is_string($value)) {
            if (mb_substr($value, 0, 4) == 'orig') {
                $price = mb_substr($value, 4);
                $original_price = true;
            } else {
                $price = $value;
                $original_price = false;
            }

            $price_parts = explode('-', $price);

            if (count($price_parts) === 1) {
                $this->start_price = intval($price_parts[0]);
                $this->original_price = $original_price;
            } elseif (count($price_parts) === 2) {
                $this->original_price = $original_price;
                $this->start_price = intval($price_parts[0]);
                $this->end_price = intval($price_parts[1]);
            }
        } else {
            parent::initFromRow($value);
        }
    }
}
