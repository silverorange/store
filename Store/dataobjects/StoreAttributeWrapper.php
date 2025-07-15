<?php

/**
 * A recordset wrapper class for StoreAttribute objects.
 *
 * @copyright 2008-2016 silverorange
 *
 * @see       StoreAttribute
 */
class StoreAttributeWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(StoreAttribute::class);
        $this->index_field = 'id';
    }

    public function getByType($shortname)
    {
        $attributes = new StoreAttributeWrapper();

        foreach ($this as $attribute) {
            if ($attribute->attribute_type->shortname === $shortname) {
                $attributes->add($attribute);
            }
        }

        return $attributes;
    }

    // display methods

    public function display($link_to_search = false)
    {
        if (count($this)) {
            echo '<ul class="attributes">';

            foreach ($this as $attribute) {
                echo '<li>';
                $attribute->display($link_to_search);
                echo '</li>';
            }

            echo '</ul>';
        }
    }

    public function displayInline($link_to_search = false)
    {
        $count = count($this);
        if ($count > 0) {
            echo '<span class="tag-attributes">';
            $first = true;

            foreach ($this as $attribute) {
                if ($first) {
                    $first = false;
                } else {
                    echo ($count === 2) ? ' &amp; ' : ', ';
                }

                $attribute->display($link_to_search);
            }

            echo '</span>';
        }
    }
}
