<?php

/**
 * Abstract base class for a list of {@link StoreStatus} objects.
 *
 * Status lists are intended to provide an internal static collection of
 * defined statuses.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreStatus
 */
abstract class StoreStatusList extends SwatObject implements Iterator, Countable
{
    /**
     * Index value used to implement the iterator interface for this list.
     */
    private int $current_index = 0;

    /**
     * An array of statuses indexed by id.
     *
     * @var array
     */
    protected $statuses_by_id = [];

    /**
     * An array of statuses indexed by shortname.
     *
     * @var array
     */
    protected $statuses_by_shortname = [];

    /**
     * An array of statuses indexed numerically in the order they
     * were added.
     *
     * @var array
     */
    protected $statuses = [];

    /**
     * Gets a status by its id.
     *
     * @param int $id the id of the status to get
     *
     * @return StoreStatus the status with the given id
     *
     * @throws StoreException if no status with the specified id exists
     */
    public function getById($id)
    {
        if (!array_key_exists($id, $this->statuses_by_id)) {
            throw new StoreException(sprintf(
                "Status with id '%s' does not exist.",
                $id
            ));
        }

        return $this->statuses_by_id[$id];
    }

    /**
     * Gets an status by its shortname.
     *
     * @param stirng $shortname the shortname of the status to get
     *
     * @return StoreStatus the status with the given shortname id or null if no
     *                     no such status exists
     */
    public function getByShortname($shortname)
    {
        $status = null;
        if (array_key_exists($shortname, $this->statuses_by_shortname)) {
            $status = $this->statuses_by_shortname[$shortname];
        }

        return $status;
    }

    /**
     * Returns the current status.
     *
     * @return mixed the current status
     */
    public function current(): mixed
    {
        return $this->statuses[$this->current_index];
    }

    /**
     * Returns the key of the current status.
     *
     * @return int the key of the current status
     */
    public function key(): int
    {
        return $this->current_index;
    }

    /**
     * Moves forward to the next status.
     */
    public function next(): void
    {
        $this->current_index++;
    }

    /**
     * Moves forward to the previous status.
     */
    public function prev()
    {
        $this->current_index--;
    }

    /**
     * Rewinds this iterator to the first status.
     */
    public function rewind(): void
    {
        $this->current_index = 0;
    }

    /**
     * Checks is there is a current status after calls to rewind() and next().
     *
     * @return bool true if there is a current status and false if there
     *              is not
     */
    public function valid(): bool
    {
        return isset($this->statuses[$this->current_index]);
    }

    /**
     * Gets the number of statuses in this list.
     *
     * This satisfies the Countable interface.
     *
     * @return int the number of statuses in this list
     */
    public function count(): int
    {
        return count($this->statuses);
    }

    /**
     * Adds a status to this list.
     *
     * @param StoreStatus $status the status to add
     */
    protected function add(StoreStatus $status)
    {
        $this->statuses[] = $status;
        $this->statuses_by_id[$status->id] = $status;
        $this->statuses_by_shortname[$status->shortname] = $status;
    }

    /**
     * Gets an array of defined statuses for this class of list.
     *
     * Subclasses must override this method to define the default set of status
     * available to this list.
     *
     * @return array an array of {@link StoreStatus} objects representing all
     *               defined statuses for this class of list
     */
    abstract protected function getDefinedStatuses();

    protected function __construct()
    {
        foreach ($this->getDefinedStatuses() as $status) {
            $this->add($status);
        }
    }
}
