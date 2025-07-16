<?php

/**
 * An attribute type data object.
 *
 * @copyright 2008-2016 silverorange
 *
 * @property int                   $id
 * @property ?string               $shortname
 * @property StoreAttributeWrapper $attributes
 */
class StoreAttributeType extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Shortname.
     *
     * @var string
     */
    public $shortname;

    protected function init()
    {
        $this->table = 'AttributeType';
        $this->id_field = 'integer:id';
    }

    /**
     * Loads an attribute type by its shortname.
     *
     * @param string $shortname the shortname of the attribute type to load
     *
     * @return bool true if loading this attribute type was successful and
     *              false if an attribute type with the given shortname does
     *              not exist
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

    protected function loadAttributes()
    {
        $sql = sprintf(
            'select * from Attribute where attribute_type = %s',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreAttributeWrapper::class)
        );
    }
}
