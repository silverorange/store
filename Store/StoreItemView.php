<?php

/**
 * A viewer for items for an e-commerce web application
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemView
{
	private $item;

	public function __construct($item);

	public function display();

	public function getItem();
	public function setItem($item);
}

?>
