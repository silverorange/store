<?php

/**
 * @copyright 2005-2016 silverorange
 */
class StoreCategoryPath extends SitePath
{
    public static $twig_product_threshold = 60;
    public static $twig_category_threshold = 5;

    /**
     * Creates a new category path object.
     *
     * @param SiteWebApplication $app the application this path exists in
     * @param int                $id  the database id of the object to create the path for.
     *                                If no database id is specified, an empty path is
     *                                created.
     */
    public function __construct(SiteWebApplication $app, $id = null)
    {
        if ($id !== null) {
            $this->loadFromId($app, $id);
        }
    }

    /**
     * Creates a new path object.
     *
     * @param int $category_id
     */
    public function loadFromId(SiteWebApplication $app, $category_id)
    {
        foreach ($this->queryPath($app, $category_id) as $row) {
            $this->addEntry(new StoreCategoryPathEntry(
                $row->id,
                $row->parent,
                $row->shortname,
                $row->title,
                $row->twig
            ));
        }
    }

    protected function queryPath(StoreApplication $app, $category_id)
    {
        $sql = sprintf(
            'select * from getCategoryPathInfo(%s, %s, %s)',
            $app->db->quote($category_id, 'integer'),
            $app->db->quote(self::$twig_product_threshold, 'integer'),
            $app->db->quote(self::$twig_category_threshold, 'integer')
        );

        return SwatDB::query($app->db, $sql);
    }
}
