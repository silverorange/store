<?php

/**
 * @copyright 2008-2016 silverorange
 */
class StoreAttribute extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Internal name.
     *
     * @var string
     */
    public $shortname;

    /**
     * User visible title.
     *
     * @var string
     */
    public $title;

    /**
     * Order to display
     * not null default 0.
     *
     * @var int
     */
    public $displayorder;

    private static $attribute_type_cache = [];

    /**
     * Loads an attribute by its shortname.
     *
     * @param string $shortname the shortname of the attribute to load
     *
     * @return bool true if loading this attribute was successful and false
     *              if an attribute with the given shortname does not exist
     */
    public function loadFromShortname($shortname)
    {
        $this->checkDB();

        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    public function getSearchAnchorTag()
    {
        $anchor = new SwatHtmlTag('a');
        $anchor->title = sprintf(
            'Search for items with attribute %s…',
            $this->title
        );

        $anchor->href = sprintf('search?attr=%s', $this->shortname);

        return $anchor;
    }

    protected function init()
    {
        $this->table = 'Attribute';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'attribute_type',
            SwatDBClassMap::get(StoreAttributeType::class)
        );
    }

    protected function hasSubDataObject($key)
    {
        $found = parent::hasSubDataObject($key);

        if ($key === 'attribute_type' && !$found) {
            $attribute_type_id = $this->getInternalValue('attribute_type');

            if ($attribute_type_id !== null
                && array_key_exists($attribute_type_id, self::$attribute_type_cache)) {
                $this->setSubDataObject(
                    'attribute_type',
                    self::$attribute_type_cache[$attribute_type_id]
                );

                $found = true;
            }
        }

        return $found;
    }

    protected function setSubDataObject($name, $value)
    {
        if ($name === 'attribute_type') {
            self::$attribute_type_cache[$value->id] = $value;
        }

        parent::setSubDataObject($name, $value);
    }

    protected function getSerializableSubDataObjects()
    {
        return array_merge(
            parent::getSerializableSubDataObjects(),
            ['attribute_type']
        );
    }

    // display methods

    public function display($link_to_search = false)
    {
        if ($link_to_search) {
            $anchor = $this->getSearchAnchorTag();
            $span = new SwatHtmlTag('span');
            $span->setContent($this->title);
            $anchor->open();
            $span->display();
            $anchor->close();
        } else {
            echo SwatString::minimizeEntities($this->title);
        }
    }
}
