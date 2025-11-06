<?php
    /*
    *   Author: Johan Sölve, Martin Bagge
    *   Date:   2025-11-06, 2026-10-14
    *   Purpose: Export DNS zone from Glesys API to BIND format zone file
    */
    require_once __DIR__.'/../api_classes/idna_convert.php';

    // Log and error messages go to stderr so stdout only carries the zone file
    function log_msg($message) {
        fwrite(STDERR, $message.PHP_EOL);
    }

    if (count($argv) < 4) {
        log_msg('Err: 3 arguments required.');
        log_msg('Usage: php php_glesys_zone_export.php <domain> <api user> <api key> [output file]');
        log_msg('Example: php php_glesys_zone_export.php example.com CL12345 your-api-key zone.txt');
        log_msg('');
        log_msg('If output file is not specified, zone will be written to stdout.');
        exit(1);
    }

    $domain     = strtolower($argv[1]);
    $api_user   = $argv[2];
    $api_key    = $argv[3];
    $output_file = isset($argv[4]) ? $argv[4] : null;
    $api_url    = 'https://api.glesys.com/';

    $glesys = new glesys_api();
    $glesys->api_user = $api_user;
    $glesys->api_key = $api_key;
    $glesys->api_url = $api_url;

    log_msg('Log: Fetching zone data for '.$domain.'...');

    // Get domain details (SOA information)
    if (!$glesys->get_domain_details($domain)) {
        log_msg('Err: Failed to fetch domain details!');
        $error = $glesys->get_last_error();
        if ($error) {
            log_msg('Err: '.$error['text']);
        }
        exit(1);
    }

    $details = $glesys->response;

    // Get all DNS records
    if (!$glesys->list_records($domain)) {
        log_msg('Err: Failed to fetch DNS records!');
        $error = $glesys->get_last_error();
        if ($error) {
            log_msg('Err: '.$error['text']);
        }
        exit(1);
    }

    $records = $glesys->response['records'];

    log_msg('Log: Found '.count($records).' records');

    // Build zone file content
    $zone_content = generate_zone_file($domain, $details, $records);

    // Write to file or stdout
    if ($output_file) {
        if (file_put_contents($output_file, $zone_content) === false) {
            log_msg('Err: Failed to write to file '.$output_file);
            exit(1);
        }
        log_msg('Log: Zone file written to '.$output_file);
    } else {
        echo $zone_content;
    }

    log_msg('Log: Export completed successfully.');
    exit(0);

    /**
     * Generate BIND format zone file from Glesys API data
     */
    function generate_zone_file($domain, $details, $records) {
        $output = '';

        // Add header comment
        $output .= '; Zone file for '.$domain.PHP_EOL;
        $output .= '; Exported from Glesys API on '.date('Y-m-d H:i:s').PHP_EOL;
        $output .= ';'.PHP_EOL;

        // Extract SOA information from details (data is under 'domain' key)
        $domain_data = isset($details['domain']) ? $details['domain'] : array();
        $soa_ttl = isset($domain_data['ttl']) ? $domain_data['ttl'] : 3600;
        $primary_ns = isset($domain_data['primarynameserver']) ? $domain_data['primarynameserver'] : 'ns1.glesys.se.';
        $responsible = isset($domain_data['responsibleperson']) ? $domain_data['responsibleperson'] : 'registry.glesys.se.';
        $serial = date('Ymd').'01'; // The API does not expose the serial, generate one from current date
        $refresh = isset($domain_data['refresh']) ? $domain_data['refresh'] : 28800;
        $retry = isset($domain_data['retry']) ? $domain_data['retry'] : 7200;
        $expire = isset($domain_data['expire']) ? $domain_data['expire'] : 604800;
        $minimum = isset($domain_data['minimum']) ? $domain_data['minimum'] : 86400;

        // Ensure domain and nameserver end with dot
        $domain_fqdn = rtrim($domain, '.').'.';
        $primary_ns_fqdn = rtrim($primary_ns, '.').'.';
        $responsible_fqdn = rtrim($responsible, '.').'.';

        // Write SOA record
        $output .= '$ORIGIN '.$domain_fqdn.PHP_EOL;
        $output .= '$TTL '.$soa_ttl.PHP_EOL;
        $output .= '@    IN    SOA    '.$primary_ns_fqdn.' '.$responsible_fqdn.' ('.PHP_EOL;
        $output .= '                    '.$serial.'    ; Serial'.PHP_EOL;
        $output .= '                    '.$refresh.'    ; Refresh'.PHP_EOL;
        $output .= '                    '.$retry.'    ; Retry'.PHP_EOL;
        $output .= '                    '.$expire.'    ; Expire'.PHP_EOL;
        $output .= '                    '.$minimum.' )  ; Minimum TTL'.PHP_EOL;
        $output .= PHP_EOL;

        // Group records by type for better readability
        $records_by_type = array(
            'NS' => array(),
            'A' => array(),
            'AAAA' => array(),
            'MX' => array(),
            'CNAME' => array(),
            'TXT' => array(),
            'SRV' => array(),
            'CAA' => array(),
            'OTHER' => array()
        );

        foreach ($records as $record) {
            $type = strtoupper($record['type']);
            if (isset($records_by_type[$type])) {
                $records_by_type[$type][] = $record;
            } else {
                $records_by_type['OTHER'][] = $record;
            }
        }

        // Write records by type
        foreach ($records_by_type as $type => $type_records) {
            if (empty($type_records)) {
                continue;
            }

            $output .= '; '.$type.' Records'.PHP_EOL;

            foreach ($type_records as $record) {
                $output .= format_record($record, $domain);
            }

            $output .= PHP_EOL;
        }

        return $output;
    }

    /**
     * Format a single DNS record in BIND format
     */
    function format_record($record, $domain) {
        $host = $record['host'];
        $ttl = isset($record['ttl']) ? $record['ttl'] : '';
        $type = strtoupper($record['type']);
        $data = $record['data'];

        // Handle host field - convert to relative or @
        if ($host === $domain || $host === $domain.'.') {
            $host = '@';
        } elseif (substr($host, -strlen($domain)-1) === '.'.$domain) {
            // Remove domain suffix to make relative
            $host = substr($host, 0, -strlen($domain)-1);
        } elseif (substr($host, -strlen($domain)-2) === '.'.$domain.'.') {
            // Remove domain suffix with trailing dot
            $host = substr($host, 0, -strlen($domain)-2);
        }

        // Pad host field for alignment
        $host_padded = str_pad($host, 20);

        // Pad TTL field
        $ttl_padded = $ttl ? str_pad($ttl, 8) : str_pad('', 8);

        // TXT records need quoting, all other types are written as-is
        if ($type === 'TXT') {
            $data = format_txt_data($data);
        }

        return $host_padded.$ttl_padded.'IN    '.$type.'    '.$data.PHP_EOL;
    }

    /**
     * Quote TXT record data, escaping backslashes and quotes and splitting
     * it into strings of at most 255 bytes as required by BIND
     */
    function format_txt_data($data) {
        // Data that is already quoted is assumed to be in zone file format
        if (strlen($data) >= 2 && $data[0] === '"' && substr($data, -1) === '"') {
            return $data;
        }

        if ($data === '') {
            return '""';
        }

        $chunks = array();
        foreach (str_split($data, 255) as $chunk) {
            $chunks[] = '"'.addcslashes($chunk, '"\\').'"';
        }
        return implode(' ', $chunks);
    }

    // Glesys API Client Class
    class glesys_api {
        public string $api_user;
        public string $api_key;
        public string $api_url;
        public ?idna_convert $punycode = null;
        public ?array $response = null;
        private ?array $last_error = null;

        function get_domain_details($domainname) {
            $args = array(
                'domainname' => $this->punycode_endoce($domainname)
            );
            $success = $this->api_request('domain/details', $args);
            return $success;
        }

        function list_records($domainname) {
            $args = array(
                'domainname' => $this->punycode_endoce($domainname)
            );
            $success = $this->api_request('domain/listrecords', $args);
            return $success;
        }

        function api_request($request, $args = array()) {
            $url = $this->api_url . $request . '/format/json';
            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $args,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_TIMEOUT         => 30,
                CURLOPT_URL             => $url,
                CURLOPT_USERPWD         => $this->api_user . ':' . $this->api_key,
                CURLOPT_CUSTOMREQUEST   => 'POST',
            ));

            $response = curl_exec($ch);
            if ($response === false) {
                $this->last_error = array(
                    'code' => curl_errno($ch),
                    'text' => 'Request failed: '.curl_error($ch)
                );
                return false;
            }
            if (empty($response)) {
                $this->last_error = array(
                    'code' => 0,
                    'text' => 'Empty response from API'
                );
                return false;
            }

            $decoded = json_decode($response, true);
            if (!$decoded) {
                $this->last_error = array(
                    'code' => 0,
                    'text' => 'Failed to decode JSON response'
                );
                return false;
            }

            $this->response = $decoded['response'];

            // Check return code
            if ($this->response['status']['code'] != 200) {
                $this->last_error = array(
                    'code' => $this->response['status']['code'],
                    'text' => $this->response['status']['text']
                );
                return false;
            }

            return true;
        }

        public function get_last_error() {
            return $this->last_error;
        }

        public function punycode_endoce($string) {
            if (empty($this->punycode)) {
                $this->punycode = new idna_convert();
            }
            return $this->punycode->encode($string);
        }

        public function punycode_dedoce($string) {
            if (empty($this->punycode)) {
                $this->punycode = new idna_convert();
            }
            return $this->punycode->decode($string);
        }
    }
