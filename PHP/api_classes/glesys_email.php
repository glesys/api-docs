<?php
/**
 * @copyright (c) 2013 Jari (tumba25) Kanerva <jari@tumba25.net> http://www.tumba25.com
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 */

if (!class_exists('glesys_api'))
{
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	$path = dirname(__FILE__);
	require("$path/glesys_api.$phpEx");
}

class glesys_email extends glesys_api
{
	/**
	 * Get a overview of all domains and count of email accounts and alias.
	 *
	 * @param void
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_overview()
	{
		$success = $this->api_request('email/overview');

		if ($success)
		{
			// Shorten the array a bit.
			$response = array(
				'status'	=> $this->response['status'],
				'summary'	=> $this->response['overview']['summary'],
				'domains'	=> $this->response['overview']['domains'],
				'debug'		=> $this->response['debug'],
			);
			$this->response = $response;
		}

		return($success);
	}

	/**
	 * Get or set the global quota.
	 * The global quota is the total amount of space that is allowed to be allocated to email accounts.
	 * The globalquota argument should be in MB.
	 *
	 * @param int optional $quota. The new quota to set.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_globalquota($quota = '')
	{
		$quota = (int) $quota;

		if (!empty($quota))
		{
			$args = array(
				'globalquota' => $quota,
			);
		}
		else
		{
			$args = array();
		}

		$success = $this->api_request('email/globalquota', $args);
		return($success);
	}

	/**
	 * Gets a list of all accounts and aliases of a domain with full details.
	 * It is also possible to filter the results by setting the optional filter parameter to an existing alias or account.
	 * Filter must be a valid email address.
	 * Example: user@example.com.
	 *
	 * @param string $domainname required.
	 * @param string $filter optional.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_list($domainname, $filter = '')
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
		);

		if (!empty($filter))
		{
			$args['filter'] = $filter;
		}

		$success = $this->api_request('email/list', $args);

		if ($success)
		{
			// Shorten the array a bit.
			$response = array(
				'status'		=> $this->response['status'],
				'emailaccounts'	=> $this->response['list']['emailaccounts'],
				'emailaliases'	=> $this->response['list']['emailaliases'],
				'debug'			=> $this->response['debug'],
			);
			$this->response = $response;
		}

		return($success);
	}

	/**
	 * Edit an email account and change things like quota, password, autoresponse and antispam level.
	 * Allowed values for antispam is 0-5. Quota is in MB.
	 *
	 * @param string $emailaccount required.
	 * @param array $args, all array parts are optional array(
	 *   'antispamlevel'	=> 0 - 5
	 *   'antivirus'		=> 0|1
	 *   'password'			=> new password
	 *   'autorespond'		=> 0|1
	 *   'autorespondsaveemail'	=> 0|1
	 *   'autorespondmessage'	=> string with message
	 *   'quota'			=> new quota in MB
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_editaccount($emailaccount, $data = array())
	{
		$args = array(
			'emailaccount'	=> $emailaccount,
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('email/editaccount', $args);
		return($success);
	}

	/**
	 * Delete an email-account or alias.
	 *
	 * @param string $email required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_delete($email)
	{
		$args = array(
			'email'	=> $email,
		);

		$success = $this->api_request('email/delete', $args);
		return($success);
	}

	/**
	 * Create an email account.
	 * Allowed values for antispam is 0-5. Quota is in MB.
	 *
	 * @param string $emailaccount required.
	 * @param string $password required.
	 * @param array $args, all array parts are optional array(
	 *   'antispamlevel'	=> 0 - 5
	 *   'antivirus'		=> 0|1
	 *   'autorespond'		=> 0|1
	 *   'autorespondsaveemail'	=> 0|1
	 *   'autorespondmessage'	=> string with message
	 *   'quota'			=> new quota in MB
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_createaccount($emailaccount, $password, $data = array())
	{
		$args = array(
			'emailaccount'	=> $emailaccount,
			'password'		=> $password,
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('email/createaccount', $args);
		return($success);
	}

	/**
	 * Gets quota information about an account.
	 * How much is being used and how much is available.
	 *
	 * @param string $emailaccount required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_quota($emailaccount)
	{
		$args = array(
			'emailaccount'	=> $emailaccount,
		);

		$success = $this->api_request('email/quota', $args);
		return($success);
	}

	/**
	 * Create an email alias.
	 *
	 * @param string $emailaccount required.
	 * @param string $goto required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_createalias($emailalias, $goto)
	{
		$args = array(
			'emailalias'	=> $emailalias,
			'goto'			=> $goto,
		);

		$success = $this->api_request('email/createalias', $args);
		return($success);
	}

	/**
	 * Edit an email alias.
	 *
	 * @param string $emailaccount required.
	 * @param string $goto required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_editalias($emailalias, $goto)
	{
		$args = array(
			'emailalias'	=> $emailalias,
			'goto'			=> $goto,
		);

		$success = $this->api_request('email/editalias', $args);
		return($success);
	}

	/**
	 * Get email related costs and pricelists.
	 *
	 * @param void
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function email_costs()
	{
		$success = $this->api_request('email/costs');
		return($success);
	}
}
