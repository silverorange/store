<?php

require_once 'PEAR/PackageFileManager2.php';

$version = '1.8.7';
$notes = <<<EOT
see ChangeLog
EOT;

$description =<<<EOT
Classes specific to building store websites.

* Built on top of Swat and Site packages
* An OO-style API
EOT;

$package = new PEAR_PackageFileManager2();
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$result = $package->setOptions(
	array(
		'filelistgenerator' => 'svn',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'Store' => 'php',
			'locale' => 'data',
			'www' => 'data',
		),
	)
);

$package->setPackage('Store');
$package->setSummary('Classes for building store websites');
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('LGPL', 'http://www.gnu.org/copyleft/lesser.html');

$package->setReleaseVersion($version);
$package->setReleaseStability('stable');
$package->setAPIVersion('0.0.1');
$package->setAPIStability('stable');
$package->setNotes($notes);

$package->addIgnore('package.php');

$package->addMaintainer('lead', 'nrf', 'Nathan Fredrickson', 'nathan@silverorange.com');
$package->addMaintainer('lead', 'gauthierm', 'Mike Gauthier', 'mike@silverorange.com');

$package->addReplacement('Store/Store.php', 'pear-config', '@DATA-DIR@', 'data_dir');

$package->setPhpDep('5.1.5');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'Swat', 'pear.silverorange.com', '1.2.22');
$package->addPackageDepWithChannel('required', 'Site', 'pear.silverorange.com', '1.0.31');
$package->addPackageDepWithChannel('required', 'Admin', 'pear.silverorange.com', '1.1.12');
$package->addPackageDepWithChannel('required', 'XML_RPCAjax', 'pear.silverorange.com', '1.0.5');
$package->addPackageDepWithChannel('required', 'Yui', 'pear.silverorange.com', '1.0.4');
$package->addPackageDepWithChannel('required', 'Crypt_GPG', 'pear.silverorange.com', '0.3.3');
$package->addPackageDepWithChannel('required', 'Text_Password', 'pear.php.net', '1.1.0');
$package->addPackageDepWithChannel('required', 'Image_Transform', 'pear.silverorange.com', '0.9.0so2');
$package->addPackageDepWithChannel('required', 'Validate_Finance_CreditCard', 'pear.php.net', '0.5.2');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
