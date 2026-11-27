## Synopsis

This project contains CLI tools for importing and exporting DNS zone files to/from your Glesys account.

## Requirements

PHP 7.4 or later with the curl extension.

Both scripts use `../api_classes/idna_convert.php` for international domain names,
so they must be run from within this repository layout.

## Credentials

Both scripts read your API credentials from the `GLESYS_API_USER` and `GLESYS_API_KEY`
environment variables:

    export GLESYS_API_USER=CL12345
    read -s GLESYS_API_KEY && export GLESYS_API_KEY

## Import - php_glesys_zone_import.php

Import a BIND-format DNS zone file to Glesys DNS.

### Usage

php php_glesys_zone_import.php <DNS zone file> <optional: domain name>

### Author

The script was created by Tobias Rahm 2015-11-28

## Export - php_glesys_zone_export.php

Export a DNS zone from Glesys to BIND-format zone file.

### Usage

php php_glesys_zone_export.php <domain> [output file]

If output file is not specified, the zone will be written to stdout. Log and error
messages are written to stderr, so stdout can be redirected straight to a file.

### Examples

Export to file:
php php_glesys_zone_export.php example.com zone.txt

Export to stdout:
php php_glesys_zone_export.php example.com
