<?php

require_once 'Swat/SwatObject.php';
require_once 'Store/Store.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/StoreItemStatus.php';

/**
 * A list of {@link StoreItemStatus} objects
 *
 * This list contains all available item statuses and has methods to get
 * patticular statuses.
 *
 * By default, two statuses are defined: 'available' and 'outofstock'. If
 * Site code needs additional statuses if should subclass this class and
 * add statuses in the {@link StoreItemStatusList::getDefinedStatuses()}
 * method.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemStatus
 */
class StoreItemStatusList extends SwatObject implements Iterator, Countable
{
	// {{{ private properties

	/**
	 * Static collection of available statuses for this class of status list 
	 *
	 * @var array
	 */
	private static $defined_statuses = null;

	/**
	 * Index value used to implement the iterator interface for this list
	 *
	 * @var integer
	 */
	private $current_index = 0;

	// }}}
	// {{{ protected properties

	/**
	 * An array of item statuses indexed by id 
	 *
	 * @var array
	 */
	protected $statuses_by_id = array();

	/**
	 * An array of item statuses indexed by shortname
	 *
	 * @var array
	 */
	protected $statuses_by_shortname = array();

	/**
	 * An array of item statuses indexed numerically in the order they
	 * were added
	 *
	 * @var array
	 */
	protected $statuses = array();

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		foreach ($this->getDefinedStatuses() as $status) {
			$this->add($status);
		}
	}

	// }}}
	// {{{ public static function status()

	/**
	 * Convenience function to get a status by shortname without having to
	 * create a list instance
	 *
	 * Example usage:
	 *
	 * <code>
	 * $item->status = StoreItemStatusList::status('available');
	 * </code>
	 *
	 * @param string $status_shortname the shortname of the status to retrieve.
	 *
	 * @return StoreItemStatus the item status corresponding to the shortname
	 *                          or null if no such status exists.
	 */
	public static function status($status_shortname)
	{
		$class_map = StoreClassMap::instance();
		$list_class = $class_map->resolveClass('StoreItemStatusList');
		$list = new $list_class();
		return $list->getByShortname($status_shortname);
	}

	// }}}
	// {{{ public function getById()

	/**
	 * Gets an item status by its id
	 *
	 * @param integer $id the id of the item status to get.
	 *
	 * @return StoreItemStatus the item status with the given id or null if no
	 *                          such item status exists.
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
	 * Gets an item status by its shortname 
	 *
	 * @param stirng $shortname the shortname of the item status to get.
	 *
	 * @return StoreItemStatus the item status with the given shortname id or
	 *                          null if no such item status exists.
	 */
	public function getByShortname($shortname)
	{
		$status = null;
		if (array_key_exists($shortname, $this->statuses_by_shortname))
			$status = $this->statuses_by_shortname[$shortname];

		return $status;
	}

	// }}}
	// {{{ public function current()

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
	// {{{ public function key()

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
	// {{{ public function next()

	/**
	 * Moves forward to the next status
	 */
	public final function next()
	{
		$this->current_index++;
	}

	// }}}
	// {{{ public function prev()

	/**
	 * Moves forward to the previous status
	 */
	public final function prev()
	{
		$this->current_index--;
	}

	// }}}
	// {{{ public function rewind()

	/**
	 * Rewinds this iterator to the first status
	 */
	public final function rewind()
	{
		$this->current_index = 0;
	}

	// }}}
	// {{{ public function valid()

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
	 * Adds an item status to this list
	 *
	 * @param StoreItemStatus $status the status to add.
	 */
	protected function add(StoreItemStatus $status)
	{
		$this->statuses[] = $status;
		$this->statuses_by_id[$status->id] = $status;
		$this->statuses_by_shortname[$status->shortname] = $status;
	}

	// }}}
	// {{{ protected function getDefinedStatuses()

	/**
	 * Gets an array of defined item statuses for this list class
	 *
	 * Subclasses are encoraged to override this method to change the default
	 * set of item statuses or to provide additional statuses.
	 *
	 * @return array an array of {@link StoreItemStatus} objects representing
	 *                all defined item statuses for this list class.
	 */
	protected function getDefinedStatuses()
	{
		if (self::$defined_statuses === null) {
			self::$defined_statuses = array();

			$class_map = StoreClassMap::instance();
			$status_class = $class_map->resolveClass('StoreItemStatus');

			$available_status =
				new $status_class(0, 'available', Store::_('Available'));

			$out_of_stock_status =
				new $status_class(1, 'outofstock', Store::_('Out of Stock'));

			self::$defined_statuses[] = $available_status;
			self::$defined_statuses[] = $out_of_stock_status;
		}

		return self::$defined_statuses;
	}

	// }}}
}

?>
