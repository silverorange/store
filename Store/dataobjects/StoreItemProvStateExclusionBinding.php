<?php

/**
 * Dataobject for item provstate exclusion bindings.
 *
 * @copyright 2012-2016 silverorange
 *
 * @property StoreProvState $provstate
 * @property StoreItem      $item
 */
class StoreItemProvStateExclusionBinding extends SwatDBDataObject
{
    protected function init()
    {
        $this->registerInternalProperty(
            'provstate',
            SwatDBClassMap::get(StoreProvState::class)
        );

        $this->registerInternalProperty(
            'item',
            SwatDBClassMap::get(StoreItem::class)
        );

        $this->table = 'ItemProvStateExclusionBinding';
    }
}
