<?php

require_once 'Swat/SwatObject.php';
require_once 'Store/StoreStatus.php';

/**
 * Abstract base class for a list of {@link StoreStatus} objects
 *
 * Status lists are intended to provide an internal static collection of
 * defined statuses.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreStatus
 */
abstract class StoreStatusList extends SwatObject implements Iterator, Countable
{
	// {{{ private properties

	/**
	 * Index value used to implement the iterator interface for this list
	 *
	 * @var integer
	 */
	private $current_index = 0;

	// }}}
	// {{{ protected properties

	/**
	 * An array of statuses indexed by id 
	 *
	 * @var array
	 */
	protected $statuses_by_id = array();

	/**
	 * An array of statuses indexed by shortname
	 *
	 * @var array
	 */
	protected $statuses_by_shortname = array();

	/**
	 * An array of statuses indexed numerically in the order they
	 * were added
	 *
	 * @var array
	 */
	protected $statuses = array();

	// }}}
	// {{{ public function getById()

	/**
	 * Gets a status by its id
	 *
	 * @param integer $id the id of the status to get.
	 *
	 * @return StoreStatus the status with the given id or null if no such
	 *                      status exists.
	 */
	public function getById($id)
	{
		$status = null;
		if (array_key_exists($id, $this->statuses_by_id))
			$status = $this->statuses_by_id[$id];

		return $status;
	}

	// }}}
	// {{{ public function getByShortname()

	/**
	 * Gets an status by its shortname 
	 *
	 * @param stirng $shortname the shortname of the status to get.
	 *
	 * @return StoreStatus the status with the given shortname id or null if no
	 *                      no such status exists.
	 */
	public function getByShortname($shortname)
	{
		$status = null;
		if (array_key_exists($shortname, $this->statuses_by_shortname))
			$status = $this->statuses_by_shortname[$shortname];

		return $status;
	}

	// }}}
	// {{{ public final function current()

	/**
	 * Returns the current status
	 *
	 * @return mixed the current status.
	 */
	public final function current()
	{
		return $this->statuses[$this->current_index];
	}

	// }}}
	// {{{ public final function key()

	/**
	 * Returns the key of the current status
	 *
	 * @return integer the key of the current status
	 */
	public final function key()
	{
		return $this->current_index;
	}

	// }}}
	// {{{ public final function next()

	/**
	 * Moves forward to the next status
	 */
	public final function next()
	{
		$this->current_index++;
	}

	// }}}
	// {{{ public final function prev()

	/**
	 * Moves forward to the previous status
	 */
	public final function prev()
	{
		$this->current_index--;
	}

	// }}}
	// {{{ public final function rewind()

	/**
	 * Rewinds this iterator to the first status
	 */
	public final function rewind()
	{
		$this->current_index = 0;
	}

	// }}}
	// {{{ public final function valid()

	/**
	 * Checks is there is a current status after calls to rewind() and next()
	 *
	 * @return boolean true if there is a current status and false if there
	 *                  is not.
	 */
	public final function valid()
	{
		return isset($this->statuses[$this->current_index]);
	}

	// }}}
	// {{{ public function count()

	/**
	 * Gets the number of statuses in this list
	 *
	 * This satisfies the Countable interface.
	 *
	 * @return integer the number of statuses in this list.
	 */
	public function count()
	{
		return count($this->statuses);
	}

	// }}}
	// {{{ protected function add()

	/**
	 * Adds a status to this list
	 *
	 * @param StoreStatus $status the status to add.
	 */
	protected function add(StoreStatus $status)
	{
		$this->statuses[] = $status;
		$this->statuses_by_id[$status->id] = $status;
		$this->statuses_by_shortname[$status->shortname] = $status;
	}

	// }}}
	// {{{ abstract protected function getDefinedStatuses()

	/**
	 * Gets an array of defined statuses for this class of list
	 *
	 * Subclasses must override this method to define the default set of status
	 * available to this list.
	 *
	 * @return array an array of {@link StoreStatus} objects representing all
	 *                defined statuses for this class of list.
	 */
	abstract protected function getDefinedStatuses();

	// }}}
	// {{{ protected function __construct()

	protected function __construct()
	{
		foreach ($this->getDefinedStatuses() as $status) {
			$this->add($status);
		}
	}

	// }}}
}

?>
