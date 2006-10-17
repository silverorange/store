<?php

require_once 'Admin/AdminListDependency.php';

class StoreAccountAddressDependency extends AdminListDependency
{
	//subclassed to get rid of the pluralization
	protected function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case self::DELETE:
			$message = sprintf(Store::_('Delete the following %s?'),
				$this->title);
			break;

		default:
			parent::getStatusLevelText($status_level, $count);
		}
		return $message;
	}
}

?>
