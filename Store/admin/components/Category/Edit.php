<?php

/**
 * Edit page for Categories
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var StoreCategory
	 */
	protected $category;

	// }}}
	// {{{ private properties

	private $parent;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());
		$this->parent = SiteApplication::initVar('parent');
		$this->initCategory();
	}

	// }}}
	// {{{ protected function initCategory()

	protected function initCategory()
	{
		$class_name = SwatDBClassMap::get('StoreCategory');
		$this->category = new $class_name();
		$this->category->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->category->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Store::_('Category with id “%s” not found.'),
						$this->id));
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate(): void
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('title')->value, $this->id);
			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname, $this->id)) {
			$message = new SwatMessage(
				Store::_('Shortname already exists and must be unique.'),
				'error');

			$this->ui->getWidget('shortname')->addMessage($message);
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

	protected function saveDBData(): void
	{
		$this->updateCategory();

		if ($this->id === null) {
			$date = new SwatDate();
			$date->toUTC();
			$this->category->createdate = $date->getDate();

			$this->category->parent = $this->ui->getWidget(
				'edit_form')->getHiddenField('parent');
		}

		$this->category->save();

		$message = new SwatMessage(sprintf(
			Store::_('“%s” has been saved.'),
			$this->category->title));

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}
	// {{{ protected function updateCategory()

	protected function updateCategory()
	{
		$this->category->title =
			$this->ui->getWidget('title')->value;

		$this->category->shortname =
			$this->ui->getWidget('shortname')->value;

		$this->category->description =
			$this->ui->getWidget('description')->value;

		$this->category->bodytext =
			$this->ui->getWidget('bodytext')->value;

		$this->category->always_visible =
			$this->ui->getWidget('always_visible')->value;
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
		$this->ui->setValues($this->category->getAttributes());
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
