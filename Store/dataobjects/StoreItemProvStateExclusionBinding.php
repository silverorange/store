<?php

/**
 * Dataobject for item provstate exclusion bindings.
 *
 * @copyright 2012-2016 silverorange
 */
class StoreItemProvStateExclusionBinding extends SwatDBDataObject
{
    // {{{ protected function init()

    protected function init()
    {
        $this->registerInternalProperty(
            'provstate',
            SwatDBClassMap::get('StoreProvState')
        );

        $this->registerInternalProperty(
            'item',
            SwatDBClassMap::get('StoreItem')
        );

        $this->table = 'ItemProvStateExclusionBinding';
    }

    // }}}
}
