<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for Articles
 *
 * @package   veseys2
 * @copyright 2005-2006 silverorange
 */
class ArticleDelete extends AdminDBDelete
{
	// used for custom relocate
	private $parent_id;

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = sprintf('select parent from Article where id = %s',
			$this->app->db->quote($this->getFirstItem(), 'integer'));

		$this->parent_id = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'delete from Article where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One article has been deleted.', '%d articles have been deleted.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ protected function relocate()

	/**
	 * Relocate after process
	 */
	protected function relocate()
	{
		if ($this->single_delete) {
			if ($this->parent_id === null)
				$this->app->relocate('Article/Index');
			else
				$this->app->relocate(sprintf('Article/Index?id=%s', 
					$this->parent_id));
		} else {
			parent::relocate();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		
		$dep = new AdminListDependency();
		$dep->title = Store::_('article');
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Article', 'integer:id', null, 'text:title', 'title',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$this->getDependencies($dep, $item_list);
			
		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();

		$this->buildNavBar();
	}

	// }}}
	// {{{ private function getDependencies()

	private function getDependencies($dep, $item_list)
	{
		$dep_subarticles = new AdminListDependency();
		$dep_subarticles->title = Store::_('sub-article');
		$dep_subarticles->entries = AdminListDependency::queryEntries(
			$this->app->db, 'Article', 'integer:id', 'integer:parent',
			'title', 'title', 'parent in ('.$item_list.')',
			AdminDependency::DELETE); 

		$dep->addDependency($dep_subarticles);

		if (count($dep_subarticles->entries)) {
			$entries = array();
			foreach ($dep_subarticles->entries as $entry)
				$entries[] = $this->app->db->quote($entry->id, 'integer');

			$item_list = implode(',', $entries);

			$this->getDependencies($dep_subarticles, $item_list);
		}
	}

	// }}}
	// {{{ private fucntion buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->popEntry();

		if ($this->single_delete) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getArticleNavBar', array($this->getFirstItem()));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Article/Index?id='.$elem->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Delete')));
	}

	// }}}
}

?>
