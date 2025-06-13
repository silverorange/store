<?php

/**
 * Item alias object.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreItemAliasWrapper
 */
class StoreItemAlias extends SwatDBDataObject
{
    /**
     * unique id,.
     *
     * @var int
     */
    public $id;

    /**
     * not null,.
     *
     * @var string
     */
    public $sku;

    /**
     * Sets up this dataobject.
     */
    protected function init()
    {
        $this->registerInternalProperty(
            'item',
            SwatDBClassMap::get('StoreItem')
        );

        $this->table = 'ItemAlias';
        $this->id_field = 'integer:id';
    }
}
