/**
 * @copyright (c) 2013 Jari (tumba25) Kanerva <jari@tumba25.net> http://www.tumba25.com
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 */

glesys_api.php is the main class that does the actual request. All other files includes glesys_api.php
and tries to find it in the same directory where the including class file is. If they aren't in the same
directory you need to change the path in the class files you use.

glesys_api.php also includes idna_convert.php to punycode internationalized domain names.

All functions return boolean true on success and false otherwise.
By calling $glesys_api->fetch_response() after the request you'll get a array with request status,
eventual requested info and debug info containing the POST data sent with the request if any.
This is what $glesys_api->fetch_response() returns on failure.
array(
	'code'	=> (int)	response code
	'text'	=> (string)	response/error text
	'debug'	=> array(
		'input'	=> array(
			The POST data sent to the API
		)
	)
);

What it returns on successful depends on the called function and the parameters given. There is a .txt file
in the docs folder for each class where you can see the parameters for each function.

You can also use $glesys_api->encode() and $glesys_api->decode() to convert domain names to and from punycode.
The methods in these classes calls $glesys_api->encode() so you don't need to do that before calling them.
