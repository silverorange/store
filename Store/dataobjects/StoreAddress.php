<?php

/**
 * An address for an e-commerce web application.
 *
 * Addresses usually belongs to accounts but may be used in other instances.
 * There is intentionally no reference back to the account or order this
 * address belongs to.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreAccountAddress, StoreOrderAddress
 *
 * @property ?int            $id
 * @property ?bool           $po_box
 * @property ?string         $fullname
 * @property ?string         $company
 * @property ?string         $line1
 * @property ?string         $line2
 * @property ?string         $city
 * @property ?StoreProvState $provstate
 * @property ?string         $provstate_other
 * @property ?string         $postal_code
 * @property StoreCountry    $country
 * @property ?string         $phone
 */
abstract class StoreAddress extends SwatDBDataObject
{
    /**
     * Address identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Whether this address is a PO box.
     *
     * @var bool
     */
    public $po_box;

    /**
     * The full name of the address holder.
     *
     * @var string
     */
    protected $fullname;

    /**
     * The company of the address.
     *
     * @var string
     */
    protected $company;

    /**
     * Line 1 of the address.
     *
     * This usually corresponds to the street name and number.
     *
     * @var string
     */
    protected $line1;

    /**
     * Optional line 2 of the address.
     *
     * This usually corresponds to a suite or apartment number.
     *
     * @var string
     */
    protected $line2;

    /**
     * The city of this address.
     *
     * @var string
     */
    protected $city;

    /**
     * Alternative free-form field for provstate of this address.
     *
     * @var string
     */
    protected $provstate_other;

    /**
     * The ZIP Code or postal code of this address.
     *
     * @var string
     */
    protected $postal_code;

    /**
     * Phone number for this address.
     *
     * @var string
     */
    protected $phone;

    /*
     * Array of common street suffixes and their proper postal abbreviations.
     * http://www.usps.com/ncsc/lookups/abbreviations.html
     */
    private static $street_suffixes = [
        'ALLEE'      => 'ALY',
        'ALLEY'      => 'ALY',
        'ALLY'       => 'ALY',
        'ALY'        => 'ALY',
        'ANEX'       => 'ANX',
        'ANNEX'      => 'ANX',
        'ANNX'       => 'ANX',
        'ANX'        => 'ANX',
        'ARC'        => 'ARC',
        'ARCADE'     => 'ARC',
        'AV'         => 'AVE',
        'AVE'        => 'AVE',
        'AVEN'       => 'AVE',
        'AVENU'      => 'AVE',
        'AVENUE'     => 'AVE',
        'AVN'        => 'AVE',
        'AVNUE'      => 'AVE',
        'BAYOO'      => 'BYU',
        'BAYOU'      => 'BYU',
        'BCH'        => 'BCH',
        'BEACH'      => 'BCH',
        'BEND'       => 'BND',
        'BND'        => 'BND',
        'BLF'        => 'BLF',
        'BLUF'       => 'BLF',
        'BLUFF'      => 'BLF',
        'BLUFFS'     => 'BLFS',
        'BOT'        => 'BTM',
        'BOTTM'      => 'BTM',
        'BOTTOM'     => 'BTM',
        'BTM'        => 'BTM',
        'BLVD'       => 'BLVD',
        'BOUL'       => 'BLVD',
        'BOULEVARD'  => 'BLVD',
        'BOULV'      => 'BLVD',
        'BR'         => 'BR',
        'BRANCH'     => 'BR',
        'BRNCH'      => 'BR',
        'BRDGE'      => 'BRG',
        'BRG'        => 'BRG',
        'BRIDGE'     => 'BRG',
        'BRK'        => 'BRK',
        'BROOK'      => 'BRK',
        'BROOKS'     => 'BRKS',
        'BURG'       => 'BG',
        'BURGS'      => 'BGS',
        'BYP'        => 'BYP',
        'BYPA'       => 'BYP',
        'BYPAS'      => 'BYP',
        'BYPASS'     => 'BYP',
        'BYPS'       => 'BYP',
        'CAMP'       => 'CP',
        'CMP'        => 'CP',
        'CP'         => 'CP',
        'CANYN'      => 'CYN',
        'CANYON'     => 'CYN',
        'CNYN'       => 'CYN',
        'CYN'        => 'CYN',
        'CAPE'       => 'CPE',
        'CPE'        => 'CPE',
        'CAUSEWAY'   => 'CSWY',
        'CAUSWAY'    => 'CSWY',
        'CSWY'       => 'CSWY',
        'CEN'        => 'CTR',
        'CENT'       => 'CTR',
        'CENTER'     => 'CTR',
        'CENTR'      => 'CTR',
        'CENTRE'     => 'CTR',
        'CNTER'      => 'CTR',
        'CNTR'       => 'CTR',
        'CTR'        => 'CTR',
        'CENTERS'    => 'CTRS',
        'CIR'        => 'CIR',
        'CIRC'       => 'CIR',
        'CIRCL'      => 'CIR',
        'CIRCLE'     => 'CIR',
        'CRCL'       => 'CIR',
        'CRCLE'      => 'CIR',
        'CIRCLES'    => 'CIRS',
        'CLF'        => 'CLF',
        'CLIFF'      => 'CLF',
        'CLFS'       => 'CLFS',
        'CLIFFS'     => 'CLFS',
        'CLB'        => 'CLB',
        'CLUB'       => 'CLB',
        'COMMON'     => 'CMN',
        'COR'        => 'COR',
        'CORNER'     => 'COR',
        'CORNERS'    => 'CORS',
        'CORS'       => 'CORS',
        'COURSE'     => 'CRSE',
        'CRSE'       => 'CRSE',
        'COURT'      => 'CT',
        'CRT'        => 'CT',
        'CT'         => 'CT',
        'COURTS'     => 'CTS',
        'CTS'        => 'CTS',
        'COVE'       => 'CV',
        'CV'         => 'CV',
        'COVES'      => 'CVS',
        'CK'         => 'CRK',
        'CR'         => 'CRK',
        'CREEK'      => 'CRK',
        'CRK'        => 'CRK',
        'CRECENT'    => 'CRES',
        'CRES'       => 'CRES',
        'CRESCENT'   => 'CRES',
        'CRESENT'    => 'CRES',
        'CRSCNT'     => 'CRES',
        'CRSENT'     => 'CRES',
        'CRSNT'      => 'CRES',
        'CREST'      => 'CRST',
        'CROSSING'   => 'XING',
        'CRSSING'    => 'XING',
        'CRSSNG'     => 'XING',
        'XING'       => 'XING',
        'CROSSROAD'  => 'XRD',
        'CURVE'      => 'CURV',
        'DALE'       => 'DL',
        'DL'         => 'DL',
        'DAM'        => 'DM',
        'DM'         => 'DM',
        'DIV'        => 'DV',
        'DIVIDE'     => 'DV',
        'DV'         => 'DV',
        'DVD'        => 'DV',
        'DR'         => 'DR',
        'DRIV'       => 'DR',
        'DRIVE'      => 'DR',
        'DRV'        => 'DR',
        'DRIVES'     => 'DRS',
        'EST'        => 'EST',
        'ESTATE'     => 'EST',
        'ESTATES'    => 'ESTS',
        'ESTS'       => 'ESTS',
        'EXP'        => 'EXPY',
        'EXPR'       => 'EXPY',
        'EXPRESS'    => 'EXPY',
        'EXPRESSWAY' => 'EXPY',
        'EXPW'       => 'EXPY',
        'EXPY'       => 'EXPY',
        'EXT'        => 'EXT',
        'EXTENSION'  => 'EXT',
        'EXTN'       => 'EXT',
        'EXTNSN'     => 'EXT',
        'EXTENSIONS' => 'EXTS',
        'EXTS'       => 'EXTS',
        'FALL'       => 'FALL',
        'FALLS'      => 'FLS',
        'FLS'        => 'FLS',
        'FERRY'      => 'FRY',
        'FRRY'       => 'FRY',
        'FRY'        => 'FRY',
        'FIELD'      => 'FLD',
        'FLD'        => 'FLD',
        'FIELDS'     => 'FLDS',
        'FLDS'       => 'FLDS',
        'FLAT'       => 'FLT',
        'FLT'        => 'FLT',
        'FLATS'      => 'FLTS',
        'FLTS'       => 'FLTS',
        'FORD'       => 'FRD',
        'FRD'        => 'FRD',
        'FORDS'      => 'FRDS',
        'FOREST'     => 'FRST',
        'FORESTS'    => 'FRST',
        'FRST'       => 'FRST',
        'FORG'       => 'FRG',
        'FORGE'      => 'FRG',
        'FRG'        => 'FRG',
        'FORGES'     => 'FRGS',
        'FORK'       => 'FRK',
        'FRK'        => 'FRK',
        'FORKS'      => 'FRKS',
        'FRKS'       => 'FRKS',
        'FORT'       => 'FT',
        'FRT'        => 'FT',
        'FT'         => 'FT',
        'FREEWAY'    => 'FWY',
        'FREEWY'     => 'FWY',
        'FRWAY'      => 'FWY',
        'FRWY'       => 'FWY',
        'FWY'        => 'FWY',
        'GARDEN'     => 'GDN',
        'GARDN'      => 'GDN',
        'GDN'        => 'GDN',
        'GRDEN'      => 'GDN',
        'GRDN'       => 'GDN',
        'GARDENS'    => 'GDNS',
        'GDNS'       => 'GDNS',
        'GRDNS'      => 'GDNS',
        'GATEWAY'    => 'GTWY',
        'GATEWY'     => 'GTWY',
        'GATWAY'     => 'GTWY',
        'GTWAY'      => 'GTWY',
        'GTWY'       => 'GTWY',
        'GLEN'       => 'GLN',
        'GLN'        => 'GLN',
        'GLENS'      => 'GLNS',
        'GREEN'      => 'GRN',
        'GRN'        => 'GRN',
        'GREENS'     => 'GRNS',
        'GROV'       => 'GRV',
        'GROVE'      => 'GRV',
        'GRV'        => 'GRV',
        'GROVES'     => 'GRVS',
        'HARB'       => 'HBR',
        'HARBOR'     => 'HBR',
        'HARBR'      => 'HBR',
        'HBR'        => 'HBR',
        'HRBOR'      => 'HBR',
        'HARBORS'    => 'HBRS',
        'HAVEN'      => 'HVN',
        'HAVN'       => 'HVN',
        'HVN'        => 'HVN',
        'HEIGHT'     => 'HTS',
        'HEIGHTS'    => 'HTS',
        'HGTS'       => 'HTS',
        'HT'         => 'HTS',
        'HTS'        => 'HTS',
        'HIGHWAY'    => 'HWY',
        'HIGHWY'     => 'HWY',
        'HIWAY'      => 'HWY',
        'HIWY'       => 'HWY',
        'HWAY'       => 'HWY',
        'HWY'        => 'HWY',
        'HILL'       => 'HL',
        'HL'         => 'HL',
        'HILLS'      => 'HLS',
        'HLS'        => 'HLS',
        'HLLW'       => 'HOLW',
        'HOLLOW'     => 'HOLW',
        'HOLLOWS'    => 'HOLW',
        'HOLW'       => 'HOLW',
        'HOLWS'      => 'HOLW',
        'INLET'      => 'INLT',
        'INLT'       => 'INLT',
        'IS'         => 'IS',
        'ISLAND'     => 'IS',
        'ISLND'      => 'IS',
        'ISLANDS'    => 'ISS',
        'ISLNDS'     => 'ISS',
        'ISS'        => 'ISS',
        'ISLE'       => 'ISLE',
        'ISLES'      => 'ISLE',
        'JCT'        => 'JCT',
        'JCTION'     => 'JCT',
        'JCTN'       => 'JCT',
        'JUNCTION'   => 'JCT',
        'JUNCTN'     => 'JCT',
        'JUNCTON'    => 'JCT',
        'JCTNS'      => 'JCTS',
        'JCTS'       => 'JCTS',
        'JUNCTIONS'  => 'JCTS',
        'KEY'        => 'KY',
        'KY'         => 'KY',
        'KEYS'       => 'KYS',
        'KYS'        => 'KYS',
        'KNL'        => 'KNL',
        'KNOL'       => 'KNL',
        'KNOLL'      => 'KNL',
        'KNLS'       => 'KNLS',
        'KNOLLS'     => 'KNLS',
        'LAKE'       => 'LK',
        'LK'         => 'LK',
        'LAKES'      => 'LKS',
        'LKS'        => 'LKS',
        'LAND'       => 'LAND',
        'LANDING'    => 'LNDG',
        'LNDG'       => 'LNDG',
        'LNDNG'      => 'LNDG',
        'LA'         => 'LN',
        'LANE'       => 'LN',
        'LANES'      => 'LN',
        'LN'         => 'LN',
        'LGT'        => 'LGT',
        'LIGHT'      => 'LGT',
        'LIGHTS'     => 'LGTS',
        'LF'         => 'LF',
        'LOAF'       => 'LF',
        'LCK'        => 'LCK',
        'LOCK'       => 'LCK',
        'LCKS'       => 'LCKS',
        'LOCKS'      => 'LCKS',
        'LDG'        => 'LDG',
        'LDGE'       => 'LDG',
        'LODG'       => 'LDG',
        'LODGE'      => 'LDG',
        'LOOP'       => 'LOOP',
        'LOOPS'      => 'LOOP',
        'MALL'       => 'MALL',
        'MANOR'      => 'MNR',
        'MNR'        => 'MNR',
        'MANORS'     => 'MNRS',
        'MNRS'       => 'MNRS',
        'MDW'        => 'MDW',
        'MEADOW'     => 'MDW',
        'MDWS'       => 'MDWS',
        'MEADOWS'    => 'MDWS',
        'MEDOWS'     => 'MDWS',
        'MEWS'       => 'MEWS',
        'MILL'       => 'ML',
        'ML'         => 'ML',
        'MILLS'      => 'MLS',
        'MLS'        => 'MLS',
        'MISSION'    => 'MSN',
        'MISSN'      => 'MSN',
        'MSN'        => 'MSN',
        'MSSN'       => 'MSN',
        'MOTORWAY'   => 'MTWY',
        'MNT'        => 'MT',
        'MOUNT'      => 'MT',
        'MT'         => 'MT',
        'MNTAIN'     => 'MTN',
        'MNTN'       => 'MTN',
        'MOUNTAIN'   => 'MTN',
        'MOUNTIN'    => 'MTN',
        'MTIN'       => 'MTN',
        'MTN'        => 'MTN',
        'MNTNS'      => 'MTNS',
        'MOUNTAINS'  => 'MTNS',
        'NCK'        => 'NCK',
        'NECK'       => 'NCK',
        'ORCHARD'    => 'ORCH',
        'ORCHRD'     => 'ORCH',
        'OVAL'       => 'OVAL',
        'OVL'        => 'OVAL',
        'OVERPASS'   => 'OPAS',
        'PARK'       => 'PARK',
        'PK'         => 'PARK',
        'PRK'        => 'PARK',
        'PARKS'      => 'PARK',
        'PARKWAY'    => 'PKWY',
        'PARKWY'     => 'PKWY',
        'PKWAY'      => 'PKWY',
        'PKWY'       => 'PKWY',
        'PKY'        => 'PKWY',
        'PARKWAYS'   => 'PKWY',
        'PKWYS'      => 'PKWY',
        'PASS'       => 'PASS',
        'PASSAGE'    => 'PSGE',
        'PATH'       => 'PATH',
        'PATHS'      => 'PATH',
        'PIKE'       => 'PIKE',
        'PIKES'      => 'PIKE',
        'PINE'       => 'PNE',
        'PINES'      => 'PNES',
        'PNES'       => 'PNES',
        'PL'         => 'PL',
        'PLACE'      => 'PL',
        'PLAIN'      => 'PLN',
        'PLN'        => 'PLN',
        'PLAINES'    => 'PLNS',
        'PLAINS'     => 'PLNS',
        'PLNS'       => 'PLNS',
        'PLAZA'      => 'PLZ',
        'PLZ'        => 'PLZ',
        'PLZA'       => 'PLZ',
        'POINT'      => 'PT',
        'PT'         => 'PT',
        'POINTS'     => 'PTS',
        'PTS'        => 'PTS',
        'PORT'       => 'PRT',
        'PRT'        => 'PRT',
        'PORTS'      => 'PRTS',
        'PRTS'       => 'PRTS',
        'PR'         => 'PR',
        'PRAIRIE'    => 'PR',
        'PRARIE'     => 'PR',
        'PRR'        => 'PR',
        'RAD'        => 'RADL',
        'RADIAL'     => 'RADL',
        'RADIEL'     => 'RADL',
        'RADL'       => 'RADL',
        'RAMP'       => 'RAMP',
        'RANCH'      => 'RNCH',
        'RANCHES'    => 'RNCH',
        'RNCH'       => 'RNCH',
        'RNCHS'      => 'RNCH',
        'RAPID'      => 'RPD',
        'RPD'        => 'RPD',
        'RAPIDS'     => 'RPDS',
        'RPDS'       => 'RPDS',
        'REST'       => 'RST',
        'RST'        => 'RST',
        'RDG'        => 'RDG',
        'RDGE'       => 'RDG',
        'RIDGE'      => 'RDG',
        'RDGS'       => 'RDGS',
        'RIDGES'     => 'RDGS',
        'RIV'        => 'RIV',
        'RIVER'      => 'RIV',
        'RIVR'       => 'RIV',
        'RVR'        => 'RIV',
        'RD'         => 'RD',
        'ROAD'       => 'RD',
        'RDS'        => 'RDS',
        'ROADS'      => 'RDS',
        'ROUTE'      => 'RTE',
        'ROW'        => 'ROW',
        'RUE'        => 'RUE',
        'RUN'        => 'RUN',
        'SHL'        => 'SHL',
        'SHOAL'      => 'SHL',
        'SHLS'       => 'SHLS',
        'SHOALS'     => 'SHLS',
        'SHOAR'      => 'SHR',
        'SHORE'      => 'SHR',
        'SHR'        => 'SHR',
        'SHOARS'     => 'SHRS',
        'SHORES'     => 'SHRS',
        'SHRS'       => 'SHRS',
        'SKYWAY'     => 'SKWY',
        'SPG'        => 'SPG',
        'SPNG'       => 'SPG',
        'SPRING'     => 'SPG',
        'SPRNG'      => 'SPG',
        'SPGS'       => 'SPGS',
        'SPNGS'      => 'SPGS',
        'SPRINGS'    => 'SPGS',
        'SPRNGS'     => 'SPGS',
        'SPUR'       => 'SPUR',
        'SPURS'      => 'SPUR',
        'SQ'         => 'SQ',
        'SQR'        => 'SQ',
        'SQRE'       => 'SQ',
        'SQU'        => 'SQ',
        'SQUARE'     => 'SQ',
        'SQRS'       => 'SQS',
        'SQUARES'    => 'SQS',
        'STA'        => 'STA',
        'STATION'    => 'STA',
        'STATN'      => 'STA',
        'STN'        => 'STA',
        'STRA'       => 'STRA',
        'STRAV'      => 'STRA',
        'STRAVE'     => 'STRA',
        'STRAVEN'    => 'STRA',
        'STRAVENUE'  => 'STRA',
        'STRAVN'     => 'STRA',
        'STRVN'      => 'STRA',
        'STRVNUE'    => 'STRA',
        'STREAM'     => 'STRM',
        'STREME'     => 'STRM',
        'STRM'       => 'STRM',
        'ST'         => 'ST',
        'STR'        => 'ST',
        'STREET'     => 'ST',
        'STRT'       => 'ST',
        'STREETS'    => 'STS',
        'SMT'        => 'SMT',
        'SUMIT'      => 'SMT',
        'SUMITT'     => 'SMT',
        'SUMMIT'     => 'SMT',
        'TER'        => 'TER',
        'TERR'       => 'TER',
        'TERRACE'    => 'TER',
        'THROUGHWAY' => 'TRWY',
        'TRACE'      => 'TRCE',
        'TRACES'     => 'TRCE',
        'TRCE'       => 'TRCE',
        'TRACK'      => 'TRAK',
        'TRACKS'     => 'TRAK',
        'TRAK'       => 'TRAK',
        'TRK'        => 'TRAK',
        'TRKS'       => 'TRAK',
        'TRAFFICWAY' => 'TRFY',
        'TRFY'       => 'TRFY',
        'TR'         => 'TRL',
        'TRAIL'      => 'TRL',
        'TRAILS'     => 'TRL',
        'TRL'        => 'TRL',
        'TRLS'       => 'TRL',
        'TUNEL'      => 'TUNL',
        'TUNL'       => 'TUNL',
        'TUNLS'      => 'TUNL',
        'TUNNEL'     => 'TUNL',
        'TUNNELS'    => 'TUNL',
        'TUNNL'      => 'TUNL',
        'TPK'        => 'TPKE',
        'TPKE'       => 'TPKE',
        'TRNPK'      => 'TPKE',
        'TRPK'       => 'TPKE',
        'TURNPIKE'   => 'TPKE',
        'TURNPK'     => 'TPKE',
        'UNDERPASS'  => 'UPAS',
        'UN'         => 'UN',
        'UNION'      => 'UN',
        'UNIONS'     => 'UNS',
        'VALLEY'     => 'VLY',
        'VALLY'      => 'VLY',
        'VLLY'       => 'VLY',
        'VLY'        => 'VLY',
        'VALLEYS'    => 'VLYS',
        'VLYS'       => 'VLYS',
        'VDCT'       => 'VIA',
        'VIA'        => 'VIA',
        'VIADCT'     => 'VIA',
        'VIADUCT'    => 'VIA',
        'VIEW'       => 'VW',
        'VW'         => 'VW',
        'VIEWS'      => 'VWS',
        'VWS'        => 'VWS',
        'VILL'       => 'VLG',
        'VILLAG'     => 'VLG',
        'VILLAGE'    => 'VLG',
        'VILLG'      => 'VLG',
        'VILLIAGE'   => 'VLG',
        'VLG'        => 'VLG',
        'VILLAGES'   => 'VLGS',
        'VLGS'       => 'VLGS',
        'VILLE'      => 'VL',
        'VL'         => 'VL',
        'VIS'        => 'VIS',
        'VIST'       => 'VIS',
        'VISTA'      => 'VIS',
        'VST'        => 'VIS',
        'VSTA'       => 'VIS',
        'WALK'       => 'WALK',
        'WALKS'      => 'WALK',
        'WALL'       => 'WALL',
        'WAY'        => 'WAY',
        'WY'         => 'WAY',
        'WAYS'       => 'WAYS',
        'WELL'       => 'WL',
        'WELLS'      => 'WLS',
        'WLS'        => 'WLS',
    ];

    /**
     * Checks whether or not an address string is a PO box.
     *
     * @param string $string the string to check
     *
     * @return bool true if the specified string is a PO box and false if it
     *              is not
     */
    public static function isPoBoxLine($string)
    {
        $po_box_exp = '(p[.\s]*o[.\s]*|post\s+office\s+)box\s*';

        // escape delimiters
        $po_box_exp = str_replace('/', '\/', $po_box_exp);
        $po_box_exp = '/' . $po_box_exp . '/ui';

        return preg_match($po_box_exp, $string) == 1;
    }

    /**
     * Gets whether or not this address is for a PO box.
     *
     * @return bool true if this address is for a PO box and false if it is
     *              not
     */
    public function isPoBox()
    {
        return $this->po_box
            || self::isPoBoxLine($this->getLine1())
            || self::isPoBoxLine($this->getLine2());
    }

    /**
     * Checks the application's config and returns whether a key to a
     * verification service exists or not.
     *
     * @param StoreApplication $app the application that you want to check the
     *                              config for verification key
     *
     * @return bool true if there is a key for verification
     */
    public static function isVerificationAvailable(StoreApplication $app)
    {
        return isset($app->config->strikeiron->verify_address_usa_key);
    }

    /**
     * Displays this address in postal format.
     */
    public function display()
    {
        $span_tag = new SwatHtmlTag('span');
        $span_tag->class = 'vcard address';
        $span_tag->open();

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

        $span_tag->close();
    }

    /**
     * Displays this address in a two-line condensed form.
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

    /**
     * Displays this address in a two-line condensed form.
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

    public function copyFrom(StoreAddress $address)
    {
        $this->setFullName($address->getFullName());
        $this->setCompany($address->getCompany());
        $this->setLine1($address->getLine1());
        $this->setLine2($address->getLine2());
        $this->setCity($address->getCity());
        $this->setPostalCode($address->getPostalCode());
        $this->setProvStateOther($address->getProvstateOther());
        $this->setPhone($address->getPhone());

        $this->provstate = $address->getInternalValue('provstate');
        $this->country = $address->getInternalValue('country');
        $this->po_box = $address->po_box;
    }

    /**
     * Compares this address to another address.
     *
     * @param StoreAddress $address the address to compare this entry to
     *
     * @return bool true if all internal values match, and false if any
     *              don't match
     */
    public function compare(StoreAddress $address)
    {
        $equal = true;

        if ($this->getFullName() !== $address->getFullName()) {
            $equal = false;
        }

        if ($this->getCompany() !== $address->getCompany()) {
            $equal = false;
        }

        if ($this->getLine1() !== $address->getLine1()) {
            $equal = false;
        }

        if ($this->getLine2() !== $address->getLine2()) {
            $equal = false;
        }

        if ($this->getCity() !== $address->getCity()) {
            $equal = false;
        }

        if ($this->getProvStateOther() !== $address->getProvStateOther()) {
            $equal = false;
        }

        if ($this->getPostalCode() !== $address->getPostalCode()) {
            $equal = false;
        }

        if ($this->getPhone() !== $address->getPhone()) {
            $equal = false;
        }

        return $equal;
    }

    /**
     * Verify this address.
     *
     * @param mixed $modify
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

    /**
     * Compares this address to another address.
     *
     * @param StoreAddress $address the address to compare this entry to
     *
     * @return bool true if all internal values loosely match, and false if
     *              any don't match
     */
    public function mostlyEqual(StoreAddress $address)
    {
        $equal = true;

        if ($this->getFullName() != $address->getFullName()) {
            $equal = false;
        }

        if (trim(mb_strtoupper($this->getCompany())) !=
            trim(mb_strtoupper($address->getCompany()))) {
            $equal = false;
        }

        if (mb_strtoupper($this->getLine1()) != mb_strtoupper($address->getLine1())
            && !self::differByStreetSuffixOnly($this->getLine1(), $address->getLine1())
            && !self::differByStreetAbbreviationOnly(
                $this->getLine1(),
                $address->getLine1()
            )) {
            $equal = false;
        }

        if (trim(mb_strtoupper($this->getLine2())) !=
            trim(mb_strtoupper($address->getLine2()))) {
            $equal = false;
        }

        if (mb_strtoupper($this->getCity()) != mb_strtoupper($address->getCity())) {
            $equal = false;
        }

        if (mb_strtoupper($this->getProvStateOther()) !=
            mb_strtoupper($address->getProvStateOther())) {
            $equal = false;
        }

        if ($this->country->id != $address->country->id) {
            $equal = false;
        }

        if ($this->country->id === 'US') {
            if (mb_substr($this->getPostalCode(), 0, 5) !=
                mb_substr($address->getPostalCode(), 0, 5)) {
                $equal = false;
            }
        } else {
            if ($this->getPostalCode() != $address->getPostalCode()) {
                $equal = false;
            }
        }

        if ($this->getPhone() != $address->getPhone()) {
            $equal = false;
        }

        return $equal;
    }

    protected function init()
    {
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'provstate',
            SwatDBClassMap::get(StoreProvState::class)
        );

        $this->registerInternalProperty(
            'country',
            SwatDBClassMap::get(StoreCountry::class)
        );
    }

    /**
     * Displays this address in Canada Post format.
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

        if ($this->getCompany() != '') {
            $span_tag->class = 'fn org';
            $span_tag->setContent($this->getCompany());
            $span_tag->display();
            echo '<br />';
        }

        $address_span_tag = new SwatHtmlTag('span');
        $address_span_tag->class = 'adr';
        $address_span_tag->open();

        $this->displayLines();

        if ($this->getCity() != '') {
            $span_tag->class = 'locality';
            $span_tag->setContent($this->getCity());
            $span_tag->display();
            echo ' ';
        }

        if ($this->provstate !== null) {
            $abbr_tag = new SwatHtmlTag('abbr');
            $abbr_tag->class = 'region';
            $abbr_tag->title = $this->provstate->title;
            $abbr_tag->setContent($this->provstate->abbreviation);
            $abbr_tag->display();
        } elseif ($this->getProvStateOther() != '') {
            $span_tag->class = 'region';
            $span_tag->setContent($this->getProvStateOther());
            $span_tag->display();
        }

        echo '&nbsp;&nbsp;';

        $span_tag->class = 'postal-code';
        $span_tag->setContent($this->getPostalCode());
        $span_tag->display();
        echo '<br />';

        $span_tag->class = 'country-name';
        $span_tag->setContent($this->country->title);
        $span_tag->display();

        $address_span_tag->close();

        if ($this->getPhone() != '') {
            echo '<br />', Store::_('Phone: ');
            $span_tag->class = 'tel';
            $span_tag->setContent($this->getPhone());
            $span_tag->display();
        }
    }

    /**
     * Displays this address in Royal Mail format.
     *
     * Formatting rules for UK addresses are taken from
     * {@link http://www.royalmail.com/portal/rm/content1?catId=400126&mediaId=32700664}.
     */
    protected function displayGB()
    {
        echo SwatString::minimizeEntities($this->getFullName()), '<br />';

        if ($this->getCompany() != '') {
            echo SwatString::minimizeEntities($this->getCompany()), '<br />';
        }

        echo SwatString::minimizeEntities($this->getLine1()), '<br />';

        if ($this->getLine2() != '') {
            echo SwatString::minimizeEntities($this->getLine2()), '<br />';
        }

        echo SwatString::minimizeEntities($this->getCity()), '<br />';

        if ($this->getProvStateOther() != '') {
            echo SwatString::minimizeEntities($this->getProvStateOther()), '<br />';
        }

        echo SwatString::minimizeEntities($this->getPostalCode()), '<br />';

        echo SwatString::minimizeEntities($this->country->title), '<br />';

        if ($this->getPhone() != '') {
            printf(
                'Phone: %s',
                SwatString::minimizeEntities($this->getPhone())
            );
        }
    }

    /**
     * Displays this address in US Postal Service format.
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

        if ($this->getCompany() != '') {
            $span_tag->class = 'fn org';
            $span_tag->setContent($this->getCompany());
            $span_tag->display();
            echo '<br />';
        }

        $address_span_tag = new SwatHtmlTag('span');
        $address_span_tag->class = 'adr';
        $address_span_tag->open();

        $this->displayLines();

        if ($this->getCity() != '') {
            $span_tag->class = 'locality';
            $span_tag->setContent($this->getCity());
            $span_tag->display();
            echo ' ';
        }

        if ($this->provstate !== null) {
            $abbr_tag = new SwatHtmlTag('abbr');
            $abbr_tag->class = 'region';
            $abbr_tag->title = $this->provstate->title;
            $abbr_tag->setContent($this->provstate->abbreviation);
            $abbr_tag->display();
        } elseif ($this->getProvStateOther() != '') {
            $span_tag->class = 'region';
            $span_tag->setContent($this->getProvStateOther());
            $span_tag->display();
        }

        echo '&nbsp;&nbsp;';

        $span_tag->class = 'postal-code';
        $span_tag->setContent($this->getPostalCode());
        $span_tag->display();
        echo '<br />';

        $span_tag->class = 'country-name';
        $span_tag->setContent($this->country->title);
        $span_tag->display();

        $address_span_tag->close();

        if ($this->getPhone() != '') {
            echo '<br />', Store::_('Phone: ');
            $span_tag->class = 'tel';
            $span_tag->setContent($this->getPhone());
            $span_tag->display();
        }
    }

    /**
     * Displays this address in a two-line condensed form.
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

        if ($this->getCompany() != '') {
            $span_tag->class = 'fn org';
            $span_tag->setContent($this->getCompany());
            $span_tag->display();
            echo ', ';
        }

        $address_span_tag = new SwatHtmlTag('span');
        $address_span_tag->class = 'adr';
        $address_span_tag->open();

        if ($this->getLine1() != '') {
            $span_tag->class = 'street-address';
            $span_tag->setContent($this->getLine1());
            $span_tag->display();

            if ($this->getLine2() != '') {
                echo ', ';
                $span_tag->class = 'extended-address';
                $span_tag->setContent($this->getLine2());
                $span_tag->display();
            }
        }

        if ($this->getFullName() != '' || $this->getCompany() != ''
            || $this->getLine1() != '') {
            echo '<br />';
        }

        if ($this->getCity() != '') {
            $span_tag->class = 'locality';
            $span_tag->setContent($this->getCity());
            $span_tag->display();
            echo ' ';
        }

        if ($this->provstate !== null) {
            $abbr_tag = new SwatHtmlTag('abbr');
            $abbr_tag->class = 'region';
            $abbr_tag->title = $this->provstate->title;
            $abbr_tag->setContent($this->provstate->abbreviation);
            $abbr_tag->display();
        } elseif ($this->getProvStateOther() != '') {
            $span_tag->class = 'region';
            $span_tag->setContent($this->getProvStateOther());
            $span_tag->display();
        }

        if ($this->getPostalCode() !== null) {
            echo '&nbsp;&nbsp;';
            $span_tag->class = 'postal-code';
            $span_tag->setContent($this->getPostalCode());
            $span_tag->display();
        }

        echo ', ';

        $span_tag->class = 'country-name';
        $span_tag->setContent($this->country->title);
        $span_tag->display();

        $address_span_tag->close();

        if ($this->getPhone() != '') {
            echo '<br />', Store::_('Phone: ');
            $span_tag->class = 'tel';
            $span_tag->setContent($this->getPhone());
            $span_tag->display();
        }
    }

    /**
     * Displays this address in a two-line condensed form.
     */
    protected function displayCondensedGB()
    {
        echo SwatString::minimizeEntities($this->getFullName()), ', ';

        if ($this->getCompany() != '') {
            echo SwatString::minimizeEntities($this->getCompany()), ', ';
        }

        echo SwatString::minimizeEntities($this->getLine1());
        if ($this->getLine2() != '') {
            echo ', ', SwatString::minimizeEntities($this->getLine2());
        }

        echo '<br />';
        echo SwatString::minimizeEntities($this->getCity());

        if ($this->getProvStateOther() != '') {
            echo ', ', SwatString::minimizeEntities($this->getProvStateOther());
        }

        if ($this->getPostalCode() !== null) {
            echo ', ', SwatString::minimizeEntities($this->getPostalCode());
        }

        echo ', ', SwatString::minimizeEntities($this->country->title);

        if ($this->getPhone() != '') {
            echo '<br />';
            printf(
                Store::_('Phone: %s'),
                SwatString::minimizeEntities($this->getPhone())
            );
        }
    }

    /**
     * Displays this address in a two-line condensed form.
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

        if ($this->getCompany() != '') {
            $span_tag->class = 'fn org';
            $span_tag->setContent($this->getCompany());
            $span_tag->display();
            echo ', ';
        }

        $address_span_tag = new SwatHtmlTag('span');
        $address_span_tag->class = 'adr';
        $address_span_tag->open();

        if ($this->getLine1() != '') {
            $span_tag->class = 'street-address';
            $span_tag->setContent($this->getLine1());
            $span_tag->display();

            if ($this->getLine2() != '') {
                echo ', ';
                $span_tag->class = 'extended-address';
                $span_tag->setContent($this->getLine2());
                $span_tag->display();
            }
        }

        if ($this->getFullName() != '' || $this->getCompany() != ''
            || $this->getLine1() != '') {
            echo '<br />';
        }

        if ($this->getCity() != '') {
            $span_tag->class = 'locality';
            $span_tag->setContent($this->getCity());
            $span_tag->display();
            echo ' ';
        }

        if ($this->provstate !== null) {
            $abbr_tag = new SwatHtmlTag('abbr');
            $abbr_tag->class = 'region';
            $abbr_tag->title = $this->provstate->title;
            $abbr_tag->setContent($this->provstate->abbreviation);
            $abbr_tag->display();
        } elseif ($this->getProvStateOther() != '') {
            $span_tag->class = 'region';
            $span_tag->setContent($this->getProvStateOther());
            $span_tag->display();
        }

        if ($this->getPostalCode() !== null) {
            echo '&nbsp;&nbsp;';
            $span_tag->class = 'postal-code';
            $span_tag->setContent($this->getPostalCode());
            $span_tag->display();
        }

        echo ', ';

        $span_tag->class = 'country-name';
        $span_tag->setContent($this->country->title);
        $span_tag->display();

        $address_span_tag->close();

        if ($this->getPhone() != '') {
            echo '<br />', Store::_('Phone: ');
            $span_tag->class = 'tel';
            $span_tag->setContent($this->getPhone());
            $span_tag->display();
        }
    }

    /**
     * Displays this address in a two-line condensed form.
     */
    protected function displayCondensedAsTextCA()
    {
        echo $this->getFullName(), ', ';

        if ($this->getCompany() != '') {
            echo $this->getCompany(), ', ';
        }

        echo $this->getLine1();
        if ($this->getLine2() != '') {
            echo ', ', $this->getLine2();
        }

        echo "\n";

        echo $this->getCity(), ' ';

        if ($this->provstate !== null) {
            echo $this->provstate->abbreviation;
        } elseif ($this->getProvStateOther() != '') {
            echo $this->getProvStateOther();
        }

        if ($this->getPostalCode() !== null) {
            echo '  ';
            echo $this->getPostalCode();
        }
        echo ', ';

        echo $this->country->title;

        if ($this->getPhone() != '') {
            echo "\n";
            printf(
                Store::_('Phone: %s'),
                SwatString::minimizeEntities($this->getPhone())
            );
        }
    }

    /**
     * Displays this address in a two-line condensed form.
     */
    protected function displayCondensedAsTextGB()
    {
        echo $this->getFullName(), ', ';

        if ($this->getCompany() != '') {
            echo $this->getCompany(), ', ';
        }

        echo $this->getLine1();
        if ($this->getLine2() != '') {
            echo ', ', $this->getLine2();
        }

        echo "\n";

        echo $this->getCity();
        if ($this->getProvStateOther() != '') {
            echo ', ', $this->getProvStateOther();
        }

        if ($this->getPostalCode() !== null) {
            echo ', ', $this->getPostalCode();
        }

        echo ', ', $this->country->title;

        if ($this->getPhone() != '') {
            echo "\n";
            printf(
                Store::_('Phone: %s'),
                SwatString::minimizeEntities($this->getPhone())
            );
        }
    }

    /**
     * Displays this address in a two-line condensed form.
     */
    protected function displayCondensedAsTextUS()
    {
        echo $this->getFullName(), ', ';

        if ($this->getCompany() != '') {
            echo $this->getCompany(), ', ';
        }

        echo $this->getLine1();
        if ($this->getLine2() != '') {
            echo ', ', $this->getLine2();
        }

        echo "\n";

        echo $this->getCity(), ' ';

        if ($this->provstate !== null) {
            echo $this->provstate->abbreviation;
        } elseif ($this->getProvStateOther() != '') {
            echo $this->getProvStateOther();
        }

        if ($this->getPostalCode() !== null) {
            echo '  ';
            echo $this->getPostalCode();
        }
        echo ', ';

        echo $this->country->title;

        if ($this->getPhone() != '') {
            echo "\n";
            printf(
                Store::_('Phone: %s'),
                SwatString::minimizeEntities($this->getPhone())
            );
        }
    }

    protected function displayLines()
    {
        if ($this->getLine1() != '') {
            echo '<span class="street-address">';

            $line1 = SwatString::minimizeEntities($this->getLine1());
            $line1 = preg_replace('/\s*\n\s*/', "\n", $line1);
            $line1 = nl2br(trim($line1));
            echo $line1;

            echo '</span>';
            echo '<br />';

            if ($this->getLine2() != '') {
                echo '<span class="extended-address">';

                $line2 = SwatString::minimizeEntities($this->getLine2());
                $line2 = preg_replace('/\s*\n\s*/', "\n", $line2);
                $line2 = nl2br(trim($line2));
                echo $line2;

                echo '</span>';
                echo '<br />';
            }
        }
    }

    protected function verifyUS(SiteApplication $app, $modify)
    {
        $valid = false;
        $key = $app->config->strikeiron->verify_address_usa_key;

        if ($key === null) {
            return $valid;
        }

        $options = ['timeout' => 1];

        $strikeiron = new Services_StrikeIron_VerifyAddressUsa(
            $key,
            '',
            $options
        );

        $address = [
            'addressLine1'   => $this->getLine1(),
            'addressLine2'   => $this->getLine2(),
            'city_state_zip' => sprintf(
                '%s %s %s',
                $this->getCity(),
                $this->provstate->abbreviation,
                $this->getPostalCode()
            ),
            'firm'         => $this->company,
            'urbanization' => '',
            'casing'       => 'Proper',
        ];

        try {
            $result = $strikeiron->VerifyAddressUSA($address);
            $valid = $result->VerifyAddressUSAResult->AddressStatus === 'Valid';

            if ($modify) {
                $this->setLine1($result->VerifyAddressUSAResult->AddressLine1);
                $this->setLine2($result->VerifyAddressUSAResult->AddressLine2);
                $this->setCity($result->VerifyAddressUSAResult->City);
                $this->setCompany($result->VerifyAddressUSAResult->Firm);
                $this->setPostalCode($result->VerifyAddressUSAResult->ZipPlus4);
                $this->po_box =
                    ($result->VerifyAddressUSAResult->RecordType === 'P');

                if ($this->provstate->abbreviation !==
                    $result->VerifyAddressUSAResult->State) {
                    $class = SwatDBClassMap::get(StoreProvState::class);
                    $provstate = new $class();
                    $provstate->setDatabase($app->db);
                    $provstate->loadFromAbbreviation(
                        $result->VerifyAddressUSAResult->State,
                        'US'
                    );

                    $this->provstate = $provstate;
                }
            }
        } catch (SoapFault $e) {
            $e = new SwatException($e);
            $e->processAndContinue();
            $valid = true;
        } catch (Services_StrikeIron_SoapException $e) {
            $e = new SwatException($e);
            $e->processAndContinue();
            $valid = true;
        }

        return $valid;
    }

    protected function verifyCA(SiteApplication $app, $modify)
    {
        // TODO: actually verify canadian addresses.
        return true;
    }

    protected function getProtectedPropertyList()
    {
        return array_merge(
            parent::getProtectedPropertyList(),
            [
                'fullname' => [
                    'get' => 'getFullName',
                    'set' => 'setFullName',
                ],
                'company' => [
                    'get' => 'getCompany',
                    'set' => 'setCompany',
                ],
                'line1' => [
                    'get' => 'getLine1',
                    'set' => 'setLine1',
                ],
                'line2' => [
                    'get' => 'getLine2',
                    'set' => 'setLine2',
                ],
                'city' => [
                    'get' => 'getCity',
                    'set' => 'setCity',
                ],
                'provstate_other' => [
                    'get' => 'getProvStateOther',
                    'set' => 'setProvStateOther',
                ],
                'postal_code' => [
                    'get' => 'getPostalCode',
                    'set' => 'setPostalCode',
                ],
                'phone' => [
                    'get' => 'getPhone',
                    'set' => 'setPhone',
                ],
            ]
        );
    }

    private static function differByStreetSuffixOnly($a, $b)
    {
        $result = false;

        if (mb_strlen($a) === mb_strlen($b)) {
            return $result;
        }

        if (mb_strlen($a) > mb_strlen($b)) {
            $long = $a;
            $short = $b;
        } else {
            $long = $b;
            $short = $a;
        }

        $suffix = mb_substr($long, mb_strlen($short));
        $suffix = mb_strtoupper(trim($suffix));

        return in_array($suffix, self::$street_suffixes);
    }

    private static function differByStreetAbbreviationOnly($a, $b)
    {
        $result = false;

        if (mb_strlen($a) === mb_strlen($b)) {
            return $result;
        }

        $a = explode(' ', trim(mb_strtoupper($a)));
        $b = explode(' ', trim(mb_strtoupper($b)));

        $suffix1 = implode(' ', array_diff_assoc($a, $b));
        $suffix2 = implode(' ', array_diff_assoc($b, $a));

        if (array_key_exists($suffix1, self::$street_suffixes)
            && self::$street_suffixes[$suffix1] === $suffix2) {
            $result = true;
        }

        if (array_key_exists($suffix2, self::$street_suffixes)
            && self::$street_suffixes[$suffix2] === $suffix1) {
            $result = true;
        }

        return $result;
    }

    // getters

    /**
     * Gets the full name of the person at this address.
     *
     * Having this method allows subclasses to split the full name into an
     * arbitrary number of fields. For example, first name and last name.
     *
     * @return string the full name of the person at this address
     */
    public function getFullName()
    {
        return $this->fullname;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getLine1()
    {
        return $this->line1;
    }

    public function getLine2()
    {
        return $this->line2;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getProvStateOther()
    {
        return $this->provstate_other;
    }

    public function getPostalCode()
    {
        return $this->postal_code;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    // setters

    public function setFullName($fullname)
    {
        $this->fullname = $fullname;
    }

    public function setCompany($company)
    {
        $this->company = $company;
    }

    public function setLine1($line1)
    {
        $this->line1 = $line1;
    }

    public function setLine2($line2)
    {
        $this->line2 = $line2;
    }

    public function setCity($city)
    {
        $this->city = $city;
    }

    public function setProvStateOther($provstate_other)
    {
        $this->provstate_other = $provstate_other;
    }

    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }
}
