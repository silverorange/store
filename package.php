<?php

require_once 'PEAR/PackageFileManager2.php';

$version = '2.0.6';
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
		'filelistgenerator' => 'file',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'Store'         => 'php',
			'dependencies'  => 'data',
			'/'             => 'data',
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

$package->setPhpDep('5.3.0');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'Swat', 'pear.silverorange.com', '1.4.131');
$package->addPackageDepWithChannel('required', 'Site', 'pear.silverorange.com', '2.0.10');
$package->addPackageDepWithChannel('required', 'Admin', 'pear.silverorange.com', '1.3.70');
$package->addPackageDepWithChannel('required', 'XML_RPCAjax', 'pear.silverorange.com', '1.0.9');
$package->addPackageDepWithChannel('required', 'Yui', 'pear.silverorange.com', '1.0.6');
$package->addPackageDepWithChannel('required', 'Net_Notifier', 'pear.silverorange.com', '0.1.0');
$package->addPackageDepWithChannel('required', 'Crypt_GPG', 'pear.php.net', '0.7.0');
$package->addPackageDepWithChannel('required', 'Text_Password', 'pear.php.net', '1.1.0');
$package->addPackageDepWithChannel('required', 'Validate_Finance_CreditCard', 'pear.php.net', '0.5.2');
$package->addPackageDepWithChannel('required', 'Numbers_Words', 'pear.php.net', '0.15.0');
$package->addPackageDepWithChannel('optional', 'Deliverance', 'pear.silverorange.com', '0.2.7');
$package->addPackageDepWithChannel('optional', 'NateGoSearch', 'pear.silverorange.com', '1.0.0');
$package->addPackageDepWithChannel('optional', 'Blorg', 'pear.silverorange.com', '0.1.34');
$package->addPackageDepWithChannel('optional', 'Services_StrikeIron', 'pear.silverorange.com', '0.1.0');
$package->addPackageDepWithChannel('optional', 'Payment_PayPal_SOAP', 'pear.php.net', '0.2.1');
$package->addPackageDepWithChannel('optional', 'Services_Akismet2', 'pear.php.net', '0.2.0');
$package->addPackageDepWithChannel('optional', 'AuthorizeNet', 'pear.silverorange.com', '1.1.1so2');
$package->addExtensionDep('required', 'imagick', '2.0.0');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
