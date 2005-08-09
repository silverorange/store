<?
/**
 * @package Store
 * @copyright silverorange 2005
 */
require_once('Swat/SwatPage.php');

abstract class StorePage extends SwatPage {

	public $found = false;

	protected $source = array();

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function process() {

	}

	public function build() {

	}
}

?>
