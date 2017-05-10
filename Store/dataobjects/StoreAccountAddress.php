<?php


/**
 * An address belonging to an account for an e-commerce web application
 *
 * @package   Store
 * @copyright 2005-2006 silverorane
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAddress
 */
class StoreAccountAddress extends StoreAddress
{
	// {{{ protected properties

	/**
	 * Creation date
	 *
	 * @var SwatDate
	 */
	protected $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'AccountAddress';

		$this->registerInternalProperty('account',
			SwatDBClassMap::get('StoreAccount'));

		$this->registerDateProperty('createdate');
	}

	// }}}
	// {{{ protected function getProtectedPropertyList()

	protected function getProtectedPropertyList()
	{
		return array_merge(
			parent::getProtectedPropertyList(),
			array(
				'createdate' => array(
					'get' => 'getCreateDate',
					'set' => 'setCreateDate',
				)
			)
		);
	}

	// }}}

	// getters
	// {{{ public function getCreateDate()

	public function getCreateDate()
	{
		return $this->createdate;
	}

	// }}}

	// setters
	// {{{ public function setCreateDate()

	public function setCreateDate(SwatDate $createdate)
	{
		$this->createdate = $createdate;
	}

	// }}}
}

?>
