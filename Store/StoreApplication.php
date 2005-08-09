<?
require_once('Swat/SwatApplication.php');
require_once('MDB2.php');

abstract class StoreApplication extends SwatApplication {

	public $db;

	/**
	 * Initialize the application.
	 */
	public function init() {
		$this->initDatabase();
		$this->base_uri_length = 3;
	}

	abstract protected function getDSN();

	/**
	 * Get the page object.
	 * Uses the $_GET variables to decide which page subclass to instantiate.
	 * @return SwatPage A subclass of SwatPage is returned.
	 */
	public function getPage() {
		$source = self::initVar('source');
		
		$page = $this->instantiatePage($source);
		$source_exp = explode('/', $source);

		$page->app = $this;
		$page->setSource($source_exp);
		$page->build();
		
		if (!$page->found) {
			require_once('../include/pages/NotFoundPage.php');
			$page =  new NotFoundPage();
			$page->app = $this;
			$page->setSource($source_exp);
			$page->build();
		}
		
		return $page;
	}

	/**
	 * Instantiates a page object.
	 * @return SwatPage A subclass of SwatPage is returned.
	 */
	abstract protected function instantiatePage($source);

	private function initDatabase() {
		// TODO: change to array /form of DSN and move parts to a secure include file.
		$dsn = $this->getDSN();
		$this->db = MDB2::connect($dsn);
		$this->db->options['debug'] = true;

		if (MDB2::isError($this->db))
			throw new Exception('Unable to connect to database.');
	}
}
?>
