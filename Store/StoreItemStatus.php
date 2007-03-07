<?php

require_once 'Swat/SwatObject.php';
require_once 'Store/StoreItemStatusList.php';

/**
 * A possible item status
 *
 * Item statuses are all defined in
 * {@link StoreItemStatusList::getDefinedStatuses()} or in a subclass of
 * StoreItemStatusList.
 *
 * Making item statuses objects instead of simple constants has a few
 * advantages that are not immediately obvious:
 *
 * First, it is easier to define a base set of statuses in the Store package
 * and have site code provide additional statuses by extending the
 * {@link StoreItemStatusList} class. Alternatives to this technique are
 * having no statuses defined in Store, resulting in unecessarily abstract
 * objects, and having a mix of constant namespaces
 * (StoreItem::STATUS_AVAILABLE, MyItem::STATUS_MYSTATUS).
 *
 * A second advantage is that information about an item's statuses can easily
 * be retrieved from the item. It is easy to get the textual description
 * of an item status, for example:
 *
 * <code>
 * echo $item->getStatus()->title;
 * </code>
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItem::getStatus()
 * @see       StoreItem::setStatus()
 * @see       StoreItemStatusList
 */
class StoreItemStatus extends SwatObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title of this item status
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Mnemonic shortname used primarily when assigning item statuses to items
	 *
	 * @var string
	 */
	public $shortname;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new item status object
	 *
	 * @param integer $id
	 * @param string shortname
	 * @param string $title
	 */
	public function __construct($id, $shortname, $title)
	{
		$this->id = (integer)$id;
		$this->shortname = (string)$shortname;
		$this->title = (string)$title;
	}

	// }}}
	// {{{ public function compare()

	/**
	 * Compares this status to another status
	 *
	 * @param StoreItemStatus $status the status to compare this status to.
	 *
	 * @return integer a tri value where -1 indicates this status is less than
	 *                  the given status, 0 indicates this status is equivalent
	 *                  to the given status and 1 indicates this status is
	 *                  greater than the given status.
	 */
	public function compare(StoreItemStatus $status)
	{
		$value = 0;

		if ($this->id < $status->id)
			$value = -1;
		elseif ($this->id > $status->id)
			$value = 1;

		return $value;
	}

	// }}}
	// {{{ public function isEqual()

	/**
	 * Whether or not this status is equal to another status
	 *
	 * Use this method instead of the equality or equivalence operators when
	 * comparing item statuses.
	 *
	 * @param StoreItemStatus $status the status to compare this status to.
	 *
	 * @return boolean true if this status is equal to the given status and
	 *                  false if it is not.
	 */
	public function isEqual(StoreItemStatus $status)
	{
		return ($this->compare($status) == 0);
	}

	// }}}
}

?>
