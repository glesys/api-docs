<?php
    /*
    *   Author: Tobias Rahm
    *   Date:   2015-11-28
    */
    require_once __DIR__.'/../api_classes/idna_convert.php';

    echo PHP_EOL;
    if(count($argv) < 3) {
        echo 'Err: 3 arguments required.'.PHP_EOL;
        echo 'php php_glesys_zone_import.php <DNS zone file> <api user> <api key>'.PHP_EOL;
        exit();
    }

    $file = $argv[1];
    $api_user	= $argv[2];
	$api_key	= $argv[3];
	$api_url	= 'https://api.glesys.com/';

    $parser = new File_DNS();

    $domain = $parser->getDomain($file);

    if($domain == null) {
        if(ISSET($argv[4])) {
            $domain = $argv[4];
            echo 'Log: Retrieving domain from argument list: '.$argv[4].PHP_EOL;
        }
        else {
            echo "Log: Domain not found. Create manually? (y/n) ";
            $handle = fopen ("php://stdin","r");
            $line = fgets($handle);
            if(trim($line) == 'y'){
                echo "Write domain name: ";
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if(trim($line)){
                    $domain = trim($line);
                }
                else {
                    echo 'Log: No domain provided.'.PHP_EOL;
                    echo 'Log: Exiting';
                    exit();
                }
            }
            else {
                echo "Log: Exiting";
                exit();
            }
        }
    }

    $parser->load($domain, $file);
    
    $glesys = new glesys_api();
    $glesys->api_user = $api_user;
    $glesys->api_key = $api_key;
    $glesys->api_url = $api_url;
    

    echo 'Log: Adding domain...'.PHP_EOL;

    $domainAdded = $glesys->add_domain(trim($parser->_SOA["name"], '.'), array(
        "ttl" => $parser->_SOA["ttl"],
        "primarynameserver" => $parser->_SOA["origin"],
        "responsibleperson" => $parser->_SOA["person"],
        "refresh" => $parser->_SOA["refresh"],
        "retry" => $parser->_SOA["retry"],
        "expire" => $parser->_SOA["expire"],
        "minimum" => $parser->_SOA["minimum"],
        "createrecords" => 0
    ));
    
    if(!$domainAdded) {
        echo 'Err: Failed to add domain!'.PHP_EOL.PHP_EOL;
        
        echo "Continue adding records? (y/n) ";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        if(trim($line) != 'y'){
            echo 'Log: Exiting';
            exit();
        }
    }
    else {
        echo 'Log: Successfully added domain'.PHP_EOL;
    }
    
    $successfullRecords = 0;
    $failedRecords = 0;
    $ptrRecords = 0;
    echo 'Log: Adding '.count($parser->_records).' records...'.PHP_EOL;

    $failedRecordsCounter = array(
        'SOA' => 0,
        'A' => 0,
        'AAAA' => 0,
        'NS' => 0,
        'MX' => 0,
        'CNAME' => 0,
        'PTR' => 0,
        'TXT' => 0,
        'SRV' => 0
    );

    foreach($parser->_records as $index => $record) {
        if(strtolower($record["type"]) == 'ptr') {
            if($ptrRecords == 0) {
                echo 'Log: No support for PTR records. Skipping...'.PHP_EOL;
            }
            $ptrRecords++;
        }
        else {
            $recordAdded = $glesys->add_record(array(
                "domainname" => trim($parser->_SOA["name"], "."),
                "host" => $record["name"],
                "ttl" => $record["ttl"],
                "type" => $record["type"],
                "data" => trim($record["data"], "\"' ")
            ));
            if($recordAdded) {
                $successfullRecords++;
            }
            else {
                $failedRecords++;
                $failedRecordsCounter[strtoupper($record["type"])]++;
                echo "Wrn: Failed to add record, continue? (y/n) ";
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if(trim($line) != 'y'){
                    echo 'Log: Exiting';
                    exit();
                }
            }
        }
    };

    $recordsCounter = $parser->getRecordsCounter();

    foreach($failedRecordsCounter as $key => $value) {
        $recordsCounter[$key] -= $value;
    }

    echo PHP_EOL.'Log: --- Summary ---'.PHP_EOL;
    foreach($recordsCounter as $key => $value) {
        switch(strtoupper($key)) {
            case 'PTR':
                break;
            default:
                if($value > 0) {
                    $spaces = 8 - strlen($key);
                    echo 'Log: '.$key;
                    for($i=0; $i < $spaces; $i++) {
                        echo ' ';
                    }
                    echo $value.PHP_EOL;
                }
        }
    }
    echo 'Log: Total   '.$successfullRecords.PHP_EOL;
    if($ptrRecords > 0) {
        echo 'Log: Skipped '.$ptrRecords.' records'.PHP_EOL;
    }
    echo 'Log: '.$failedRecords.' failed.'.PHP_EOL;
    
    
    exit();

    /**
     * Cannot open file.
     */
    define('FILE_DNS_FILE_READALL_FAILED',  -1);

    /**
     * Cannot save to file.
     */
    define('FILE_DNS_FILE_WRITE_FAILED',    -2);

    /**
     * SOA Parse Failed.
     */
    define('FILE_DNS_PARSE_SOA_FAILED',     -3);

    /**
     * RR Parse failed.
     */
    define('FILE_DNS_PARSE_RR_FAILED',      -4);

    /**
     * Parsing 1X to seconds failed.
     */
    define('FILE_DNS_PARSE_TIME_FAILED',    -5);

    /**
     * Parsing seconds to 1X failed.
     */
    define('FILE_DNS_PARSEBACK_TIME_FAILED',-6);

    /**
     * Can't render, zone not loaded yet.
     */
    define('FILE_DNS_RENDER_NOT_LOADED',    -7);

    /**
     * Can't set domain. Invalid Domain name.
     */
    define('FILE_DNS_INVALID_DOMAIN',       -8);

    /**
     * Can't update/set SOA
     */
    define('FILE_DNS_UPDATE_SOA_FAILED',    -9);

    // }}}
    // {{{ File_DNS

    /**
     * an RFC1033 style zonefile editor
     *
     * The File::DNS class provides an Object Oriented
     * interface to read, edit and create DNS Zonefiles.
     *
     * @category   File
     * @package    File_DNS
     * @author     Cipriano Groenendal <cipri@php.net>
     * @copyright  2004-2005 Cipriano Groenendal <cipri@php.net>
     * @license    http://www.php.net/license/3_0.txt PHP License 3.0
     * @version    Release: @version@
     * @link       http://pear.php.net/package/File_DNS
     * @link       http://www.rfc-editor.org/rfc/rfc1033.txt
     * @link       http://www.rfc-editor.org/rfc/rfc1537.txt
     * @link       http://www.rfc-editor.org/rfc/rfc2308.txt
     * @todo       Add addRecord, delRecord functions for basic editing.
     * @todo       Add create() function to start from scratch.
     * @todo       Fix examples
     */
    class File_DNS
    {

        var $recordsCounter = array(
            'SOA' => 0,
            'A' => 0,
            'AAAA' => 0,
            'NS' => 0,
            'MX' => 0,
            'CNAME' => 0,
            'PTR' => 0,
            'TXT' => 0,
            'SRV' => 0
        );
        // {{{ properties

        /**
         * contains the domainname of the loaded zone
         *
         * The domainname will automaticly be appended
         * to any and all records. Unused if set to null.
         *
         * @var string
         * @see load, _parseZone
         * @access private
         */
        var $_domain = null;

        /**
         * contains the filename of the loaded zone
         *
         * This is the currently loaded filename, and is
         * also used during save to write to again.
         *
         * @var string
         * @see load, save
         */
        var $_filename = null;

        /**
         * SOA Record of the loaded zone.
         *
         * This contains all the relevant data stored
         * in the SOA (Start of Authority) record.
         * It's stored in an associative array, that
         * should be pretty self-explaining.
         * <pre>
         * Array
         *   (
         *       [name] => example.com.
         *       [ttl] => 345600
         *       [class] => IN
         *       [origin] => ns1.example.com.
         *       [person] => hostmaster.example.com.
         *       [serial] => 204041514
         *       [refresh] => 14400
         *       [retry] => 1800
         *       [expire] => 86400
         *       [minimum] => 10800
         *   )
         * </pre>
         * @var array
         * @see _parseZone, _parseSOA, setSOAValue.
         */
        var $_SOA = array();

        /**
         * contains all the records in this zone.
         *
         * An unindexed array of Resource Records (RR's)
         * for this zone. Each item is a separate RR.
         * It's format should be pretty self explaining.
         * See manual for exact definition.
         *
         * @var array
         * @see _parseZone
         */
        var $_records = array();

        /**
         * contains all supported Resource Records.
         *
         * This list contains all supported resource records.
         * This currently is:
         *
         * SOA
         * A
         * AAAA
         * NS
         * MX
         * CNAME
         * PTR
         * TXT
         *
         * @var array
         * @see _parseRR
         */
        var $_types = array('SOA', 'A', 'AAAA', 'NS', 'MX', 'CNAME', 'PTR', 'TXT', 'SRV');

        /**
         * zonefile modification check
         *
         * This checks whether the loaded zonefile has been modified.
         * If so, we need to generate a new serial when we render it.
         *
         * @var bool
         * @see generateZone, setDomainName, setTTL,
         * @see addRecord, replaceRecord, setRecord, delRecord
         */
        var $_isModified = false;


        /**
         * package Version
         *
         * @var string
         */
        var $version = '@version@';


        function getRecordsCounter() {
            return $this->recordsCounter;
        }
        
        function getDomain($zonefile) {
            $domain = null;
            $lines = file($zonefile);
            foreach ($lines as $line_num => $line) {
                if(strpos(trim($line), "\$ORIGIN") === 0) {
                    $domain = trim(substr($line, 7));
                    if(strpos($domain, ";")) {
                        $domain = trim(substr($domain, 0, strpos($domain, ";"))); 
                    }
                }
            }
            return $domain;
        }
        
        function load($domain, $zonefile, $lock = false)
        {
            //First, clean off the object.
            $this->free();
            if(!is_file($zonefile)) {
                echo 'Err: Cannot find zone file "'.$zonefile.'".'.PHP_EOL;
                echo 'Err: Exiting'.PHP_EOL;
                exit();
            }
            $zone = file_get_contents($zonefile);
            
//            if (PEAR::isError($zone)) {
//                //File package doesn't have codes associated with errors,
//                //so raise our own.
//                return PEAR::raiseError("Unable to read file $zonefile",
//                                        FILE_DNS_FILE_READALL_FAILED,
//                                        NULL, NULL, $zonefile);
//            }
            
            $lines = file($zonefile);
            foreach ($lines as $line_num => $line) {
                if(strpos(trim($line), "\$ORIGIN") === 0) {
                    $domain = trim(substr($line, 7));
                    if(strpos($domain, ";")) {
                        $domain = trim(substr($domain, 0, strpos($domain, ";"))); 
                    }
                }
                else if(strpos(trim($line), "\$TTL") === 0) {
                    $this->_ttl = trim(substr($line, 4));
                    if(strpos($this->_ttl, ";")) {
                        $this->_ttl = trim(substr($this->_ttl, 0, strpos($this->_ttl, ";"))); 
                    }
                }
            }
            
            
            $ret = $this->setDomainName($domain);
//            if (PEAR::isError($ret)) {
//                return $ret;
//            }
            $this->_filename = $zonefile;
            $parse = $this->_parseZone($zone);
            $this->_isModified = false;
            return $parse;
        }

        // }}}
        // {{{ Parsing
        // {{{ _parseZone()


        /**
         * parses a zonefile to object
         *
         * This function parses the zonefile and saves the data
         * collected from it to the _domain, _SOA and _records variables.
         *
         * @param string $zone  The zonefile to parse.
         * @return bool  true on success, PEAR Error on failure.
         */
        function _parseZone($zone)
        {
            //RFC1033: A semicolon (';') starts a comment; the
            //remainder of the line is ignored.
            $zone = preg_replace('/(;.*)$/m', '', $zone);

            //FIXME
            //There has to be an easier way to do that, but for now it'll do.

            //RFC1033: Parenthesis ('(',')') are used to group
            //data that crosses a line boundary.
            $zone = preg_replace_callback(
                '/(\([^()]*\))/',
                function ($matches) {
                    return str_replace("\n", "", $matches[0]);
                },
                $zone);
            $zone = str_replace('(', '', $zone);
            $zone = str_replace(')', '', $zone);


            /*
             * Origin is the current origin(@) that we're at now.
             * OriginFQDN is the FQDN origin, that gets appended to
             * non FQDN origins.
             *
             * FQDN == Fully Qualified Domain Name.
             *
             * Example:
             *
             *  $ORIGIN example.com.
             *  $ORIGIN sub1
             *  @ is sub1.example.com.
             *  $ORIGIN sub2
             *  @ is sub2.example.com.
             *  $ORIGIN new.sub3.example.com.
             *  @ is new.sub3.example.com.
             */

            $originFQDN = $origin = $current = $this->_domain;
            $ttl = 86400; //RFC1537 advices this value as a default TTL.

            $zone = explode("\n", $zone);
            foreach ($zone as $line) {
                $line = rtrim($line);
                $line = preg_replace('/\s+/', ' ', $line);

                $record = array();
                if (!$line) {
                    //Empty lines are stripped.
                } elseif (preg_match('/^\$TTL([^0-9]*)([0-9]+)/i',
                                     $line, $matches)) {
                    //RFC 2308 defins the $TTL keyword as default TTL from here.
                    $ttl = intval($matches[2]);
                } elseif (preg_match('/^\$ORIGIN (.*\.)/', $line, $matches)) {
                    //FQDN origin. Note the trailing dot(.)
                    $origin = $originFQDN = trim($matches[1]);
                } elseif (preg_match('/^\$ORIGIN (.*)/', $line, $matches)) {
                    //New origin. Append to current origin.
                    $origin = trim($matches[1]) . '.' . $origin;
                } elseif (stristr($line, ' SOA ')) {
                    if ($this->_SOA) {
                        //SOA already set. Only one per zone is possible.
                        //Done parsing.
                        //A second SOA is added by programs such as dig,
                        //to indicate the end of a zone.
                        break;
                    }
                    $soa = $this->_parseSOA($line, $origin, $ttl);
//                    if (PEAR::isError($soa)) {
//                        return $soa;
//                    }
                    $soa = $this->setSOAValue($soa);
//                    if (PEAR::isError($soa)) {
//                        return $soa;
//                    }
                } else {
                    $rr = $this->_parseRR($line, $origin, $ttl, $current);
//                    if (PEAR::isError($rr)){
//                        return $rr;
//                    }
                    $current = $rr['name'];
                    $this->_records[] = $rr;
                }
            }
            return true;
        }

        // }}}
        // {{{ _parseSOA()

        /**
         * parses a SOA (Start Of Authority) record line.
         *
         * This function returns the parsed SOA in array form.
         *
         * @param string $line   the SOA line to be parsed.
         *                       Should be stripped of comments and on 1 line.
         * @param string $origin the current origin of this SOA record
         * @param int    $ttl    the TTL of this record
         * @return array array of SOA info to be saved on success,
         *               PEAR error object on failure.
         */
        function _parseSOA($line, $origin, $ttl)
        {
            $soa = array();
            $regexp = '/(.*) SOA (\S*) (\S*) (\S*) (\S*) (\S*) (\S*) (\S*)/i';
            preg_match($regexp, $line, $matches);
            if (sizeof($matches) != 9) {
                return PEAR::raiseError('Unable to parse SOA.',
                                        FILE_DNS_PARSE_SOA_FAILED);
            }
            $pre = explode(' ', strtolower($matches[1]));
            if ($pre[0] == '@') {
                $soa['name'] = $origin;
            } else {
                $soa['name'] = $pre[0];
            }
            if (isset($pre[1])) {
                if (strtoupper($pre[1]) == 'IN') {
                    $soa['ttl'] = $ttl;
                    $soa['class'] = 'IN';
                } else {
                    $soa['ttl'] = $this->parseToSeconds($pre[1]);
                }
                if (isset($pre[2])) {
                    $soa['class'] = $pre[2];
                }
            } else {
                $soa['ttl'] = $ttl;
                $soa['class'] = 'IN';
            }
            $soa['origin']  = $matches[2];
            $soa['person']  = $matches[3];
            $soa['serial']  = $matches[4];
            $soa['refresh'] = $this->parseToSeconds($matches[5]);
            $soa['retry']   = $this->parseToSeconds($matches[6]);
            $soa['expire']  = $this->parseToSeconds($matches[7]);
            $soa['minimum'] = $this->parseToSeconds($matches[8]);
//            foreach (array_values($soa) as $item) {
//                //Scan all items to see if any are a pear error.
//                if (PEAR::isError($item)) {
//                    return $item;
//                }
//            }
            return $soa;
        }

        // }}}
        // {{{ _parseRR()

        /**
         * parses a (Resource Record) into an array
         *
         * @param string  $line    the RR line to be parsed.
         * @param string  $origin  the current origin of this record.
         * @param int     $ttl     the TTL of this record.
         * @param string  $current the current domainname we're working on.
         * @return array  array of RR info to be saved on success,
         *                PEAR error object on failure.
         */
        function _parseRR($line, $origin, $ttl, $current)
        {
            $record = array();
            $items = explode(' ', $line);
            $record['name'] = $items[0];
            $record['ttl'] = null;
            $record['class'] = null;
            $record['type'] = null;
            $record['data'] = null;
            if (!$record['name']) {
                //No name specified, inherit current name.
                $record['name'] = $current;
            }
            
            unset($items[0]);
            foreach ($items as $key => $item) {
                
                $item = trim($item);
                if (preg_match('/^[0-9]/', $item) &&
                          is_null($record['ttl'])) {
                    //Only a TTL can start with a number.
                    $record['ttl'] = $this->parseToSeconds($item);
                } elseif ((strtoupper($item) == 'IN') &&
                          is_null($record['class'])) {
                    //This is the class definition.
                    $record['class'] = 'IN';
                } elseif (array_search($item, $this->_types) &&
                          is_null($record['type'])) {
                    //We found our type!
                    if (is_null($record['ttl'])) {
                        //TTL was left out. Use default.
                        $record['ttl'] = $ttl;
                        $gotTTL = 1;
                    }
                    if (is_null($record['class'])) {
                        //Class was left out. Use default.
                        $record['class'] = 'IN';
                        $gotClass = 1;
                    }
                    $record['type'] = $item;
                    
                    $this->recordsCounter[strtoupper($item)]++;
                } elseif (!is_null($record['type'])) {
                    //We found out what type we are. This must be the data field.
                    switch (strtoupper($record['type'])) {
                    case 'A':
                    case 'AAAA':
                    case 'NS':
                    case 'CNAME':
                    case 'PTR':
                        $record['data'] = $item;
                        
                        break 2;

                    case 'MX':
                        //MX have an extra element. Save both right away.
                        //The setting itself is in the next item.
                        $record['data'] = $item.' '.$items[$key+1];
                        break 2;

                        
                    case 'SRV':
                        $record['data'] = $items[$key].' '.$items[$key+1].' '.$items[$key+2].' '.$items[$key+3];
                        break 2;
                        
                    case 'TXT':
                        $record['data'] .= ' ' . $item;
                        break 1;
                    default:
                        return PEAR::raiseError('Unable to parse RR. ' .
                                                $record['type'] .
                                                ' not recognized.',
                                                FILE_DNS_PARSE_RR_FAILED,
                                                NULL, NULL, $record['type']);
                        break 2;
                    }
                    //We're done parsing this RR now. Break out of the loop.
                } else {
                    print_r($item);
                    echo 'Err: Unable to parse RR. ' . $item . ' not recognized'.PHP_EOL;
                    return;
                }
            }
            foreach (array_values($record) as $item) {
                //Scan all items to see if any are a pear error.
//                if (PEAR::isError($item)) {
//                    return $item;
//                }
            }
            return $record;

        }

        // }}}
        // }}}
        // {{{ free()

        /**
         * resets the object so one can load another file
         *
         * @return bool     true
         */
        function free()
        {
            $this->_domain = null;
            $this->_filename = null;
            $this->_SOA = array();
            $this->_records = array();
            $this->_isModified = false;
            return true;
        }

        // }}}
        // {{{ Saving
        // {{{ toString()


        /**
         * returns a string with the zonefile generated from this object.
         *
         * @param  string  $separator The lineending separator. Defaults to \n
         * @return string  The generated zone, PEAR Error on failure.
         */
        function toString($separator = "\n")
        {
            $zone = $this->_generateZone();
            if (PEAR::isError($zone)) {
                return $zone;
            }
            $zone = implode($separator, $zone);
            return $zone;
        }

        // }}}
        // {{{ save()

        /**
         * saves the zonefile back to the file.
         *
         * @param   string $filename  the filename to save to.
         *                            Defaults to the loaded file.
         * @param   string $separator the lineending separator.
         *                            Defaults to \n.
         * @param   int    $lock      file-lock type to use.
         *                            Defaults to FALSE (none)
         * @return  true   true on success, PEAR Error on failure.
         */
        function save($filename = null, $separator = "\n", $lock = false)
        {
            if ($filename == null) {
             $filename = $this->_filename;
            }
            $zone = $this->_generateZone();
            $zone = implode($separator, $zone);
            $save = File::write($filename, $zone, FILE_MODE_WRITE, $lock);
            if (PEAR::isError($save)) {
                //File package doesn't have codes associated with errors,
                //so raise our own.
                return PEAR::raiseError("Unable to save file $filename",
                                        FILE_DNS_FILE_WRITE_FAILED,
                                        NULL, NULL, $filename);
            }
            return true;
        }

        // }}}
        // {{{ _generateZone()

        /**
         * generates a new zonefile.
         *
         * @return array The generated zonefile, PEAR Error on failure.
         */
        function _generateZone()
        {
            $zone = array();
            if (!$this->_SOA) {
                return PEAR::raiseError('Unable to render zone. No zone loaded.',
                                        FILE_DNS_RENDER_NOT_LOADED);
            }
            $soa = &$this->_SOA;
            if ($this->_isModified) {
                $soa['serial'] = $this->raiseSerial($soa['serial']);
                $this->_isModified = false;
            }
            $tabs = "\t\t\t\t";
            $zone[] = '$ORIGIN ' . $this->_domain . '.';
            $zone[] = implode("\t", array('@', $soa['ttl'], $soa['class'],
                                          'SOA', $soa['origin'], $soa['person'],
                                          '('
                                         )
                             );
            $soa['refresh'] = $this->parseFromSeconds($soa['refresh']);
            $soa['retry']   = $this->parseFromSeconds($soa['retry']);
            $soa['expire']  = $this->parseFromSeconds($soa['expire']);
            $soa['minimum'] = $this->parseFromSeconds($soa['minimum']);
            foreach (array_values($soa) as $item) {
                //Scan all items to see if any are a pear error.
                if (PEAR::isError($item)) {
                    return $item;
                }
            }

            $zone[] = $tabs . $soa['serial']  .    "\t; serial";
            $zone[] = $tabs . $soa['refresh'] .  "\t\t; refresh";
            $zone[] = $tabs . $soa['retry']   .  "\t\t; retry";
            $zone[] = $tabs . $soa['expire']  .  "\t\t; expire";
            $zone[] = $tabs . $soa['minimum'] . ")\t\t; minimum";
            $zone[] = '';

            foreach ($this->_records as $record) {
                $record['ttl'] = $this->parseFromSeconds($record['ttl']);
                if (PEAR::isError($record['ttl'])){
                    return $record['ttl'];
                }

                switch (strtoupper($record['type'])) {
                case 'MX':
                    //MX have an extra element.
                    //The setting itself is in the next item.
                    $zone[] = implode("\t", array(
                                      $record['name'],
                                      $record['ttl'],
                                      $record['class'],
                                      $record['type'],
                                      $record['options']['MXPreference'],
                                      $record['data']));
                    break;

                case 'A':
                case 'AAAA':
                case 'NS':
                case 'CNAME':
                case 'PTR':
                case 'TXT':
                default:
                    $zone[] = implode("\t", $record);
                    break;
                }
            }

            $zone[] = '';
            return $zone;
        }

        // }}}
        // }}}
        // {{{ Modifiers


        // }}}
        // {{{ Setters
        // {{{ setDomainName()

        /**
         * sets the domain name of the currently loaded zone.
         * It also handles changing all the RR's already saved.
         *
         * @param string    $domain  the new domain name
         * @param bool      $migrate whether or not to change all occurances
         *                           of *.oldomain
         *                           to the new domain name.
         *                           Defaults to true.
         * @return bool  true on success, PEAR Error on failure.
         */
        function setDomainName($domain, $migrate = true)
        {
            $valid = '/^[A-Za-z0-9\-\_\.]*$/';
            if (!preg_match($valid, $domain)) {
                return PEAR::raiseError("Unable to set domainname. $domain",
                                        FILE_DNS_INVALID_DOMAIN,
                                        NULL, NULL, $domain);
            }
            $oldDomain = $this->_domain;
            $domain = rtrim($domain, '.');
            $this->_domain = $domain;
            if ($this->_SOA) {
                $this->_isModified = true;
                if ($migrate) {
                    $search = '/^(.*)(' . preg_quote($oldDomain) . ')(\.)$/';
                    $replace = '$1' . $domain . '$3';
                    $this->_SOA['name']   = preg_replace($search, $replace,
                                                         $this->_SOA['name']  );
                    
                    $this->_SOA['origin'] = preg_replace($search, $replace,
                                                         $this->_SOA['origin']);
                    $this->_SOA['person'] = preg_replace($search, $replace,
                                                         $this->_SOA['person']);
                    foreach ($this->_records as $key => $record) {
                        $this->_records[$key]['name'] =
                                                  preg_replace($search, $replace,
                                                  $this->_records[$key]['name']);
                        $this->_records[$key]['data'] =
                                                  preg_replace($search, $replace,
                                                  $this->_records[$key]['data']);
                    }
                }
            }
            return true;
        }

        // }}}
        // {{{ setSOAValue()

        /**
         * sets a specific value in the SOA field.
         *
         * This function updates the list of SOA data we have.
         * List of accepted key => value pairs:
         * <pre>
         * Array
         *   (
         *       [name] => example.com.
         *       [ttl] => 345600
         *       [class] => IN
         *       [origin] => ns1.example.com.
         *       [person] => hostmaster.example.com.
         *       [serial] => 204041514
         *       [refresh] => 14400
         *       [retry] => 1800
         *       [expire] => 86400
         *       [minimum] => 10800
         *   )
         * </pre>
         *
         * @param array  $values A list of key -> value pairs
         * @return bool  true on success, PEAR Error on failure.
         * @see _SOA
         */
        function setSOAValue($values)
        {
            $soa = array();
            if (!is_array($values)) {
                return PEAR::raiseError('Unable to set SOA value.',
                                        FILE_DNS_UPDATE_SOA_FAILED);
            }
            $validKeys = array('name', 'ttl', 'class', 'origin', 'person',
                               'serial', 'refresh', 'retry', 'expire', 'minimum');
            foreach ($values as $key => $value) {
                if (array_search($key, $validKeys) === false) {
                    return PEAR::raiseError('Unable to set SOA value.' .
                                            $key . ' not recognized',
                                            FILE_DNS_UPDATE_SOA_FAILED,
                                            NULL, NULL, $key);
                }

                switch (strtolower($key)) {
                case 'person':
                    $value = str_replace('@', '.', $value);
                    $value = trim($value, '.') . '.';
                case 'name':
                case 'origin':
                    $valid = '/^[A-Za-z0-9\-\_\.]*$/';
                    if (preg_match($valid, $value)) {
                        $soa[$key] = $value;
                    } else {
                        return PEAR::raiseError('Unable to set SOA value. ' .
                                                $key . ' not validl ',
                                                FILE_DNS_UPDATE_SOA_FAILED,
                                                NULL, NULL, $key);
                    }
                    break;
                case 'class':
                        $soa[$key] = $value;
                        break;
                case 'ttl':
                case 'serial':
                case 'refresh':
                case 'retry':
                case 'expire':
                case 'minimum':
                    if (is_numeric($value)) {
                        $soa[$key] = $value;
                    } else {
                        return PEAR::raiseError('Unable to set SOA value. ' .
                                                $key . ' not recognized',
                                                FILE_DNS_UPDATE_SOA_FAILED,
                                                NULL, NULL, $key);
                    }
                    break;
                }


            }
            //If all got parsed, save values.
            
            $this->_SOA = array_merge($this->_SOA, $soa);
            
            return true;
        }

        // }}}
        // {{{ setTTL()

        /**
         * sets the TTL of a specific, or not so specific, record.
         *
         * @param int     $new  The new TTL for this record
         * @param string  $name The name of the record to edit. (NULL for all)
         * @param string  $type The type of the record to edit. (NULL for all)
         * @param string  $data The data of the record to edit. (NULL for all)
         * @return bool   true.
         */
        function setTTL($new, $name = NULL, $type = NULL, $data = NULL)
        {
            $new = abs(intval($new));
            foreach ($this->_records as $key => $record) {
                if (
                    (
                     (NULL == $name)
                     ||
                     (0 == strcasecmp($name, $record['name']) )
                     ||
                     (0 == strcasecmp("$name.{$this->_domain}.", $record['name']) )
                    )
                    &&
                    (
                     (NULL == $type)
                     ||
                     (0 == strcasecmp($type, $record['type']) )
                    )
                    &&
                    (
                     (NULL == $data)
                     ||
                     (0 == strcasecmp($data, $record['data']) )
                    )
                   ) {
                    $this->_records[$key]['ttl'] = $new;
                }
            }
            return true;
        }

        // }}}
        // {{{ setName()

        /**
         * sets the name of a specific, or not so specific, record.
         *
         * @param string  $new  The new name for this record. If needed, the
         *                      current domainname will be automaticly appended.
         * @param string  $name The name of the record to edit. (NULL for all)
         * @param string  $type The type of the record to edit. (NULL for all)
         * @param string  $data The data of the record to edit. (NULL for all)
         * @return bool   true.
         */
        function setName($new, $name = NULL, $type = NULL, $data = NULL)
        {
            $new = strval($new);
            $quotedDomain = preg_quote($this->_domain);
            if (substr($new, -1) == '.') {
                //String already correct.
            } elseif (preg_match("/$quotedDomain" . '$/i', $new)) {
                //String ends with this domain. Append a .
                $new .= '.';
            } else {
                //Subdomain specified. Append domainname
                $new .= '.' . $this->_domain . '.';
            }
            foreach ($this->_records as $key => $record) {
                if (
                    (
                     (NULL == $name)
                     ||
                     (0 == strcasecmp($name, $record['name']))
                     ||
                     (0 == strcasecmp("$name.{$this->_domain}.", $record['name']))
                    )
                    &&
                    (
                     (NULL == $type)
                     ||
                     (0 == strcasecmp($type, $record['type']))
                    )
                    &&
                    (
                     (NULL == $data)
                     ||
                     (0 == strcasecmp($data, $record['data']))
                    )
                   ) {
                    $this->_records[$key]['name'] = $new;
                }
            }
            return true;
        }

        // }}}
        // {{{ setData()

        /**
         * sets the Value of a specific, or not so specific, record.
         *
         * @param string  $new    The new Value for this record
         * @param string  $name   The name of the record to edit. (NULL for all)
         * @param string  $type   The type of the record to edit. (NULL for all)
         * @param string  $data   The data of the record to edit. (NULL for all)
         * @return bool   true on success, PEAR_ERROR on error.
         *
         */
        function setValue($new, $name = NULL, $type = NULL, $data = NULL)
        {
            $new = strval($new);
            foreach ($this->_records as $key => $record) {
                if (
                    (
                     (NULL == $name)
                     ||
                     (0 == strcasecmp($name, $record['name']) )
                     ||
                     (0 == strcasecmp("$name.{$this->_domain}.", $record['name']))
                    )
                    &&
                    (
                     (NULL == $type)
                     ||
                     (0 == strcasecmp($type, $record['type']))
                    )
                    &&
                    (
                     (NULL == $data )
                     ||
                     (0 == strcasecmp($data, $record['data']) )
                    )
                   ) {
                    $this->_records[$key]['data'] = $new;
                }
            }
            return true;
        }

        // }}}
        // {{{ setMXPref()

        /**
         * sets the MX Preference of an MX record
         * to the specified value.
         *
         * @param int    $pref   the preference level.
         * @param string $server the mailserver this MX points to.   (NULL for all)
         * @param string $name   the (sub)domain this MX applies to. (NULL for all)
         * @return bool  true on success, PEAR Error on failure.
         */
        function setMXPref($pref, $server = NULL, $name = NULL )
        {
            $pref = intval($pref);
            $quotedDomain = preg_quote($this->_domain);
            if ($name === NULL) {
                //Null filled in, leave it like that.
            } elseif (!$name) {
                //name string left empty? Set to NULL
                $name = NULL;
            } elseif (substr($name, -1) == '.') {
                //String already correct.
            } elseif (preg_match("/$quotedDomain" . '$/i', $name)) {
                //String ends with this domain. Append a .
                $name .= '.';
            } else {
                //Subdomain specified. Append domainname
                $name .= '.' . $this->_domain . '.';
            }
            if ($server === NULL) {
                //Null filled in, leave it like that.
            } elseif (!$server) {
                //Server string left empty? Set to NULL
                $server = NULL;
            } elseif (substr($server, -1) == '.') {
                //String already correct.
            } elseif (preg_match("/$quotedDomain" . '$/i', $server)) {
                //String ends with this domain. Append a .
                $server .= '.';
            } else {
                //Subdomain specified. Append domainname
                $server .= '.' . $this->_domain . '.';
            }
            foreach ($this->_records as $key => $record) {
                if (
                    ($record['type'] == 'MX')
                    &&
                    ( ($server == NULL) || ($server == $record['data']) )
                    &&
                    ( ($name   == NULL) || ($name   == $record['name']) )
                    ) {
                    if (!isset($this->_records[$key]['options'])) {
                        $this->_records[$key]['options'] = array();
                    }
                    $this->_records[$key]['options']['MXPreference'] = $pref;
                }
            }
            return true;
        }

        // }}}
        // }}}
        // {{{ Static functions
        // {{{ raiseSerial()

        /**
         * generate a new serial based on given one.
         *
         * This generates a new serial, based on the often used format
         * YYYYMMDDXX where XX is an ascending serial,
         * allowing up to 100 edits per day. After that the serial wraps
         * into the next day and it still works.
         *
         * @param int  $serial Current serial
         * @static
         * @return int New serial
         */
        function raiseSerial($serial=0)
        {
            if (substr($serial, 0, 8) == date('Ymd')) {
                //Serial's today. Simply raise it.
                $serial = $serial + 1;
            } elseif ($serial > date('Ymd00')) {
                //Serial's after today.
                $serial = $serial + 1;
            } else {
                //Older serial. Generate new one.
                $serial = date('Ymd00');
            }
            return intval($serial);
        }

        // }}}
        // {{{ parseToSeconds()

        /**
         * converts a BIND-style timeout(1D, 2H, 15M) to seconds.
         *
         * @param string  $time Time to convert.
         * @static
         * @return int    time in seconds on success, PEAR error on failure.
         */
        function parseToSeconds($time)
        {
            if (is_numeric($time)) {
                //Already a number. Return.
                return $time;
            } else {
                $pattern = '/([0-9]+)([a-zA-Z]+)/';
                $split = preg_split($pattern, $time, -1,
                                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                if (count($split) != 2) {
                    return PEAR::raiseError("Unable to parse time. $time",
                                            FILE_DNS_PARSE_TIME_FAILED,
                                            NULL, NULL, $time);
                }
                list($num, $what) = $split;
                switch (strtoupper($what))
                {
                    case 'S':
                        $times = 1; //Seconds
                        break;
                    case 'M':
                        $times = 1 * 60; //Minute
                        break;
                    case 'H':
                        $times = 1 * 60 * 60; //Hour
                        break;
                    case 'D':
                        $times = 1 * 60 * 60 * 24; //Day
                        break;
                    case 'W':
                        $times = 1 * 60 * 60 * 24 * 7; //Week
                        break;
                    default:
                        return PEAR::raiseError("Unable to parse time. $time",
                                                FILE_DNS_PARSE_TIME_FAILED,
                                                NULL, NULL, $time);
                        break;
                }
                $time = $num * $times;
                return $time;
            }
        }

        // }}}
        // {{{ parseFromSeconds()

        /**
         * converts seconds to BIND-style timeout(1D, 2H, 15M).
         *
         * @param  int    seconds to convert
         * @static
         * @return string String with time on success, PEAR error on failure.
         *
         */
        function parseFromSeconds($ttl)
        {
            $ttl = intval($ttl);
            if (!is_int($ttl)) {
                return PEAR::raiseError("Unable to parse time back. $ttl",
                                        FILE_DNS_PARSEBACK_TIME_FAILED,
                                        NULL, NULL, $ttl);
            } elseif (is_int($num = ($ttl / ( 1 * 60 * 60 * 24 * 7)))) {
                return "$num" . 'W';
            } elseif (is_int($num = ($ttl / ( 1 * 60 * 60 * 24)))) {
                return "$num" . 'D';
            } elseif (is_int($num = ($ttl / ( 1 * 60 * 60)))) {
                return "$num" . 'H';
            } elseif (is_int($num = ($ttl / ( 1 * 60)))) {
                return "$num" . 'M';
            } elseif (is_int($num = ($ttl / ( 1)))) {
                return "$num";
            }
        }

        // }}}
        // {{{ isIP()


        /**
         * checks if a value is an IP address or not.
         *
         * @param string    Value to check.
         * @static
         * @return bool     true or false.
         */
        function isIP($value)
        {
            // http://www.regular-expressions.info/regexbuddy/ipaccurate.html
            $ipaccurate = '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}'.
                '(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/';
            return preg_match($ipaccurate, $value);
        }

        // }}}
        // {{{ apiVersion()

        /**
         * returns the API version
         *
         * @return int      The API version number
         * @static
         * @access public
         */
        function apiVersion()
        {
            return '0.1.0';
        }

        // }}}
        // }}}

    }

    class glesys_api {
        public string $api_user;
        public string $api_key;
        public string $api_url;
        public ?idna_convert $punycode = null;
        public ?array $response = null;

        function add_domain($domainname, $data = array()) {
            $args = array(
                'domainname'	=> $this->punycode_endoce($domainname)
            );
            if (!empty($data))
            {
                $args = array_merge($args, $data);
            }
            $success = $this->api_request('domain/add', $args);
            return($success);
        }
        
        public function add_record($data)
        {
            $data['domainname'] = $this->punycode_endoce($data['domainname']);
            $success = $this->api_request('domain/addrecord', $data);
            return($success);
        }

        function api_request($request, $args = array()) {
            $url = $this->api_url . $request . '/format/json';
            $ch = curl_init();
            
            curl_setopt_array($ch, array(
                CURLOPT_POST			=> true,
                CURLOPT_POSTFIELDS		=> $args,
                CURLOPT_RETURNTRANSFER	=> true,
                CURLOPT_TIMEOUT			=> 30,
                CURLOPT_URL				=> $url,
                CURLOPT_USERPWD			=> $this->api_user . ':' . $this->api_key,
                CURLOPT_CUSTOMREQUEST	=> 'POST',
            ));
            
            $response = curl_exec($ch);
            if (empty($response))
            {
                return(false);
            }
            $this->response = json_decode($response, true);
            $this->response = $this->response['response'];
            // Just make sure the return code is 200.
            if ($this->response['status']['code'] != 200)
            {
                echo 'Err: Code '.$this->response['status']['code'];
                echo ': '.$this->response['status']['text'].PHP_EOL;
                print_r($this->response['debug']['input']);
                return(false);
            }
            // If we get here everything should be fine.
            return(true);
        }
        
        public function punycode_endoce($string)
        {
            if (empty($this->punycode))
            {
                $this->punycode = new idna_convert();
            }
            return($this->punycode->encode($string));
        }
        /**
         * punydecode domain names
         */
        public function punycode_dedoce($string)
        {
            if (empty($this->punycode))
            {
                $this->punycode = new idna_convert();
            }
            return($this->punycode->decode($string));
        }
    }
?>