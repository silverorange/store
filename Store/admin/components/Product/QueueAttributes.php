<?php

require_once 'Admin/pages/AdminDBConfirmation.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Confirmation page for queuing future product attribute changes.
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductQueueAttributes extends AdminDBConfirmation
{
	// {{{ protected properties

	/**
	 * A db-quoted array of attribute id's.
	 *
	 * @var array
	 */
	protected $attributes;

	/**
	 * The action to queue
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * Optional id of the category page this was called from.
	 *
	 * @var integer
	 */
	protected $category;

	// }}}
	// {{{ public function setAction()

	public function setAction($action)
	{
		$this->action = $action;
	}

	// }}}
	// {{{ public function setAttributes()

	public function setAttributes($attributes)
	{
		$this->attributes = $attributes;
	}

	// }}}
	// {{{ public function setCategory()

	public function setCategory($category)
	{
		$this->category = $category;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui_xml = dirname(__FILE__).'/queue-attributes.xml';

		parent::initInternal();

		$form = $this->ui->getWidget('confirmation_form');
		$this->setAction($form->getHiddenField('action'));
		$this->setAttributes($form->getHiddenField('attributes'));
		$this->setCategory($form->getHiddenField('category'));

		// only allow dates in the future, and only a year out for sanity's sake
		$action_date = $this->ui->getWidget('action_date');
		$action_date->setValidRange(0,1);
		$action_date->valid_range_start = new SwatDate();
		$action_date->valid_range_start->convertTZ(
			$this->app->default_time_zone);
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$action_date = $this->ui->getWidget('action_date')->value;
		$action_date->setTZ($this->app->default_time_zone);

		// build message first to avoid converting the date to UTC and then back
		$message = new SwatMessage(sprintf(Store::ngettext(
			'One product will have %2$s %3$s %4$s on %5$s at %6$s %7$s.',
			'%1$s products will have %2$s %3$s %4$s on %5$s at %6$s %7$s.',
				count($this->items)),
			SwatString::numberFormat(count($this->items)),
			SwatString::numberFormat(count($this->attributes)),
			Store::ngettext('attribute', 'attributes',
				count($this->attributes)),
			($this->action == 'add') ? Store::_('added') : Store::_('removed'),
			$action_date->formatLikeIntl(SwatDate::DF_DATE),
			$action_date->formatLikeIntl(SwatDate::DF_TIME),
			$action_date->formatTZ(SwatDate::TZ_CURRENT_SHORT)));

		$action_date->toUTC();

		$sql = sprintf('insert into ProductAttributeBindingQueue
			(product, attribute, queue_action, action_date)
			select Product.id, Attribute.id, %s, %s
			from Product cross join Attribute
			where Product.id in (%s) and Attribute.id in (%s)',
			$this->app->db->quote($this->action, 'text'),
			$this->app->db->quote($action_date, 'date'),
			$this->getItemList(),
			implode(',', $this->attributes));

		SwatDB::exec($this->app->db, $sql);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{  protected function relocate()

	protected function relocate()
	{
		if ($this->category === null) {
			$this->app->relocate('Product');
		} else {
			$this->app->relocate('Category/Index?id='.$this->category);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('action', $this->action);
		$form->addHiddenField('attributes', $this->attributes);
		$form->addHiddenField('category', $this->category);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $this->getConfirmationMessage();
		$message->content_type = 'text/xml';

		$item_list = $this->getItemList('integer');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->popEntry();

		if ($this->category !== null) {
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category));

			foreach ($cat_navbar_rs as $entry) {
				$this->title = $entry->title;
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
			}
		}

		$this->navbar->addEntry(new SwatNavBarEntry($this->getTitle()));
	}

	// }}}
	// {{{ protected function getConfirmationMessage()

	protected function getConfirmationMessage()
	{
		ob_start();
		printf('<h3>%s</h3>', $this->getTitle());

		// You unfortunately can't nest ngettext calls. Nor does there appear to
		// be a better way to do a sentence with multiple plural options.
		if (count($this->attributes) == 1) {
			$message = Store::ngettext(
				'The attribute <em>%s</em> will be %s the following product:',
				'The attribute <em>%s</em> will be %s the following products:',
				count($this->items));
		} else {
			$message = Store::ngettext(
				'The attributes <em>%s</em> will be %s the following product:',
				'The attributes <em>%s</em> will be %s the following products:',
				count($this->items));
		}

		$message = sprintf('<p>%s</p>', $message);
		printf($message,
			implode(', ', $this->getAttributeTitles()),
			($this->action == 'add') ? Store::_('added to') :
				Store::_('removed from'));

		echo '<ul>';
		foreach ($this->getProductTitles() as $id => $title) {
			printf('<li>%s</li>', $title);
		}
		echo '</ul>';
		return ob_get_clean();
	}

	// }}}
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		if ($this->action == 'add') {
			$title = Store::_('Queue Product Attribute Addition');
		} else {
			$title = Store::_('Queue Product Attribute Removal');
		}

		return $title;
	}

	// }}}
	// {{{ protected function getAttributeTitles()

	protected function getAttributeTitles()
	{
		$where_clause = sprintf('id in (%s)',
			implode(',', $this->attributes));

		return SwatDB::getOptionArray($this->app->db, 'attribute', 'title',
			'id', 'title', $where_clause);
	}

	// }}}
	// {{{ protected function getProductTitles()

	protected function getProductTitles()
	{
		$where_clause = sprintf('id in (%s)',
			$this->getItemList());

		return SwatDB::getOptionArray($this->app->db, 'product', 'title',
			'id', 'title', $where_clause);
	}

	// }}}
}

?>
