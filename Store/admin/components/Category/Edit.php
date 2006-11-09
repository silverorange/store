<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Date.php';

/**
 * Edit page for Categories
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryEdit extends AdminDBEdit
{
	// {{{ private properties

	protected $ui_xml = 'Store/admin/components/Category/edit.xml';
	protected $fields;

	// }}}
	// {{{ private properties

	private $parent;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->fields = array('title', 'shortname', 'bodytext');

		$this->parent = SiteApplication::initVar('parent');
	}

	// }}}
	
	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('title')->value, $this->id);
			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname, $this->id)) {
			$msg = new SwatMessage(
				Store::_('Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($msg);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from Category
				where shortname = %s and parent %s %s and id %s %s';

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($this->parent, false),
			$this->app->db->quote($this->parent, 'integer'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();
		
		if ($this->id === null) {
			$this->fields[] = 'date:createdate';
			$date = new Date();
			$date->toUTC();
			$values['createdate'] = $date->getDate();
			
			$this->fields[] = 'integer:parent';
			$values['parent'] = 
				$this->ui->getWidget('edit_form')->getHiddenField('parent');
			
			$this->id = SwatDB::insertRow($this->app->db, 'Category',
				$this->fields, $values, 'integer:id');
		} else {
			SwatDB::updateRow($this->app->db, 'Category', $this->fields,
				$values, 'integer:id', $this->id);
		}

		$this->addToSearchQueue();

		$msg = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$values['title']));

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('title', 'shortname', 'bodytext'));
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote(3, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote(3, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('parent', $this->parent);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Category',
			$this->fields, 'integer:id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Category with id ‘%s’ not found.'),
				$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$last_entry = $this->navbar->popEntry();
		$category_id = ($this->id === null) ? $this->parent : $this->id;

		if ($category_id !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getCategoryNavbar', array($category_id));
			
			foreach ($navbar_rs as $row)
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));

			$this->title = $row->title;
		}

		$this->navbar->addEntry($last_entry);
	}

	// }}}
}

?>
