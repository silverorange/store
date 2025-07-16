<?php

/**
 * @copyright 2008-2016 silverorange
 */
class StoreCategoryPathEntry extends SitePathEntry
{
    public $twig;

    /**
     * Creates a new category path entry.
     *
     * @param int    $id        the database id of this entry
     * @param int    $parent    the database id of the parent of this entry or
     *                          null if this entry does not have a parent
     * @param string $shortname the shortname of this entry
     * @param string $title     the title of this entry
     * @param bool   $twig      whether this is a twig category
     */
    public function __construct($id, $parent, $shortname, $title, $twig)
    {
        parent::__construct($id, $parent, $shortname, $title);
        $this->twig = $twig;
    }
}
