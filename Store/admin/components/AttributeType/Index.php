<?php

/**
 * Index page for Attribute Types
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeTypeIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(__DIR__.'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('AttributeType/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view): ?SwatTableModel
	{
		$sql = 'select * from AttributeType order by shortname';

		$rs = SwatDB::query($this->app->db, $sql, StoreAttributeTypeWrapper::class);

		return $rs;
	}

	// }}}
}

?>
