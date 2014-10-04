<?php
/**
 * @package GleSYS API
 *
 * @copyright (c) 2013 Jari (tumba25) Kanerva <jari@tumba25.net> http://www.tumba25.com
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 */


class glesys_api
{
	private $api_user	= '';
	private $api_key	= '';
	private $api_url	= 'https://api.glesys.com/';

	protected $response = false;
	protected $punycode = false;

	public function __construct($api_user, $api_key, $api_url = false)
	{
		if ($api_user)
		{
			$this->api_user = $api_user;
		}

		if ($api_key)
		{
			$this->api_key = $api_key;
		}

		if ($api_url)
		{
			$this->api_url = $api_url;
		}

		// Make sure the API URL ends with a slash.
		if (substr($this->api_url, -1) != '/')
		{
			$this->api_url .= '/';
		}
	}

	/**
	 * Makes the actual request.
	 *
	 * @param string, $request ex: 'email/overview'.
	 * @param array $args or bool false if no arguments are to be passed.
	 * @param bool $use_post: true to use POST or false to use GET.
	 * @return true on success or false on error.
	 */
	protected function api_request($request, $args = array())
	{
		$url = $this->api_url . $request . '/format/json';

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_POST			=> true,
			CURLOPT_POSTFIELDS		=> $args,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_SSL_VERIFYHOST	=> 0,
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
			$this->response = array(
				'code'	=> $this->response['status']['code'],
				'text'	=> $this->response['status']['text'],
				'debug'	=> $this->response['debug']
			);

			return(false);
		}

		// If we get here everything should be fine.
		return(true);
	}

	public function fetch_response()
	{
		return($this->response);
	}

	/**
	 * punycode domain names
	 */
	public function punycode_endoce($string)
	{
		if (empty($this->punycode))
		{
			$phpEx = substr(strrchr(__FILE__, '.'), 1);
			$path = dirname(__FILE__);
			require("$path/idna_convert.$phpEx");
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
			$phpEx = substr(strrchr(__FILE__, '.'), 1);
			$path = dirname(__FILE__);
			require("$path/idna_convert.$phpEx");
			$this->punycode = new idna_convert();
		}

		return($this->punycode->decode($string));
	}
}
