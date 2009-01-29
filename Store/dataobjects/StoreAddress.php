<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreCountry.php';
require_once 'Store/dataobjects/StoreProvState.php';

/**
 * An address for an e-commerce web application
 *
 * Addresses usually belongs to accounts but may be used in other instances.
 * There is intentionally no reference back to the account or order this
 * address belongs to.
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountAddress, StoreOrderAddress
 */
abstract class StoreAddress extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Address identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The full name of the address holder
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * The company of the address
	 *
	 * @var text
	 */
	public $company;

	/**
	 * Line 1 of the address
	 *
	 * This usually corresponds to the street name and number.
	 *
	 * @var string
	 */
	public $line1;

	/**
	 * Optional line 2 of the address
	 *
	 * This usually corresponds to a suite or apartment number.
	 *
	 * @var string
	 */
	public $line2;

	/**
	 * The city of this address
	 *
	 * @var string
	 */
	public $city;

	/**
	 * Alternative free-form field for provstate of this address
	 *
	 * @var string
	 */
	public $provstate_other;

	/**
	 * The ZIP Code or postal code of this address
	 *
	 * @var string
	 */
	public $postal_code;

	/**
	 * Phone number for this address
	 *
	 * @var text
	 */
	public $phone;

	// }}}
	// {{{ private properties

	/*
	 * Array of common street suffixes and their proper postal abbreviations.
	 * http://www.usps.com/ncsc/lookups/abbreviations.html
	 */
	private static $street_suffixes = array(
		'ALLEE' => 'ALY',
		'ALLEY' => 'ALY',
		'ALLY' => 'ALY',
		'ALY' => 'ALY',
		'ANEX' => 'ANX',
		'ANNEX' => 'ANX',
		'ANNX' => 'ANX',
		'ANX' => 'ANX',
		'ARC' => 'ARC',
		'ARCADE' => 'ARC',
		'AV' => 'AVE',
		'AVE' => 'AVE',
		'AVEN' => 'AVE',
		'AVENU' => 'AVE',
		'AVENUE' => 'AVE',
		'AVN' => 'AVE',
		'AVNUE' => 'AVE',
		'BAYOO' => 'BYU',
		'BAYOU' => 'BYU',
		'BCH' => 'BCH',
		'BEACH' => 'BCH',
		'BEND' => 'BND',
		'BND' => 'BND',
		'BLF' => 'BLF',
		'BLUF' => 'BLF',
		'BLUFF' => 'BLF',
		'BLUFFS' => 'BLFS',
		'BOT' => 'BTM',
		'BOTTM' => 'BTM',
		'BOTTOM' => 'BTM',
		'BTM' => 'BTM',
		'BLVD' => 'BLVD',
		'BOUL' => 'BLVD',
		'BOULEVARD' => 'BLVD',
		'BOULV' => 'BLVD',
		'BR' => 'BR',
		'BRANCH' => 'BR',
		'BRNCH' => 'BR',
		'BRDGE' => 'BRG',
		'BRG' => 'BRG',
		'BRIDGE' => 'BRG',
		'BRK' => 'BRK',
		'BROOK' => 'BRK',
		'BROOKS' => 'BRKS',
		'BURG' => 'BG',
		'BURGS' => 'BGS',
		'BYP' => 'BYP',
		'BYPA' => 'BYP',
		'BYPAS' => 'BYP',
		'BYPASS' => 'BYP',
		'BYPS' => 'BYP',
		'CAMP' => 'CP',
		'CMP' => 'CP',
		'CP' => 'CP',
		'CANYN' => 'CYN',
		'CANYON' => 'CYN',
		'CNYN' => 'CYN',
		'CYN' => 'CYN',
		'CAPE' => 'CPE',
		'CPE' => 'CPE',
		'CAUSEWAY' => 'CSWY',
		'CAUSWAY' => 'CSWY',
		'CSWY' => 'CSWY',
		'CEN' => 'CTR',
		'CENT' => 'CTR',
		'CENTER' => 'CTR',
		'CENTR' => 'CTR',
		'CENTRE' => 'CTR',
		'CNTER' => 'CTR',
		'CNTR' => 'CTR',
		'CTR' => 'CTR',
		'CENTERS' => 'CTRS',
		'CIR' => 'CIR',
		'CIRC' => 'CIR',
		'CIRCL' => 'CIR',
		'CIRCLE' => 'CIR',
		'CRCL' => 'CIR',
		'CRCLE' => 'CIR',
		'CIRCLES' => 'CIRS',
		'CLF' => 'CLF',
		'CLIFF' => 'CLF',
		'CLFS' => 'CLFS',
		'CLIFFS' => 'CLFS',
		'CLB' => 'CLB',
		'CLUB' => 'CLB',
		'COMMON' => 'CMN',
		'COR' => 'COR',
		'CORNER' => 'COR',
		'CORNERS' => 'CORS',
		'CORS' => 'CORS',
		'COURSE' => 'CRSE',
		'CRSE' => 'CRSE',
		'COURT' => 'CT',
		'CRT' => 'CT',
		'CT' => 'CT',
		'COURTS' => 'CTS',
		'CT' => 'CTS',
		'COVE' => 'CV',
		'CV' => 'CV',
		'COVES' => 'CVS',
		'CK' => 'CRK',
		'CR' => 'CRK',
		'CREEK' => 'CRK',
		'CRK' => 'CRK',
		'CRECENT' => 'CRES',
		'CRES' => 'CRES',
		'CRESCENT' => 'CRES',
		'CRESENT' => 'CRES',
		'CRSCNT' => 'CRES',
		'CRSENT' => 'CRES',
		'CRSNT' => 'CRES',
		'CREST' => 'CRST',
		'CROSSING' => 'XING',
		'CRSSING' => 'XING',
		'CRSSNG' => 'XING',
		'XING' => 'XING',
		'CROSSROAD' => 'XRD',
		'CURVE' => 'CURV',
		'DALE' => 'DL',
		'DL' => 'DL',
		'DAM' => 'DM',
		'DM' => 'DM',
		'DIV' => 'DV',
		'DIVIDE' => 'DV',
		'DV' => 'DV',
		'DVD' => 'DV',
		'DR' => 'DR',
		'DRIV' => 'DR',
		'DRIVE' => 'DR',
		'DRV' => 'DR',
		'DRIVES' => 'DRS',
		'EST' => 'EST',
		'ESTATE' => 'EST',
		'ESTATES' => 'ESTS',
		'ESTS' => 'ESTS',
		'EXP' => 'EXPY',
		'EXPR' => 'EXPY',
		'EXPRESS' => 'EXPY',
		'EXPRESSWAY' => 'EXPY',
		'EXPW' => 'EXPY',
		'EXPY' => 'EXPY',
		'EXT' => 'EXT',
		'EXTENSION' => 'EXT',
		'EXTN' => 'EXT',
		'EXTNSN' => 'EXT',
		'EXTENSIONS' => 'EXTS',
		'EXTS' => 'EXTS',
		'FALL' => 'FALL',
		'FALLS' => 'FLS',
		'FLS' => 'FLS',
		'FERRY' => 'FRY',
		'FRRY' => 'FRY',
		'FRY' => 'FRY',
		'FIELD' => 'FLD',
		'FLD' => 'FLD',
		'FIELDS' => 'FLDS',
		'FLDS' => 'FLDS',
		'FLAT' => 'FLT',
		'FLT' => 'FLT',
		'FLATS' => 'FLTS',
		'FLTS' => 'FLTS',
		'FORD' => 'FRD',
		'FRD' => 'FRD',
		'FORDS' => 'FRDS',
		'FOREST' => 'FRST',
		'FORESTS' => 'FRST',
		'FRST' => 'FRST',
		'FORG' => 'FRG',
		'FORGE' => 'FRG',
		'FRG' => 'FRG',
		'FORGES' => 'FRGS',
		'FORK' => 'FRK',
		'FRK' => 'FRK',
		'FORKS' => 'FRKS',
		'FRKS' => 'FRKS',
		'FORT' => 'FT',
		'FRT' => 'FT',
		'FT' => 'FT',
		'FREEWAY' => 'FWY',
		'FREEWY' => 'FWY',
		'FRWAY' => 'FWY',
		'FRWY' => 'FWY',
		'FWY' => 'FWY',
		'GARDEN' => 'GDN',
		'GARDN' => 'GDN',
		'GDN' => 'GDN',
		'GRDEN' => 'GDN',
		'GRDN' => 'GDN',
		'GARDENS' => 'GDNS',
		'GDNS' => 'GDNS',
		'GRDNS' => 'GDNS',
		'GATEWAY' => 'GTWY',
		'GATEWY' => 'GTWY',
		'GATWAY' => 'GTWY',
		'GTWAY' => 'GTWY',
		'GTWY' => 'GTWY',
		'GLEN' => 'GLN',
		'GLN' => 'GLN',
		'GLENS' => 'GLNS',
		'GREEN' => 'GRN',
		'GRN' => 'GRN',
		'GREENS' => 'GRNS',
		'GROV' => 'GRV',
		'GROVE' => 'GRV',
		'GRV' => 'GRV',
		'GROVES' => 'GRVS',
		'HARB' => 'HBR',
		'HARBOR' => 'HBR',
		'HARBR' => 'HBR',
		'HBR' => 'HBR',
		'HRBOR' => 'HBR',
		'HARBORS' => 'HBRS',
		'HAVEN' => 'HVN',
		'HAVN' => 'HVN',
		'HVN' => 'HVN',
		'HEIGHT' => 'HTS',
		'HEIGHTS' => 'HTS',
		'HGTS' => 'HTS',
		'HT' => 'HTS',
		'HTS' => 'HTS',
		'HIGHWAY' => 'HWY',
		'HIGHWY' => 'HWY',
		'HIWAY' => 'HWY',
		'HIWY' => 'HWY',
		'HWAY' => 'HWY',
		'HWY' => 'HWY',
		'HILL' => 'HL',
		'HL' => 'HL',
		'HILLS' => 'HLS',
		'HLS' => 'HLS',
		'HLLW' => 'HOLW',
		'HOLLOW' => 'HOLW',
		'HOLLOWS' => 'HOLW',
		'HOLW' => 'HOLW',
		'HOLWS' => 'HOLW',
		'INLET' => 'INLT',
		'INLT' => 'INLT',
		'IS' => 'IS',
		'ISLAND' => 'IS',
		'ISLND' => 'IS',
		'ISLANDS' => 'ISS',
		'ISLNDS' => 'ISS',
		'ISS' => 'ISS',
		'ISLE' => 'ISLE',
		'ISLES' => 'ISLE',
		'JCT' => 'JCT',
		'JCTION' => 'JCT',
		'JCTN' => 'JCT',
		'JUNCTION' => 'JCT',
		'JUNCTN' => 'JCT',
		'JUNCTON' => 'JCT',
		'JCTNS' => 'JCTS',
		'JCTS' => 'JCTS',
		'JUNCTIONS' => 'JCTS',
		'KEY' => 'KY',
		'KY' => 'KY',
		'KEYS' => 'KYS',
		'KYS' => 'KYS',
		'KNL' => 'KNL',
		'KNOL' => 'KNL',
		'KNOLL' => 'KNL',
		'KNLS' => 'KNLS',
		'KNOLLS' => 'KNLS',
		'LAKE' => 'LK',
		'LK' => 'LK',
		'LAKES' => 'LKS',
		'LKS' => 'LKS',
		'LAND' => 'LAND',
		'LANDING' => 'LNDG',
		'LNDG' => 'LNDG',
		'LNDNG' => 'LNDG',
		'LA' => 'LN',
		'LANE' => 'LN',
		'LANES' => 'LN',
		'LN' => 'LN',
		'LGT' => 'LGT',
		'LIGHT' => 'LGT',
		'LIGHTS' => 'LGTS',
		'LF' => 'LF',
		'LOAF' => 'LF',
		'LCK' => 'LCK',
		'LOCK' => 'LCK',
		'LCKS' => 'LCKS',
		'LOCKS' => 'LCKS',
		'LDG' => 'LDG',
		'LDGE' => 'LDG',
		'LODG' => 'LDG',
		'LODGE' => 'LDG',
		'LOOP' => 'LOOP',
		'LOOPS' => 'LOOP',
		'MALL' => 'MALL',
		'MANOR' => 'MNR',
		'MNR' => 'MNR',
		'MANORS' => 'MNRS',
		'MNRS' => 'MNRS',
		'MDW' => 'MDW',
		'MEADOW' => 'MDW',
		'MDWS' => 'MDWS',
		'MEADOWS' => 'MDWS',
		'MEDOWS' => 'MDWS',
		'MEWS' => 'MEWS',
		'MILL' => 'ML',
		'ML' => 'ML',
		'MILLS' => 'MLS',
		'MLS' => 'MLS',
		'MISSION' => 'MSN',
		'MISSN' => 'MSN',
		'MSN' => 'MSN',
		'MSSN' => 'MSN',
		'MOTORWAY' => 'MTWY',
		'MNT' => 'MT',
		'MOUNT' => 'MT',
		'MT' => 'MT',
		'MNTAIN' => 'MTN',
		'MNTN' => 'MTN',
		'MOUNTAIN' => 'MTN',
		'MOUNTIN' => 'MTN',
		'MTIN' => 'MTN',
		'MTN' => 'MTN',
		'MNTNS' => 'MTNS',
		'MOUNTAINS' => 'MTNS',
		'NCK' => 'NCK',
		'NECK' => 'NCK',
		'ORCHARD' => 'ORCH',
		'ORCHRD' => 'ORCH',
		'OVAL' => 'OVAL',
		'OVL' => 'OVAL',
		'OVERPASS' => 'OPAS',
		'PARK' => 'PARK',
		'PK' => 'PARK',
		'PRK' => 'PARK',
		'PARKS' => 'PARK',
		'PARKWAY' => 'PKWY',
		'PARKWY' => 'PKWY',
		'PKWAY' => 'PKWY',
		'PKWY' => 'PKWY',
		'PKY' => 'PKWY',
		'PARKWAYS' => 'PKWY',
		'PKWYS' => 'PKWY',
		'PASS' => 'PASS',
		'PASSAGE' => 'PSGE',
		'PATH' => 'PATH',
		'PATHS' => 'PATH',
		'PIKE' => 'PIKE',
		'PIKES' => 'PIKE',
		'PINE' => 'PNE',
		'PINES' => 'PNES',
		'PNES' => 'PNES',
		'PL' => 'PL',
		'PLACE' => 'PL',
		'PLAIN' => 'PLN',
		'PLN' => 'PLN',
		'PLAINES' => 'PLNS',
		'PLAINS' => 'PLNS',
		'PLNS' => 'PLNS',
		'PLAZA' => 'PLZ',
		'PLZ' => 'PLZ',
		'PLZA' => 'PLZ',
		'POINT' => 'PT',
		'PT' => 'PT',
		'POINTS' => 'PTS',
		'PTS' => 'PTS',
		'PORT' => 'PRT',
		'PRT' => 'PRT',
		'PORTS' => 'PRTS',
		'PRTS' => 'PRTS',
		'PR' => 'PR',
		'PRAIRIE' => 'PR',
		'PRARIE' => 'PR',
		'PRR' => 'PR',
		'RAD' => 'RADL',
		'RADIAL' => 'RADL',
		'RADIEL' => 'RADL',
		'RADL' => 'RADL',
		'RAMP' => 'RAMP',
		'RANCH' => 'RNCH',
		'RANCHES' => 'RNCH',
		'RNCH' => 'RNCH',
		'RNCHS' => 'RNCH',
		'RAPID' => 'RPD',
		'RPD' => 'RPD',
		'RAPIDS' => 'RPDS',
		'RPDS' => 'RPDS',
		'REST' => 'RST',
		'RST' => 'RST',
		'RDG' => 'RDG',
		'RDGE' => 'RDG',
		'RIDGE' => 'RDG',
		'RDGS' => 'RDGS',
		'RIDGES' => 'RDGS',
		'RIV' => 'RIV',
		'RIVER' => 'RIV',
		'RIVR' => 'RIV',
		'RVR' => 'RIV',
		'RD' => 'RD',
		'ROAD' => 'RD',
		'RDS' => 'RDS',
		'ROADS' => 'RDS',
		'ROUTE' => 'RTE',
		'ROW' => 'ROW',
		'RUE' => 'RUE',
		'RUN' => 'RUN',
		'SHL' => 'SHL',
		'SHOAL' => 'SHL',
		'SHLS' => 'SHLS',
		'SHOALS' => 'SHLS',
		'SHOAR' => 'SHR',
		'SHORE' => 'SHR',
		'SHR' => 'SHR',
		'SHOARS' => 'SHRS',
		'SHORES' => 'SHRS',
		'SHRS' => 'SHRS',
		'SKYWAY' => 'SKWY',
		'SPG' => 'SPG',
		'SPNG' => 'SPG',
		'SPRING' => 'SPG',
		'SPRNG' => 'SPG',
		'SPGS' => 'SPGS',
		'SPNGS' => 'SPGS',
		'SPRINGS' => 'SPGS',
		'SPRNGS' => 'SPGS',
		'SPUR' => 'SPUR',
		'SPURS' => 'SPUR',
		'SQ' => 'SQ',
		'SQR' => 'SQ',
		'SQRE' => 'SQ',
		'SQU' => 'SQ',
		'SQUARE' => 'SQ',
		'SQRS' => 'SQS',
		'SQUARES' => 'SQS',
		'STA' => 'STA',
		'STATION' => 'STA',
		'STATN' => 'STA',
		'STN' => 'STA',
		'STRA' => 'STRA',
		'STRAV' => 'STRA',
		'STRAVE' => 'STRA',
		'STRAVEN' => 'STRA',
		'STRAVENUE' => 'STRA',
		'STRAVN' => 'STRA',
		'STRVN' => 'STRA',
		'STRVNUE' => 'STRA',
		'STREAM' => 'STRM',
		'STREME' => 'STRM',
		'STRM' => 'STRM',
		'ST' => 'ST',
		'STR' => 'ST',
		'STREET' => 'ST',
		'STRT' => 'ST',
		'STREETS' => 'STS',
		'SMT' => 'SMT',
		'SUMIT' => 'SMT',
		'SUMITT' => 'SMT',
		'SUMMIT' => 'SMT',
		'TER' => 'TER',
		'TERR' => 'TER',
		'TERRACE' => 'TER',
		'THROUGHWAY' => 'TRWY',
		'TRACE' => 'TRCE',
		'TRACES' => 'TRCE',
		'TRCE' => 'TRCE',
		'TRACK' => 'TRAK',
		'TRACKS' => 'TRAK',
		'TRAK' => 'TRAK',
		'TRK' => 'TRAK',
		'TRKS' => 'TRAK',
		'TRAFFICWAY' => 'TRFY',
		'TRFY' => 'TRFY',
		'TR' => 'TRL',
		'TRAIL' => 'TRL',
		'TRAILS' => 'TRL',
		'TRL' => 'TRL',
		'TRLS' => 'TRL',
		'TUNEL' => 'TUNL',
		'TUNL' => 'TUNL',
		'TUNLS' => 'TUNL',
		'TUNNEL' => 'TUNL',
		'TUNNELS' => 'TUNL',
		'TUNNL' => 'TUNL',
		'TPK' => 'TPKE',
		'TPKE' => 'TPKE',
		'TRNPK' => 'TPKE',
		'TRPK' => 'TPKE',
		'TURNPIKE' => 'TPKE',
		'TURNPK' => 'TPKE',
		'UNDERPASS' => 'UPAS',
		'UN' => 'UN',
		'UNION' => 'UN',
		'UNIONS' => 'UNS',
		'VALLEY' => 'VLY',
		'VALLY' => 'VLY',
		'VLLY' => 'VLY',
		'VLY' => 'VLY',
		'VALLEYS' => 'VLYS',
		'VLYS' => 'VLYS',
		'VDCT' => 'VIA',
		'VIA' => 'VIA',
		'VIADCT' => 'VIA',
		'VIADUCT' => 'VIA',
		'VIEW' => 'VW',
		'VW' => 'VW',
		'VIEWS' => 'VWS',
		'VWS' => 'VWS',
		'VILL' => 'VLG',
		'VILLAG' => 'VLG',
		'VILLAGE' => 'VLG',
		'VILLG' => 'VLG',
		'VILLIAGE' => 'VLG',
		'VLG' => 'VLG',
		'VILLAGES' => 'VLGS',
		'VLGS' => 'VLGS',
		'VILLE' => 'VL',
		'VL' => 'VL',
		'VIS' => 'VIS',
		'VIST' => 'VIS',
		'VISTA' => 'VIS',
		'VST' => 'VIS',
		'VSTA' => 'VIS',
		'WALK' => 'WALK',
		'WALKS' => 'WALK',
		'WALL' => 'WALL',
		'WAY' => 'WAY',
		'WY' => 'WAY',
		'WAYS' => 'WAYS',
		'WELL' => 'WL',
		'WELLS' => 'WLS',
		'WLS' => 'WLS',
	);

	// }}}
	// {{{ public static function isVerificationAvailable()

	/**
	 * Checks the application's config and returns whether a key to a
	 * verification service exists or not.
	 *
	 * @param StoreApplication $app the application that you want to check the
	 *                               config for verification key.
	 *
	 * @return boolean True if there is a key for verification.
	 */
	public static function isVerificationAvailable(StoreApplication $app)
	{
		return isset($app->config->strikeiron->verify_address_usa_key);
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this address in postal format
	 */
	public function display()
	{
		$address_tag = new SwatHtmlTag('address');
		$address_tag->class = 'vcard';
		$address_tag->open();

		switch ($this->country->id) {
		case 'CA':
			$this->displayCA();
			break;
		case 'GB':
			$this->displayGB();
			break;
		default:
			$this->displayUS();
		}

		$address_tag->close();
	}

	// }}}
	// {{{ public function displayCondensed()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display uses XHTML and is ideal for cell renderers. The format of
	 * this display borrows from but does not conform to post office address
	 * formatting rules.
	 */
	public function displayCondensed()
	{
		/*
		 * Condensed display is intentionally not wrapped in an address tag so
		 * it may be wrapped inside an inline element. See r6634.
		 */

		switch ($this->country->id) {
		case 'CA':
			$this->displayCondensedCA();
			break;
		case 'GB':
			$this->displayCondensedGB();
			break;
		default:
			$this->displayCondensedUS();
		}
	}

	// }}}
	// {{{ public function displayCondensedAsText()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display is formatted as plain text and is ideal for emails. The
	 * format of this display borrows from but does not conform to post office
	 * address formatting rules.
	 */
	public function displayCondensedAsText()
	{
		switch ($this->country->id) {
		case 'CA':
			$this->displayCondensedAsTextCA();
			break;
		case 'GB':
			$this->displayCondensedAsTextGB();
			break;
		default:
			$this->displayCondensedAsTextUS();
		}
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StoreAddress $address)
	{
		$this->fullname        = $address->fullname;
		$this->company         = $address->company;
		$this->line1           = $address->line1;
		$this->line2           = $address->line2;
		$this->city            = $address->city;
		$this->postal_code     = $address->postal_code;
		$this->provstate_other = $address->provstate_other;
		$this->phone           = $address->phone;
		$this->provstate       = $address->getInternalValue('provstate');
		$this->country         = $address->getInternalValue('country');
	}

	// }}}
	// {{{ public function getFullName()

	/**
	 * Gets the full name of the person at this address
	 *
	 * Having this method allows subclasses to split the full name into an
	 * arbitrary number of fields. For example, first name and last name.
	 *
	 * @return string the full name of the person at this address.
	 */
	public function getFullName()
	{
		return $this->fullname;
	}

	// }}}
	// {{{ public function compare()

	/**
	 * Compares this address to another address
	 *
	 * @param StoreAddress $address the address to compare this entry to.
	 *
	 * @return boolean True if all internal values match, and false if any
	 *                  don't match.
	 */
	public function compare(StoreAddress $address)
	{
		$equal = true;

		if ($this->fullname !== $address->fullname)
			$equal = false;

		if ($this->company !== $address->company)
			$equal = false;

		if ($this->line1 !== $address->line1)
			$equal = false;

		if ($this->line2 !== $address->line2)
			$equal = false;

		if ($this->city !== $address->city)
			$equal = false;

		if ($this->provstate_other !== $address->provstate_other)
			$equal = false;

		if ($this->postal_code !== $address->postal_code)
			$equal = false;

		if ($this->phone !== $address->phone)
			$equal = false;

		return $equal;
	}

	// }}}
	// {{{ public function verify()

	/**
	 * Verify this address
	 */
	public function verify(SiteApplication $app, $modify = true)
	{
		$valid = false;

		switch ($this->country->id) {
		case 'CA':
			$valid = $this->verifyCA($app, $modify);
			break;
		case 'US':
		default:
			$valid = $this->verifyUS($app, $modify);
		}

		return $valid;
	}

	// }}}
	// {{{ public function mostlyEqual()

	/**
	 * Compares this address to another address
	 *
	 * @param StoreAddress $address the address to compare this entry to.
	 *
	 * @return boolean True if all internal values loosely match, and false if
	 *                  any don't match.
	 */
	public function mostlyEqual(StoreAddress $address)
	{
		$equal = true;

		if ($this->fullname != $address->fullname)
			$equal = false;

		if ($this->company != $address->company)
			$equal = false;

		if (strtoupper($this->line1) != strtoupper($address->line1) &&
			!self::differByStreetSuffixOnly($this->line1, $address->line1) &&
			!self::differByStreetAbbreviationOnly($this->line1, $address->line1))
				$equal = false;

		if (strtoupper($this->line2) != strtoupper($address->line2))
			$equal = false;

		if (strtoupper($this->city) != strtoupper($address->city))
			$equal = false;

		if (strtoupper($this->provstate_other) != strtoupper($address->provstate_other))
			$equal = false;

		if ($this->country->id != $address->country->id)
			$equal = false;

		if ($this->country->id === 'US') {
			if (substr($this->postal_code, 0, 5) != substr($address->postal_code, 0, 5))
				$equal = false;
		} else {
			if ($this->postal_code != $address->postal_code)
				$equal = false;
		}

		if ($this->phone != $address->phone)
			$equal = false;

		return $equal;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('provstate',
			SwatDBClassMap::get('StoreProvState'));

		$this->registerInternalProperty('country',
			SwatDBClassMap::get('StoreCountry'));
	}

	// }}}
	// {{{ protected function displayCA()

	/**
	 * Displays this address in Canada Post format
	 *
	 * Canadian address format rules are taken from {@link Canada Post
	 * http://www.canadapost.ca/personal/tools/pg/manual/PGaddress-e.asp#1383571}
	 */
	protected function displayCA()
	{
		$span_tag = new SwatHtmlTag('span');

		if ($this->getFullName() != '') {
			$span_tag->class = 'fn';
			$span_tag->setContent($this->getFullName());
			$span_tag->display();
			echo '<br />';
		}

		if ($this->company != '') {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo '<br />';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		if ($this->line1 != '') {
			$span_tag->class = 'street-address';
			$span_tag->setContent($this->line1);
			$span_tag->display();
			echo '<br />';

			if ($this->line2 != '') {
				$span_tag->class = 'extended-address';
				$span_tag->setContent($this->line2);
				$span_tag->display();
				echo '<br />';
			}
		}

		if ($this->city != '') {
			$span_tag->class = 'locality';
			$span_tag->setContent($this->city);
			$span_tag->display();
			echo ' ';
		}

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif ($this->provstate_other != '') {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		echo '&nbsp;&nbsp;';

		$span_tag->class = 'postal-code';
		$span_tag->setContent($this->postal_code);
		$span_tag->display();
		echo '<br />';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if ($this->phone != '') {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayGB()

	/**
	 * Displays this address in Royal Mail format
	 *
	 * Formatting rules for UK addresses are taken from
	 * {@link http://www.royalmail.com/portal/rm/content1?catId=400126&mediaId=32700664}.
	 */
	protected function displayGB()
	{
		echo SwatString::minimizeEntities($this->getFullName()), '<br />';

		if ($this->company != '')
			echo SwatString::minimizeEntities($this->company), '<br />';

		echo SwatString::minimizeEntities($this->line1), '<br />';

		if ($this->line2 != '')
			echo SwatString::minimizeEntities($this->line2), '<br />';

		echo SwatString::minimizeEntities($this->city), '<br />';

		if ($this->provstate_other != '')
			echo SwatString::minimizeEntities($this->provstate_other), '<br />';

		echo SwatString::minimizeEntities($this->postal_code), '<br />';

		echo SwatString::minimizeEntities($this->country->title), '<br />';

		if ($this->phone != '')
			printf('Phone: %s',
				SwatString::minimizeEntities($this->phone));
	}

	// }}}
	// {{{ protected function displayUS()

	/**
	 * Displays this address in US Postal Service format
	 *
	 * American address format rules are taken from
	 * {@link http://pe.usps.gov/text/pub28/28c2_007.html}.
	 */
	protected function displayUS()
	{
		$span_tag = new SwatHtmlTag('span');

		if ($this->getFullName() != '') {
			$span_tag->class = 'fn';
			$span_tag->setContent($this->getFullName());
			$span_tag->display();
			echo '<br />';
		}

		if ($this->company != '') {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo '<br />';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		if ($this->line1 != '') {
			$span_tag->class = 'street-address';
			$span_tag->setContent($this->line1);
			$span_tag->display();
			echo '<br />';

			if ($this->line2 != '') {
				$span_tag->class = 'extended-address';
				$span_tag->setContent($this->line2);
				$span_tag->display();
				echo '<br />';
			}
		}

		if ($this->city != '') {
			$span_tag->class = 'locality';
			$span_tag->setContent($this->city);
			$span_tag->display();
			echo ' ';
		}

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif ($this->provstate_other != '') {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		echo '&nbsp;&nbsp;';

		$span_tag->class = 'postal-code';
		$span_tag->setContent($this->postal_code);
		$span_tag->display();
		echo '<br />';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if ($this->phone != '') {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCondensedCA()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedCA()
	{
		$span_tag = new SwatHtmlTag('span');

		if ($this->getFullName() != '') {
			$span_tag->class = 'fn';
			$span_tag->setContent($this->getFullName());
			$span_tag->display();
			echo ', ';
		}

		if ($this->company != '') {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo ', ';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		if ($this->line1 != '') {
			$span_tag->class = 'street-address';
			$span_tag->setContent($this->line1);
			$span_tag->display();

			if ($this->line2 != '') {
				echo ', ';
				$span_tag->class = 'extended-address';
				$span_tag->setContent($this->line2);
				$span_tag->display();
			}
		}

		if ($this->getFullName() != '' || $this->company != '' ||
			$this->line1 != '') {
			echo '<br />';
		}

		if ($this->city != '') {
			$span_tag->class = 'locality';
			$span_tag->setContent($this->city);
			$span_tag->display();
			echo ' ';
		}

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif ($this->provstate_other != '') {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			$span_tag->class = 'postal-code';
			$span_tag->setContent($this->postal_code);
			$span_tag->display();
		}

		echo ', ';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if ($this->phone != '') {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCondensedGB()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedGB()
	{
		echo SwatString::minimizeEntities($this->getFullName()), ', ';

		if ($this->company != '')
			echo SwatString::minimizeEntities($this->company), ', ';

		echo SwatString::minimizeEntities($this->line1);
		if ($this->line2 != '')
			echo ', ', SwatString::minimizeEntities($this->line2);

		echo '<br />';
		echo SwatString::minimizeEntities($this->city);

		if ($this->provstate_other != '')
			echo ', ', SwatString::minimizeEntities($this->provstate_other);

		if ($this->postal_code !== null)
			echo ', ', SwatString::minimizeEntities($this->postal_code);

		echo ', ', SwatString::minimizeEntities($this->country->title);

		if ($this->phone != '') {
			echo '<br />';
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function displayCondensedUS()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedUS()
	{
		$span_tag = new SwatHtmlTag('span');

		if ($this->getFullName() != '') {
			$span_tag->class = 'fn';
			$span_tag->setContent($this->getFullName());
			$span_tag->display();
			echo ', ';
		}

		if ($this->company != '') {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo ', ';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		if ($this->line1 != '') {
			$span_tag->class = 'street-address';
			$span_tag->setContent($this->line1);
			$span_tag->display();

			if ($this->line2 != '') {
				echo ', ';
				$span_tag->class = 'extended-address';
				$span_tag->setContent($this->line2);
				$span_tag->display();
			}
		}

		if ($this->getFullName() != '' || $this->company != '' ||
			$this->line1 != '') {
			echo '<br />';
		}

		if ($this->city != '') {
			$span_tag->class = 'locality';
			$span_tag->setContent($this->city);
			$span_tag->display();
			echo ' ';
		}

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif ($this->provstate_other != '') {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			$span_tag->class = 'postal-code';
			$span_tag->setContent($this->postal_code);
			$span_tag->display();
		}

		echo ', ';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if ($this->phone != '') {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCondensedAsTextCA()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextCA()
	{
		echo $this->getFullName(), ', ';

		if ($this->company != '')
			echo $this->company, ', ';

		echo $this->line1;
		if ($this->line2 != '')
			echo ', ', $this->line2;

		echo "\n";

		echo $this->city, ' ';

		if ($this->provstate !== null)
			echo $this->provstate->abbreviation;
		elseif ($this->provstate_other != '')
			echo $this->provstate_other;

		if ($this->postal_code !== null) {
			echo '  ';
			echo $this->postal_code;
		}
		echo ', ';

		echo $this->country->title;

		if ($this->phone != '') {
			echo "\n";
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function displayCondensedAsTextGB()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextGB()
	{
		echo $this->getFullName(), ', ';

		if ($this->company != '')
			echo $this->company, ', ';

		echo $this->line1;
		if ($this->line2 != '')
			echo ', ', $this->line2;

		echo "\n";

		echo $this->city;
		if ($this->provstate_other != '')
			echo ', ', $this->provstate_other;

		if ($this->postal_code !== null)
			echo ', ', $this->postal_code;

		echo ', ', $this->country->title;

		if ($this->phone != '') {
			echo "\n";
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function displayCondensedAsTextUS()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextUS()
	{
		echo $this->getFullName(), ', ';

		if ($this->company != '')
			echo $this->company, ', ';

		echo $this->line1;
		if ($this->line2 != '')
			echo ', ', $this->line2;

		echo "\n";

		echo $this->city, ' ';

		if ($this->provstate !== null)
			echo $this->provstate->abbreviation;
		elseif ($this->provstate_other != '')
			echo $this->provstate_other;

		if ($this->postal_code !== null) {
			echo '  ';
			echo $this->postal_code;
		}
		echo ', ';

		echo $this->country->title;

		if ($this->phone != '') {
			echo "\n";
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function verifyUS()

	protected function verifyUS(SiteApplication $app, $modify)
	{
		$valid = false;
		$key = $app->config->strikeiron->verify_address_usa_key;

		if ($key === null)
			return $valid;

		require_once 'Services/StrikeIron/VerifyAddressUsa.php';
		$options = array('timeout' => 1);

		$strikeiron = new Services_StrikeIron_VerifyAddressUsa(
			$key, '', $options);

		$address = array(
			'addressLine1'   => $this->line1,
			'addressLine2'   => $this->line2,
			'city_state_zip' => sprintf('%s %s %s',
				$this->city,
				$this->provstate->abbreviation,
				$this->postal_code),
			'firm'           => $this->company,
			'urbanization'   => '',
			'casing'         => 'Proper'
		);

		try {
			$result = $strikeiron->VerifyAddressUSA($address);
			$valid = $result->VerifyAddressUSAResult->AddressStatus === 'Valid';

			if ($modify) {
				$this->line1 = $result->VerifyAddressUSAResult->AddressLine1;
				$this->line2 = $result->VerifyAddressUSAResult->AddressLine2;
				$this->city = $result->VerifyAddressUSAResult->City;
				$this->company = $result->VerifyAddressUSAResult->Firm;
				$this->postal_code = $result->VerifyAddressUSAResult->ZipPlus4;

				if ($this->provstate->abbreviation !== $result->VerifyAddressUSAResult->State) {
					$class = SwatDBClassMap::get('StoreProvState');
					$this->provstate = new $class;
					$this->provstate->setDatabase($app->db);
					$this->provstate->loadFromAbbreviation(
						$result->VerifyAddressUSAResult->State, 'US');
				}
			}
		} catch (SoapFault $e) {
			$e = new SwatException($e);
			$e->process();
			$valid = true;
		} catch (Services_StrikeIron_SoapException $e) {
			$e = new SwatException($e);
			$e->process();
			$valid = true;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function verifyCA()

	protected function verifyCA(SiteApplication $app, $modify)
	{
		// TODO: actually verify canadian addresses.
		return true;
	}

	// }}}
	// {{{ private static function differByStreetSuffixOnly()

	private static function differByStreetSuffixOnly($a, $b)
	{
		$result = false;

		if (strlen($a) === strlen($b))
			return $result;

		if (strlen($a) > strlen($b)) {
			$long = $a;
			$short = $b;
		} else {
			$long = $b;
			$short = $a;
		}

		$suffix = substr($long, strlen($short));
		$suffix = strtoupper(trim($suffix));

		$result = in_array($suffix, self::$street_suffixes);

		return $result;
	}

	// }}}
	// {{{ private static function differByStreetAbbreviationOnly()

	private static function differByStreetAbbreviationOnly($a, $b)
	{
		$result = false;

		if (strlen($a) === strlen($b))
			return $result;

		$a = explode(' ', trim(strtoupper($a)));
		$b = explode(' ', trim(strtoupper($b)));

		$suffix1 = implode(' ', array_diff_assoc($a, $b));
		$suffix2 = implode(' ', array_diff_assoc($b, $a));

		if (array_key_exists($suffix1, self::$street_suffixes) &&
			self::$street_suffixes[$suffix1] === $suffix2)
				$result = true;

		if (array_key_exists($suffix2, self::$street_suffixes) &&
			self::$street_suffixes[$suffix2] === $suffix1)
				$result = true;

		return $result;
	}

	// }}}
}

?>
